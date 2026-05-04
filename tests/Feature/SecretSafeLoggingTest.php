<?php

namespace Tests\Feature;

use App\Domain\Issuer\Services\AuthorizationCodeService;
use App\Domain\Issuer\Services\PkceService;
use App\Domain\Issuer\Services\SigningKeyService;
use App\Domain\OidcClients\DTO\ResolvedOidcClient;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteMode;
use App\Domain\Sites\Services\SiteIdFactory;
use App\Domain\Identity\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

final class SecretSafeLoggingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, array{level: string, message: string, context: array<string, mixed>}>
     */
    private array $capturedLogs = [];

    public function test_user_api_token_issue_logs_request_id_without_raw_jwt_or_private_key(): void
    {
        $owner = User::factory()->create();
        $site = $this->createVerifiedSiteWithModes($owner, [SiteModeType::ApiResource]);
        $signingKey = $this->app->make(SigningKeyService::class)->createActiveKey();
        $privatePem = Crypt::decryptString($signingKey->private_key_encrypted);
        $this->captureLogs();

        $response = $this
            ->actingAs($owner)
            ->withHeader('X-Request-Id', 'safe-log-api-token')
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $site->id,
                'scopes' => ['orders.read'],
                'permissions' => ['orders.read'],
            ]);

        $response->assertCreated();

        $this->assertLogsCarryRequestId('safe-log-api-token');
        $this->assertLogsDoNotContain([
            (string) $response->json('token'),
            $privatePem,
        ]);
    }

    public function test_oauth_token_exchange_logs_request_id_without_raw_credentials_or_tokens(): void
    {
        $owner = User::factory()->create();
        $site = $this->createVerifiedSiteWithModes($owner, [SiteModeType::WebClient]);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $rawClientSecret = 'secret_safe_log_oauth_client';
        $client = $this->createClient($owner, $site, $rawClientSecret);
        $redirect = $this->addRedirectUri($client, 'https://example.test/auth/idshka/callback');
        $codeVerifier = str_repeat('A', 64);
        $issuedCode = $this->app->make(AuthorizationCodeService::class)->issue(
            resolvedClient: new ResolvedOidcClient($client, $site, $redirect),
            user: $owner,
            scopes: ['openid', 'profile'],
            nonce: 'safe-log-nonce',
            codeChallenge: $this->s256Challenge($codeVerifier),
            codeChallengeMethod: 'S256',
        );
        $this->captureLogs();

        $response = $this
            ->withHeader('X-Request-Id', 'safe-log-oauth-token')
            ->postJson('/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $client->client_id,
                'client_secret' => $rawClientSecret,
                'code' => $issuedCode->rawCode,
                'redirect_uri' => $redirect->redirect_uri,
                'code_verifier' => $codeVerifier,
            ]);

        $response->assertOk();

        $this->assertLogsCarryRequestId('safe-log-oauth-token');
        $this->assertLogsDoNotContain([
            $rawClientSecret,
            $issuedCode->rawCode,
            $codeVerifier,
            (string) $response->json('access_token'),
            (string) $response->json('id_token'),
        ]);
    }

    public function test_socialite_callback_logs_request_id_without_provider_tokens(): void
    {
        $accessToken = 'secret-social-access-token';
        $refreshToken = 'secret-social-refresh-token';
        $this->captureLogs();

        Socialite::fake('google', $this->socialiteUser(
            providerUserId: 'safe-log-google-user',
            email: 'safe-log@example.com',
            name: 'Safe Log User',
            accessToken: $accessToken,
            refreshToken: $refreshToken,
        ));

        $response = $this
            ->withHeader('X-Request-Id', 'safe-log-socialite')
            ->withSession($this->socialiteIntent('login', 'google'))
            ->getJson('/auth/google/callback?code=social-code-secret&state=social-state');

        $response->assertOk();

        $socialAccount = SocialAccount::query()->firstOrFail();
        $this->assertSame($accessToken, Crypt::decryptString((string) $socialAccount->access_token_encrypted));
        $this->assertSame($refreshToken, Crypt::decryptString((string) $socialAccount->refresh_token_encrypted));

        $this->assertLogsCarryRequestId('safe-log-socialite');
        $this->assertLogsDoNotContain([
            $accessToken,
            $refreshToken,
            'social-code-secret',
        ]);
    }

    /**
     */
    private function captureLogs(): void
    {
        $this->capturedLogs = [];

        Log::listen(function (MessageLogged $event): void {
            $this->capturedLogs[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });
    }

    private function assertLogsCarryRequestId(string $requestId): void
    {
        $this->assertNotEmpty($this->capturedLogs);

        foreach ($this->capturedLogs as $log) {
            $this->assertSame($requestId, $log['context']['request_id'] ?? null, $log['message']);
        }
    }

    /**
     * @param  list<string>  $secrets
     */
    private function assertLogsDoNotContain(array $secrets): void
    {
        $encoded = json_encode($this->capturedLogs, JSON_PARTIAL_OUTPUT_ON_ERROR);
        $this->assertIsString($encoded);

        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $encoded);
        }
    }

    /**
     * @param  list<SiteModeType>  $modes
     */
    private function createVerifiedSiteWithModes(User $owner, array $modes): Site
    {
        $site = Site::query()->create([
            'id' => app(SiteIdFactory::class)->make(),
            'owner_user_id' => $owner->id,
            'display_name' => 'Example App',
            'domain' => 'example.test',
            'normalized_domain' => 'example.test',
            'verification_status' => SiteVerificationStatus::Verified->value,
            'verified_at' => now(),
        ]);

        foreach ($modes as $mode) {
            SiteMode::query()->create([
                'site_id' => $site->id,
                'mode' => $mode->value,
                'enabled_at' => now(),
            ]);
        }

        return $site;
    }

    private function createClient(User $owner, Site $site, string $rawClientSecret): OidcClient
    {
        return OidcClient::query()->create([
            'site_id' => $site->id,
            'owner_user_id' => $owner->id,
            'client_id' => 'client_'.strtolower((string) str()->ulid()),
            'client_secret_hash' => Hash::make($rawClientSecret),
            'name' => 'Example Web',
        ]);
    }

    private function addRedirectUri(OidcClient $client, string $redirectUri): OidcRedirectUri
    {
        return OidcRedirectUri::query()->create([
            'oidc_client_id' => $client->id,
            'redirect_uri' => $redirectUri,
            'redirect_uri_hash' => hash('sha256', $redirectUri),
        ]);
    }

    private function s256Challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * @return array{identity.socialite.intent: array{intent: string, provider: string, created_at: string}}
     */
    private function socialiteIntent(string $intent, string $provider): array
    {
        return [
            'identity.socialite.intent' => [
                'intent' => $intent,
                'provider' => $provider,
                'created_at' => now()->toIso8601String(),
            ],
        ];
    }

    private function socialiteUser(
        string $providerUserId,
        string $email,
        string $name,
        string $accessToken,
        string $refreshToken,
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
}
