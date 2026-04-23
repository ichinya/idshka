<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\SocialProvider;
use App\Domain\Identity\Events\SocialAccountUnlinked;
use App\Domain\Identity\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class UnlinkSocialAccountAction
{
    public function handle(User $user, SocialProvider $provider): bool
    {
        Log::info('[identity.social.unlink] started', [
            'user_id' => $user->id,
            'provider' => $provider->value,
        ]);

        $deleted = SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider->value)
            ->delete();

        if ($deleted < 1) {
            Log::warning('[identity.social.unlink] no linked account found', [
                'user_id' => $user->id,
                'provider' => $provider->value,
            ]);

            return false;
        }

        SocialAccountUnlinked::dispatch($user, $provider);

        Log::info('[identity.social.unlink] completed', [
            'user_id' => $user->id,
            'provider' => $provider->value,
        ]);

        return true;
    }
}
