<?php

namespace Tests\Feature;

use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Services\SigningKeyService;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteMode;
use App\Domain\Sites\Services\SiteIdFactory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Tests\TestCase;

class IssuerApiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_issue_user_api_token_for_verified_api_resource_site(): void
    {
        $owner = User::factory()->create();
        $site = $this->createSite(ownerId: $owner->id, verified: true, apiResourceMode: true);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $site->id,
                'scopes' => ['orders.read'],
                'permissions' => ['orders.read'],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('site_id', $site->id)
            ->assertJsonPath('aud', 'example.test')
            ->assertJsonPath('scope.0', 'orders.read')
            ->assertJsonPath('permissions.0', 'orders.read');

        $rawToken = (string) $response->json('token');
        $token = (new Parser(new JoseEncoder))->parse($rawToken);

        $this->assertSame('JWT', $token->headers()->get('typ'));
        $this->assertSame((string) $response->json('kid'), $token->headers()->get('kid'));
        $this->assertSame('user_api', $token->claims()->get('token_type'));
        $this->assertSame($site->id, $token->claims()->get('site_id'));
        $this->assertSame((string) $owner->id, $token->claims()->get('sub'));
        $this->assertSame((string) $response->json('jti'), $token->claims()->get('jti'));

        $audClaim = $token->claims()->get('aud');
        $audiences = is_array($audClaim) ? $audClaim : [$audClaim];
        $this->assertContains('example.test', $audiences);

        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'audience' => 'example.test',
            'jti' => $response->json('jti'),
            'token_hash' => hash('sha256', $rawToken),
        ]);

        /** @var ApiToken $apiToken */
        $apiToken = ApiToken::query()->where('jti', (string) $response->json('jti'))->firstOrFail();
        $this->assertSame($apiToken->id, $response->json('token_id'));
    }

    public function test_owner_can_issue_user_api_token_with_custom_expiration(): void
    {
        $owner = User::factory()->create();
        $site = $this->createSite(ownerId: $owner->id, verified: true, apiResourceMode: true);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $expiresAt = CarbonImmutable::now()->addDay()->seconds(0);

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $site->id,
                'scopes' => ['orders.read'],
                'permissions' => ['orders.read'],
                'expires_at' => $expiresAt->toISOString(),
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('expires_at', $expiresAt->toISOString());

        $token = (new Parser(new JoseEncoder))->parse((string) $response->json('token'));

        $this->assertSame($expiresAt->getTimestamp(), $token->claims()->get('exp')->getTimestamp());
        $this->assertDatabaseHas('api_tokens', [
            'jti' => $response->json('jti'),
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    public function test_owner_can_issue_non_expiring_user_api_token(): void
    {
        $owner = User::factory()->create();
        $site = $this->createSite(ownerId: $owner->id, verified: true, apiResourceMode: true);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $site->id,
                'scopes' => ['orders.read'],
                'permissions' => ['orders.read'],
                'does_not_expire' => true,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('expires_at', null);

        $token = (new Parser(new JoseEncoder))->parse((string) $response->json('token'));

        $this->assertFalse($token->claims()->has('exp'));
        $this->assertDatabaseHas('api_tokens', [
            'jti' => $response->json('jti'),
            'expires_at' => null,
        ]);
    }

    public function test_jwks_endpoint_returns_public_keys_and_no_cookies(): void
    {
        $signingKeyService = $this->app->make(SigningKeyService::class);
        $active = $signingKeyService->createActiveKey();
        $signingKeyService->prepareNextKey();

        $response = $this->getJson('/oauth/jwks.json');

        $response
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertJsonStructure([
                'keys' => [
                    '*' => ['kty', 'kid', 'alg', 'use', 'n', 'e'],
                ],
            ]);

        $kids = array_column($response->json('keys'), 'kid');
        $this->assertContains($active->kid, $kids);
    }

    public function test_revoke_is_idempotent_and_writes_db_denylist(): void
    {
        $owner = User::factory()->create();
        $site = $this->createSite(ownerId: $owner->id, verified: true, apiResourceMode: true);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $issueResponse = $this
            ->actingAs($owner)
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $site->id,
                'scopes' => ['orders.read'],
                'permissions' => ['orders.read'],
            ]);

        $issueResponse->assertCreated();

        $apiTokenId = (int) $issueResponse->json('token_id');

        $first = $this->actingAs($owner)->postJson('/api/v1/user/api-tokens/'.$apiTokenId.'/revoke');
        $second = $this->actingAs($owner)->postJson('/api/v1/user/api-tokens/'.$apiTokenId.'/revoke');

        $first->assertOk();
        $second->assertOk();

        $this->assertDatabaseHas('api_tokens', [
            'id' => $apiTokenId,
            'jti' => $issueResponse->json('jti'),
        ]);
        $this->assertDatabaseCount('revoked_jti', 1);
    }

    public function test_revoke_does_not_reveal_foreign_token_ids(): void
    {
        $owner = User::factory()->create();
        $foreignUser = User::factory()->create();
        $site = $this->createSite(ownerId: $owner->id, verified: true, apiResourceMode: true);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $issueResponse = $this
            ->actingAs($owner)
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $site->id,
                'scopes' => ['orders.read'],
                'permissions' => ['orders.read'],
            ]);

        $issueResponse->assertCreated();

        /** @var ApiToken $apiToken */
        $apiToken = ApiToken::query()->where('jti', (string) $issueResponse->json('jti'))->firstOrFail();

        $foreignResponse = $this
            ->actingAs($foreignUser)
            ->postJson('/api/v1/user/api-tokens/'.$apiToken->id.'/revoke');

        $missingResponse = $this
            ->actingAs($foreignUser)
            ->postJson('/api/v1/user/api-tokens/999999/revoke');

        $foreignResponse
            ->assertNotFound()
            ->assertJsonPath('error', 'token_not_found');
        $missingResponse
            ->assertNotFound()
            ->assertJsonPath('error', 'token_not_found');
        $this->postJson('/api/v1/user/api-tokens/not-a-number/revoke')
            ->assertNotFound();

        $this->assertNull($apiToken->refresh()->revoked_at);
    }

    public function test_issue_endpoint_fails_closed_for_auth_ownership_mode_and_scope(): void
    {
        $owner = User::factory()->create();
        $foreignUser = User::factory()->create();

        $verifiedApiSite = $this->createSite(
            ownerId: $owner->id,
            verified: true,
            apiResourceMode: true,
            domain: 'example.test',
        );
        $unverifiedSite = $this->createSite(
            ownerId: $owner->id,
            verified: false,
            apiResourceMode: false,
            domain: 'pending.example.test',
        );
        $verifiedWithoutMode = $this->createSite(
            ownerId: $owner->id,
            verified: true,
            apiResourceMode: false,
            domain: 'mode-missing.example.test',
        );

        $this->app->make(SigningKeyService::class)->createActiveKey();

        $this->postJson('/api/v1/user/api-tokens', [
            'site_id' => $verifiedApiSite->id,
        ])->assertUnauthorized();

        $this->actingAs($foreignUser)
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $verifiedApiSite->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('error', 'site_owner_mismatch');

        $this->actingAs($owner)
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $unverifiedSite->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('error', 'unverified_site_cannot_receive_production_credentials');

        $this->actingAs($owner)
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $verifiedWithoutMode->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('error', 'api_resource_mode_required');

        $this->actingAs($owner)
            ->postJson('/api/v1/user/api-tokens', [
                'site_id' => $verifiedApiSite->id,
                'scopes' => ['unknown.scope'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_scope');
    }

    public function test_issue_endpoint_returns_deterministic_validation_error_shape(): void
    {
        $owner = User::factory()->create();

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/v1/user/api-tokens', [
                'scopes' => ['orders.read'],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure([
                'error',
                'message',
                'request_id',
                'fields',
            ]);
    }

    private function createSite(int $ownerId, bool $verified, bool $apiResourceMode, string $domain = 'example.test'): Site
    {
        $site = Site::query()->create([
            'id' => app(SiteIdFactory::class)->make(),
            'owner_user_id' => $ownerId,
            'display_name' => 'Example App',
            'domain' => $domain,
            'normalized_domain' => $domain,
            'verification_status' => $verified
                ? SiteVerificationStatus::Verified->value
                : SiteVerificationStatus::Pending->value,
            'verified_at' => $verified ? now() : null,
        ]);

        if ($apiResourceMode) {
            SiteMode::query()->create([
                'site_id' => $site->id,
                'mode' => SiteModeType::ApiResource->value,
                'enabled_at' => now(),
            ]);
        }

        return $site;
    }
}
