<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\SocialProvider;
use App\Domain\Identity\Events\SocialAccountLinked;
use App\Domain\Identity\Events\SocialLoginSucceeded;
use App\Domain\Identity\Exceptions\SocialIdentityConflictException;
use App\Domain\Identity\Models\SocialAccount;
use App\Domain\Identity\Services\SocialCallbackResult;
use App\Domain\Identity\Services\SocialiteProfileFactory;
use App\Domain\Identity\Services\SocialProfile;
use App\Support\SafeLogContext;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final class HandleSocialCallbackAction
{
    public function __construct(
        private readonly SocialiteProfileFactory $profileFactory,
    ) {}

    public function handle(
        SocialProvider $provider,
        SocialiteUser $socialiteUser,
        ?User $linkingUser = null,
    ): SocialCallbackResult {
        Log::info('[identity.social.callback] started', SafeLogContext::from([
            'provider' => $provider->value,
            'linking_flow' => $linkingUser !== null,
            'linking_user_id' => $linkingUser?->id,
        ]));

        $profile = $this->profileFactory->make($provider, $socialiteUser);

        if ($linkingUser !== null) {
            return $this->linkAccount($linkingUser, $profile);
        }

        return $this->loginOrCreateAccount($profile);
    }

    private function loginOrCreateAccount(SocialProfile $profile): SocialCallbackResult
    {
        /** @var SocialAccount|null $existingAccount */
        $existingAccount = SocialAccount::query()
            ->with('user')
            ->where('provider', $profile->provider->value)
            ->where('provider_user_id', $profile->providerUserId)
            ->first();

        if ($existingAccount !== null) {
            $this->persistSocialSnapshot($existingAccount, $profile);
            $user = $existingAccount->user;

            SocialLoginSucceeded::dispatch($user, $profile->provider, false);

            Log::info('[identity.social.callback] existing social account authenticated', SafeLogContext::from([
                'provider' => $profile->provider->value,
                'user_id' => $user->id,
            ]));

            return new SocialCallbackResult(
                user: $user,
                linkingFlow: false,
                linked: false,
                createdUser: false,
            );
        }

        $userEmail = $this->resolveEmailForNewUser($profile);
        $userName = $this->resolveNameForNewUser($profile);

        /** @var User $user */
        $user = DB::transaction(function () use ($profile, $userEmail, $userName): User {
            $user = User::query()->create([
                'name' => $userName,
                'email' => $userEmail,
                'password' => Str::password(40),
            ]);

            SocialAccount::query()->create($this->socialAccountAttributes($user->id, $profile));

            return $user;
        });

        SocialAccountLinked::dispatch($user, $profile->provider, $profile->providerUserId);
        SocialLoginSucceeded::dispatch($user, $profile->provider, true);

        Log::info('[identity.social.callback] new user created from social account', SafeLogContext::from([
            'provider' => $profile->provider->value,
            'user_id' => $user->id,
        ]));

        return new SocialCallbackResult(
            user: $user,
            linkingFlow: false,
            linked: true,
            createdUser: true,
        );
    }

    private function linkAccount(User $linkingUser, SocialProfile $profile): SocialCallbackResult
    {
        $linked = DB::transaction(function () use ($linkingUser, $profile): bool {
            /** @var SocialAccount|null $ownedByAnotherUser */
            $ownedByAnotherUser = SocialAccount::query()
                ->where('provider', $profile->provider->value)
                ->where('provider_user_id', $profile->providerUserId)
                ->where('user_id', '!=', $linkingUser->id)
                ->lockForUpdate()
                ->first();

            if ($ownedByAnotherUser !== null) {
                throw new SocialIdentityConflictException('social_account_already_linked_to_another_user');
            }

            /** @var SocialAccount|null $account */
            $account = SocialAccount::query()
                ->where('provider', $profile->provider->value)
                ->where('user_id', $linkingUser->id)
                ->lockForUpdate()
                ->first();

            if ($account === null) {
                SocialAccount::query()->create($this->socialAccountAttributes($linkingUser->id, $profile));

                return true;
            }

            $wasAlreadyLinked = $account->provider_user_id === $profile->providerUserId;
            $account->provider_user_id = $profile->providerUserId;
            $this->persistSocialSnapshot($account, $profile);

            return ! $wasAlreadyLinked;
        });

        if ($linked) {
            SocialAccountLinked::dispatch($linkingUser, $profile->provider, $profile->providerUserId);
        }

        Log::info('[identity.social.callback] linking flow completed', SafeLogContext::from([
            'provider' => $profile->provider->value,
            'user_id' => $linkingUser->id,
            'linked' => $linked,
        ]));

        return new SocialCallbackResult(
            user: $linkingUser,
            linkingFlow: true,
            linked: $linked,
            createdUser: false,
        );
    }

    private function resolveEmailForNewUser(SocialProfile $profile): string
    {
        if ($profile->email === null) {
            return sprintf('%s@%s.idshka.local', $profile->providerUserId, $profile->provider->value);
        }

        $email = mb_strtolower($profile->email);
        $isTaken = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        if ($isTaken) {
            throw new SocialIdentityConflictException('email_already_exists_link_account_first');
        }

        return $email;
    }

    private function resolveNameForNewUser(SocialProfile $profile): string
    {
        if ($profile->name !== null) {
            return Str::limit($profile->name, 120, '');
        }

        return Str::limit($profile->provider->label().' user '.$profile->providerUserId, 120, '');
    }

    /**
     * @return array<string, mixed>
     */
    private function socialAccountAttributes(int $userId, SocialProfile $profile): array
    {
        return [
            'user_id' => $userId,
            'provider' => $profile->provider->value,
            'provider_user_id' => $profile->providerUserId,
            'email' => $profile->email,
            'name' => $profile->name,
            'avatar_url' => $profile->avatarUrl,
            'access_token_encrypted' => $this->encryptNullable($profile->accessToken),
            'refresh_token_encrypted' => $this->encryptNullable($profile->refreshToken),
            'expires_at' => $profile->expiresAt,
        ];
    }

    private function persistSocialSnapshot(SocialAccount $account, SocialProfile $profile): void
    {
        $account->fill([
            'email' => $profile->email,
            'name' => $profile->name,
            'avatar_url' => $profile->avatarUrl,
            'access_token_encrypted' => $this->encryptNullable($profile->accessToken),
            'refresh_token_encrypted' => $this->encryptNullable($profile->refreshToken),
            'expires_at' => $profile->expiresAt,
        ]);
        $account->save();
    }

    private function encryptNullable(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Crypt::encryptString($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
