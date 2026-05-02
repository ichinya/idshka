<?php

namespace Tests\Feature;

use App\Domain\Identity\Events\PasswordLoginSucceeded;
use App\Domain\Identity\Events\SocialAccountLinked;
use App\Domain\Identity\Events\SocialAccountUnlinked;
use App\Domain\Identity\Events\SocialLoginSucceeded;
use App\Domain\Identity\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthSocialiteFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_portal_redirects_to_browser_login_page(): void
    {
        $this->get('/portal')->assertRedirect('/login');

        $this->get('/login')
            ->assertOk()
            ->assertSee('IDShka sign in');
    }

    public function test_browser_login_form_redirects_to_portal_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
        ]);

        $this
            ->from('/login')
            ->post('/login', [
                'email' => 'owner@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/portal');

        $this->assertAuthenticatedAs($user);
    }

    public function test_browser_login_form_returns_to_login_with_errors_for_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'owner@example.com',
        ]);

        $this
            ->from('/login')
            ->post('/login', [
                'email' => 'owner@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_browser_registration_form_redirects_to_portal_dashboard(): void
    {
        $this
            ->from('/login')
            ->post('/register', [
                'name' => 'Alice Owner',
                'email' => 'alice@example.com',
                'password' => 'correct-password',
                'password_confirmation' => 'correct-password',
            ])
            ->assertRedirect('/portal');

        $this->assertAuthenticated('web');
        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
        ]);
    }

    public function test_browser_logout_redirects_home(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest('web');
    }

    public function test_user_can_register_login_and_logout_with_session_auth(): void
    {
        Event::fake([PasswordLoginSucceeded::class]);

        $registerResponse = $this->postJson('/register', [
            'name' => 'Alice Owner',
            'email' => 'alice@example.com',
            'password' => 'correct-password',
            'password_confirmation' => 'correct-password',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('name', 'Alice Owner')
            ->assertJsonPath('email', 'alice@example.com');

        $registeredUser = User::query()->where('email', 'alice@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($registeredUser);
        Event::assertDispatched(
            PasswordLoginSucceeded::class,
            fn (PasswordLoginSucceeded $event): bool => $event->user->is($registeredUser)
                && $event->viaRegistration === true
        );

        $this->postJson('/logout')->assertOk()->assertJsonPath('status', 'logged_out');
        $this->assertGuest('web');

        Event::fake([PasswordLoginSucceeded::class]);

        $loginResponse = $this->postJson('/login', [
            'email' => 'ALICE@example.com',
            'password' => 'correct-password',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('user_id', $registeredUser->id)
            ->assertJsonPath('email', 'alice@example.com');

        $this->assertAuthenticatedAs($registeredUser);
        Event::assertDispatched(
            PasswordLoginSucceeded::class,
            fn (PasswordLoginSucceeded $event): bool => $event->user->is($registeredUser)
                && $event->viaRegistration === false
        );
    }

    public function test_registration_normalizes_email_before_unique_validation(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->postJson('/register', [
            'name' => 'Case Variant',
            'email' => 'ALICE@example.com',
            'password' => 'correct-password',
            'password_confirmation' => 'correct-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest('web');
    }

    public function test_social_redirect_sets_login_intent_and_redirects_to_provider(): void
    {
        Socialite::fake('google');

        $response = $this->get('/auth/google/redirect');

        $response
            ->assertRedirect('https://socialite.fake/google/authorize')
            ->assertSessionHas('identity.socialite.intent', function (array $intent): bool {
                return ($intent['intent'] ?? null) === 'login'
                    && ($intent['provider'] ?? null) === 'google';
            });
    }

    public function test_social_callback_creates_user_and_social_account(): void
    {
        Event::fake([SocialAccountLinked::class, SocialLoginSucceeded::class]);
        Socialite::fake('google', $this->socialiteUser(
            providerUserId: 'google-user-123',
            email: 'social@example.com',
            name: 'Social User'
        ));

        $response = $this
            ->withSession($this->socialiteIntent('login', 'google'))
            ->getJson('/auth/google/callback?code=test-code&state=test-state');

        $response
            ->assertOk()
            ->assertJsonPath('provider', 'google')
            ->assertJsonPath('linking_flow', false)
            ->assertJsonPath('linked', true)
            ->assertJsonPath('created_user', true);

        $user = User::query()->where('email', 'social@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-user-123',
            'email' => 'social@example.com',
            'name' => 'Social User',
        ]);

        Event::assertDispatched(
            SocialAccountLinked::class,
            fn (SocialAccountLinked $event): bool => $event->user->is($user)
                && $event->provider->value === 'google'
                && $event->providerUserId === 'google-user-123'
        );
        Event::assertDispatched(
            SocialLoginSucceeded::class,
            fn (SocialLoginSucceeded $event): bool => $event->user->is($user)
                && $event->provider->value === 'google'
                && $event->linkedDuringLogin === true
        );
    }

    public function test_repeated_social_callback_reuses_existing_user(): void
    {
        Socialite::fake('google', $this->socialiteUser(
            providerUserId: 'same-google-user',
            email: 'repeat@example.com',
            name: 'Repeat User'
        ));

        $firstResponse = $this
            ->withSession($this->socialiteIntent('login', 'google'))
            ->getJson('/auth/google/callback?code=first-code&state=first-state');

        $firstResponse
            ->assertOk()
            ->assertJsonPath('created_user', true);

        Socialite::fake('google', $this->socialiteUser(
            providerUserId: 'same-google-user',
            email: 'updated-repeat@example.com',
            name: 'Repeat User Updated'
        ));

        $secondResponse = $this
            ->withSession($this->socialiteIntent('login', 'google'))
            ->getJson('/auth/google/callback?code=second-code&state=second-state');

        $secondResponse
            ->assertOk()
            ->assertJsonPath('user_id', $firstResponse->json('user_id'))
            ->assertJsonPath('created_user', false)
            ->assertJsonPath('linked', false);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'same-google-user',
            'email' => 'updated-repeat@example.com',
            'name' => 'Repeat User Updated',
        ]);
    }

    public function test_authenticated_user_can_link_and_unlink_social_account(): void
    {
        Event::fake([SocialAccountLinked::class, SocialAccountUnlinked::class]);

        $user = User::factory()->create();

        Socialite::fake('google', $this->socialiteUser(
            providerUserId: 'linked-google-user',
            email: 'linked@example.com',
            name: 'Linked User'
        ));

        $redirectResponse = $this
            ->actingAs($user)
            ->get('/auth/google/link');

        $redirectResponse
            ->assertRedirect('https://socialite.fake/google/authorize')
            ->assertSessionHas('identity.socialite.intent', function (array $intent) use ($user): bool {
                return ($intent['intent'] ?? null) === 'link'
                    && ($intent['provider'] ?? null) === 'google'
                    && ($intent['user_id'] ?? null) === $user->id;
            });

        $callbackResponse = $this
            ->actingAs($user)
            ->withSession($this->socialiteIntent('link', 'google', $user))
            ->getJson('/auth/google/callback?code=link-code&state=link-state');

        $callbackResponse
            ->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('linking_flow', true)
            ->assertJsonPath('linked', true)
            ->assertJsonPath('created_user', false);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'linked-google-user',
        ]);
        Event::assertDispatched(
            SocialAccountLinked::class,
            fn (SocialAccountLinked $event): bool => $event->user->is($user)
                && $event->provider->value === 'google'
                && $event->providerUserId === 'linked-google-user'
        );

        $unlinkResponse = $this
            ->actingAs($user)
            ->deleteJson('/auth/google/link');

        $unlinkResponse
            ->assertOk()
            ->assertJsonPath('provider', 'google')
            ->assertJsonPath('unlinked', true);

        $this->assertDatabaseMissing('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
        ]);
        Event::assertDispatched(
            SocialAccountUnlinked::class,
            fn (SocialAccountUnlinked $event): bool => $event->user->is($user)
                && $event->provider->value === 'google'
        );
    }

    public function test_social_link_callback_requires_active_authenticated_session(): void
    {
        $user = User::factory()->create();

        Socialite::fake('google', $this->socialiteUser(
            providerUserId: 'stale-link-google-user',
            email: 'stale-link@example.com',
            name: 'Stale Link User'
        ));

        $response = $this
            ->withSession($this->socialiteIntent('link', 'google', $user))
            ->getJson('/auth/google/callback?code=stale-link-code&state=stale-link-state');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('error', 'linking_auth_required')
            ->assertJsonStructure(['error', 'message', 'request_id']);

        $this->assertDatabaseMissing('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'stale-link-google-user',
        ]);
    }

    public function test_social_link_callback_requires_authenticated_user_to_match_linking_intent(): void
    {
        $linkingUser = User::factory()->create();
        $otherUser = User::factory()->create();

        Socialite::fake('google', $this->socialiteUser(
            providerUserId: 'mismatched-link-google-user',
            email: 'mismatched-link@example.com',
            name: 'Mismatched Link User'
        ));

        $response = $this
            ->actingAs($otherUser)
            ->withSession($this->socialiteIntent('link', 'google', $linkingUser))
            ->getJson('/auth/google/callback?code=mismatched-link-code&state=mismatched-link-state');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('error', 'linking_auth_required')
            ->assertJsonStructure(['error', 'message', 'request_id']);

        $this->assertDatabaseMissing('social_accounts', [
            'user_id' => $linkingUser->id,
            'provider' => 'google',
            'provider_user_id' => 'mismatched-link-google-user',
        ]);
        $this->assertDatabaseMissing('social_accounts', [
            'user_id' => $otherUser->id,
            'provider' => 'google',
            'provider_user_id' => 'mismatched-link-google-user',
        ]);
    }

    public function test_unsupported_social_provider_returns_deterministic_error(): void
    {
        $this
            ->getJson('/auth/unknown/redirect')
            ->assertNotFound()
            ->assertJsonPath('error', 'unsupported_provider')
            ->assertJsonStructure(['error', 'message', 'request_id']);
    }

    public function test_social_callback_encrypts_provider_tokens_and_does_not_log_them(): void
    {
        $accessToken = 'secret-access-token-123';
        $refreshToken = 'secret-refresh-token-456';

        $this->expectNoSecretLogs($accessToken, $refreshToken);

        Socialite::fake('google', $this->socialiteUser(
            providerUserId: 'token-google-user',
            email: 'token@example.com',
            name: 'Token User',
            accessToken: $accessToken,
            refreshToken: $refreshToken,
        ));

        $response = $this
            ->withSession($this->socialiteIntent('login', 'google'))
            ->getJson('/auth/google/callback?code=token-code&state=token-state');

        $response->assertOk();

        $socialAccount = SocialAccount::query()->firstOrFail();

        $this->assertNotSame($accessToken, $socialAccount->access_token_encrypted);
        $this->assertNotSame($refreshToken, $socialAccount->refresh_token_encrypted);
        $this->assertSame($accessToken, Crypt::decryptString((string) $socialAccount->access_token_encrypted));
        $this->assertSame($refreshToken, Crypt::decryptString((string) $socialAccount->refresh_token_encrypted));

        $this->addToAssertionCount(1);
    }

    public function test_social_callback_rejects_overlong_provider_user_id(): void
    {
        $overlongProviderUserId = str_repeat('a', 192);

        Socialite::fake('google', $this->socialiteUser(
            providerUserId: $overlongProviderUserId,
            email: 'overlong-provider-id@example.com',
            name: 'Overlong Provider Id'
        ));

        $response = $this
            ->withSession($this->socialiteIntent('login', 'google'))
            ->getJson('/auth/google/callback?code=overlong-id-code&state=overlong-id-state');

        $response
            ->assertUnprocessable()
            ->assertJsonPath('error', 'invalid_provider_payload')
            ->assertJsonStructure(['error', 'message', 'request_id']);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('social_accounts', 0);
    }

    /**
     * @return array{intent: string, provider: string, created_at: string, user_id?: int}
     */
    private function socialiteIntent(string $intent, string $provider, ?User $user = null): array
    {
        $payload = [
            'intent' => $intent,
            'provider' => $provider,
            'created_at' => now()->toIso8601String(),
        ];

        if ($user !== null) {
            $payload['user_id'] = $user->id;
        }

        return ['identity.socialite.intent' => $payload];
    }

    private function socialiteUser(
        string $providerUserId,
        ?string $email,
        ?string $name,
        string $accessToken = 'social-access-token',
        string $refreshToken = 'social-refresh-token',
    ): SocialiteUser {
        return (new SocialiteUser)
            ->setRaw([
                'id' => $providerUserId,
                'email' => $email,
                'name' => $name,
            ])
            ->map([
                'id' => $providerUserId,
                'email' => $email,
                'name' => $name,
                'avatar' => 'https://example.com/avatar.png',
            ])
            ->setToken($accessToken)
            ->setRefreshToken($refreshToken)
            ->setExpiresIn(3600);
    }

    private function expectNoSecretLogs(string ...$secrets): void
    {
        Log::shouldReceive('shareContext')->zeroOrMoreTimes();

        foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'] as $level) {
            Log::shouldReceive($level)
                ->zeroOrMoreTimes()
                ->withArgs(fn (...$arguments): bool => ! $this->argumentsContainSecret($arguments, ...$secrets));
        }
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    private function argumentsContainSecret(array $arguments, string ...$secrets): bool
    {
        $encodedArguments = json_encode($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);

        if (! is_string($encodedArguments)) {
            return false;
        }

        foreach ($secrets as $secret) {
            if ($secret !== '' && str_contains($encodedArguments, $secret)) {
                return true;
            }
        }

        return false;
    }
}
