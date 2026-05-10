<?php

namespace Tests\Feature;

use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Services\SigningKeyService;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Enums\SiteVerificationMethod;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteMode;
use App\Domain\Sites\Models\SiteVerification;
use App\Domain\Sites\Services\SiteIdFactory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

final class SecurityRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_token_rate_limit_uses_configured_limit_and_returns_safe_json(): void
    {
        config(['security.rate_limits.oauth_token.per_minute' => 1]);
        Log::spy();

        $payload = [
            'grant_type' => 'authorization_code',
            'client_id' => 'client_rate_limited',
            'client_secret' => 'secret_raw_oauth_rate_limit',
            'code' => 'code_raw_oauth_rate_limit',
            'redirect_uri' => 'https://example.test/auth/idshka/callback',
            'code_verifier' => str_repeat('A', 64),
        ];

        $this
            ->withHeader('X-Request-Id', 'oauth-token-rate-1')
            ->postJson('/oauth/token', $payload)
            ->assertStatus(401)
            ->assertJsonPath('error', 'invalid_client');

        $this
            ->withHeader('X-Request-Id', 'oauth-token-rate-2')
            ->postJson('/oauth/token', $payload)
            ->assertStatus(429)
            ->assertHeader('X-Request-Id', 'oauth-token-rate-2')
            ->assertJsonPath('error', 'rate_limited')
            ->assertJsonPath('request_id', 'oauth-token-rate-2');

        $this->assertDatabaseCount('oauth_authorization_codes', 0);
        $this->assertLogsDoNotContain([
            $payload['client_secret'],
            $payload['code'],
            $payload['code_verifier'],
        ]);
    }

    public function test_api_token_issue_rate_limit_blocks_second_token_before_state_changes(): void
    {
        config(['security.rate_limits.token_issue.per_minute' => 1]);
        Log::spy();

        $owner = User::factory()->create();
        $site = $this->createVerifiedSiteWithModes($owner, [SiteModeType::ApiResource]);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $first = $this
            ->actingAs($owner)
            ->withHeader('X-Request-Id', 'api-token-rate-1')
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $site->id,
                'scopes' => ['orders.read'],
                'permissions' => ['orders.read'],
            ]);

        $first
            ->assertCreated()
            ->assertJsonPath('site_id', $site->id);

        $rawJwt = (string) $first->json('token');
        $this->assertNotSame('', $rawJwt);

        $this
            ->actingAs($owner)
            ->withHeader('X-Request-Id', 'api-token-rate-2')
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $site->id,
                'scopes' => ['orders.write'],
                'permissions' => ['orders.write'],
            ])
            ->assertStatus(429)
            ->assertJsonPath('error', 'rate_limited')
            ->assertJsonPath('request_id', 'api-token-rate-2');

        $this->assertDatabaseCount('api_tokens', 1);
        $this->assertDatabaseMissing('api_tokens', [
            'scopes' => json_encode(['orders.write']),
        ]);
        $this->assertLogsDoNotContain([$rawJwt]);
    }

    public function test_portal_client_rate_limit_redirects_with_request_id_before_client_or_redirect_state_changes(): void
    {
        config(['security.rate_limits.portal_client_write.per_minute' => 1]);
        Log::spy();

        $owner = User::factory()->create();
        $site = $this->createVerifiedSiteWithModes($owner, [SiteModeType::WebClient]);

        $this
            ->actingAs($owner)
            ->from('/portal')
            ->withHeader('X-Request-Id', 'portal-client-rate-1')
            ->post('/portal/clients', [
                'site_id' => $site->id,
                'name' => 'Example Web',
                'redirect_uri' => 'https://example.test/auth/idshka/callback',
            ])
            ->assertRedirect("/portal/developer/sites/{$site->id}/credentials");

        /** @var OidcClient $client */
        $client = OidcClient::query()->where('owner_user_id', $owner->id)->firstOrFail();

        $this
            ->actingAs($owner)
            ->from('/portal')
            ->withHeader('X-Request-Id', 'portal-client-rate-2')
            ->post('/portal/clients', [
                'site_id' => $site->id,
                'name' => 'Second Web',
                'redirect_uri' => 'https://example.test/auth/idshka/second/callback',
            ])
            ->assertRedirect('/portal')
            ->assertSessionHasErrors('rate_limit')
            ->assertSessionHas('request_id', 'portal-client-rate-2');

        $this->assertDatabaseCount('oidc_clients', 1);
        $this->assertDatabaseCount('oidc_redirect_uris', 1);
        $this->assertDatabaseMissing('oidc_clients', [
            'name' => 'Second Web',
        ]);
        $this->assertDatabaseMissing('oidc_redirect_uris', [
            'redirect_uri' => 'https://example.test/auth/idshka/second/callback',
        ]);
        $this->assertLogsDoNotContain([
            'https://example.test/auth/idshka/second/callback',
            (string) $client->client_secret_hash,
        ]);
    }

    public function test_portal_verification_rate_limit_blocks_second_check_before_status_changes(): void
    {
        config([
            'security.rate_limits.portal_verification.per_minute' => 1,
            'sites.allow_loopback_domains' => true,
        ]);

        $owner = User::factory()->create();
        $firstSite = $this->createPendingSite($owner, 'localhost');
        $secondSite = $this->createPendingSite($owner, '127.0.0.2');

        $this
            ->actingAs($owner)
            ->from('/portal')
            ->withHeader('X-Request-Id', 'portal-verify-rate-1')
            ->post("/portal/sites/{$firstSite->id}/verify", [
                'method' => SiteVerificationMethod::File->value,
            ])
            ->assertRedirect("/portal/developer/sites/{$firstSite->id}/verification");

        $this->assertSame(SiteVerificationStatus::Verified->value, $firstSite->refresh()->verification_status);

        $this
            ->actingAs($owner)
            ->from('/portal')
            ->withHeader('X-Request-Id', 'portal-verify-rate-2')
            ->post("/portal/sites/{$secondSite->id}/verify", [
                'method' => SiteVerificationMethod::File->value,
            ])
            ->assertRedirect('/portal')
            ->assertSessionHasErrors('rate_limit')
            ->assertSessionHas('request_id', 'portal-verify-rate-2');

        $this->assertSame(SiteVerificationStatus::Pending->value, $secondSite->refresh()->verification_status);
        $this->assertDatabaseMissing('site_verifications', [
            'site_id' => $secondSite->id,
            'method' => SiteVerificationMethod::File->value,
            'status' => SiteVerificationStatus::Verified->value,
        ]);
    }

    public function test_portal_revoke_rate_limit_blocks_second_revoke_before_state_changes(): void
    {
        config(['security.rate_limits.portal_credential_revoke.per_minute' => 1]);

        $owner = User::factory()->create();
        $site = $this->createVerifiedSiteWithModes($owner, [SiteModeType::ApiResource]);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $this
            ->actingAs($owner)
            ->post('/portal/api-tokens', [
                'site_id' => $site->id,
                'scopes' => 'orders.read',
                'permissions' => 'orders.read',
            ]);

        $this
            ->actingAs($owner)
            ->post('/portal/api-tokens', [
                'site_id' => $site->id,
                'scopes' => 'orders.write',
                'permissions' => 'orders.write',
            ]);

        $tokens = ApiToken::query()->where('user_id', $owner->id)->orderBy('id')->get();
        $this->assertCount(2, $tokens);

        $this
            ->actingAs($owner)
            ->from('/portal')
            ->withHeader('X-Request-Id', 'portal-revoke-rate-1')
            ->post("/portal/api-tokens/{$tokens[0]->id}/revoke", [
                'confirm' => 'revoke',
            ])
            ->assertRedirect('/portal/account/tokens');

        $this->assertNotNull($tokens[0]->refresh()->revoked_at);

        $this
            ->actingAs($owner)
            ->from('/portal')
            ->withHeader('X-Request-Id', 'portal-revoke-rate-2')
            ->post("/portal/api-tokens/{$tokens[1]->id}/revoke", [
                'confirm' => 'revoke',
            ])
            ->assertRedirect('/portal')
            ->assertSessionHasErrors('rate_limit')
            ->assertSessionHas('request_id', 'portal-revoke-rate-2');

        $this->assertNull($tokens[1]->refresh()->revoked_at);
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

    private function createPendingSite(User $owner, string $domain): Site
    {
        $site = Site::query()->create([
            'id' => app(SiteIdFactory::class)->make(),
            'owner_user_id' => $owner->id,
            'display_name' => 'Pending '.$domain,
            'domain' => $domain,
            'normalized_domain' => $domain,
            'verification_status' => SiteVerificationStatus::Pending->value,
        ]);

        foreach (SiteVerificationMethod::cases() as $method) {
            SiteVerification::query()->create([
                'site_id' => $site->id,
                'method' => $method->value,
                'token' => 'token_'.$method->value.'_'.$site->id,
                'expires_at' => now()->addMinutes(30),
                'status' => SiteVerificationStatus::Pending->value,
            ]);
        }

        return $site;
    }

    /**
     * @param  list<string>  $needles
     */
    private function assertLogsDoNotContain(array $needles): void
    {
        foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'] as $level) {
            Log::shouldNotHaveReceived($level, [
                Mockery::on(fn (mixed $message): bool => $this->containsAnyNeedle($message, $needles)),
            ]);

            Log::shouldNotHaveReceived($level, [
                Mockery::on(fn (mixed $message): bool => $this->containsAnyNeedle($message, $needles)),
                Mockery::any(),
            ]);

            Log::shouldNotHaveReceived($level, [
                Mockery::any(),
                Mockery::on(fn (mixed $context): bool => $this->containsAnyNeedle($context, $needles)),
            ]);
        }
    }

    /**
     * @param  list<string>  $needles
     */
    private function containsAnyNeedle(mixed $value, array $needles): bool
    {
        $serialized = is_string($value) ? $value : json_encode($value);

        if (! is_string($serialized)) {
            return false;
        }

        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($serialized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
