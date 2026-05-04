<?php

namespace Tests\Unit\Issuer;

use App\Domain\Issuer\Enums\SigningKeyStatus;
use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Services\JwksService;
use App\Domain\Issuer\Services\SigningKeyService;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Services\SiteIdFactory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SigningKeyRotationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_next_keeps_previous_active_public_jwk_and_newest_active_signs(): void
    {
        $service = $this->app->make(SigningKeyService::class);
        $first = $service->createActiveKey();
        $next = $service->prepareNextKey();

        $activated = $service->activateNextKey();

        $this->assertSame($next->id, $activated->id);
        $this->assertSame($next->id, $service->requireActiveKey()->id);
        $this->assertDatabaseHas('signing_keys', [
            'id' => $first->id,
            'status' => SigningKeyStatus::Active->value,
        ]);

        $kids = array_column($this->app->make(JwksService::class)->getPublicJwks()['keys'], 'kid');
        $this->assertContains($first->kid, $kids);
        $this->assertContains($next->kid, $kids);
    }

    public function test_retire_expired_keys_blocks_non_expiring_tokens_then_retires_old_active_key(): void
    {
        config([
            'issuer.user_api_token_ttl_seconds' => 1,
            'issuer.web_access_token_ttl_seconds' => 1,
            'issuer.id_token_ttl_seconds' => 1,
        ]);

        $service = $this->app->make(SigningKeyService::class);
        $old = $service->createActiveKey();
        $site = $this->createVerifiedSite(User::factory()->create());
        $nonExpiringToken = $this->createApiToken($site, $old->id, null);

        $service->prepareNextKey();
        $new = $service->activateNextKey();
        $new->forceFill(['activated_at' => now()->subMinutes(10)])->save();

        $blocked = $service->retireExpiredKeys();
        $this->assertSame(0, $blocked['retired_count']);
        $this->assertSame(1, $blocked['blocked_count']);
        $this->assertSame(SigningKeyStatus::Active->value, $old->refresh()->status);

        $nonExpiringToken->forceFill(['revoked_at' => now()])->save();

        $retired = $service->retireExpiredKeys();
        $this->assertSame(1, $retired['retired_count']);
        $this->assertSame(0, $retired['blocked_count']);
        $this->assertSame(SigningKeyStatus::Retired->value, $old->refresh()->status);
        $this->assertNotNull($old->retired_at);

        $kids = array_column($this->app->make(JwksService::class)->getPublicJwks()['keys'], 'kid');
        $this->assertNotContains($old->kid, $kids);
        $this->assertContains($new->kid, $kids);
    }

    public function test_force_retire_and_rollback_clear_jwks_cache_and_change_signing_key(): void
    {
        Cache::put('issuer:jwks:public', ['keys' => []], 120);

        $service = $this->app->make(SigningKeyService::class);
        $first = $service->createActiveKey();
        $service->prepareNextKey();
        $second = $service->activateNextKey();

        $rolledBack = $service->rollbackToPreviousActive();
        $this->assertSame($first->id, $rolledBack->id);
        $this->assertSame($first->id, $service->requireActiveKey()->id);
        $this->assertSame(SigningKeyStatus::Retired->value, $second->refresh()->status);
        $this->assertFalse(Cache::has('issuer:jwks:public'));

        Cache::put('issuer:jwks:public', ['keys' => []], 120);
        $retired = $service->forceRetireByKid($first->kid);

        $this->assertSame($first->id, $retired->id);
        $this->assertSame(SigningKeyStatus::Retired->value, $first->refresh()->status);
        $this->assertFalse(Cache::has('issuer:jwks:public'));
    }

    private function createVerifiedSite(User $owner): Site
    {
        return Site::query()->create([
            'id' => app(SiteIdFactory::class)->make(),
            'owner_user_id' => $owner->id,
            'display_name' => 'Example App',
            'domain' => 'example.test',
            'normalized_domain' => 'example.test',
            'verification_status' => SiteVerificationStatus::Verified->value,
            'verified_at' => now(),
        ]);
    }

    private function createApiToken(Site $site, int $signingKeyId, mixed $expiresAt): ApiToken
    {
        return ApiToken::query()->create([
            'user_id' => $site->owner_user_id,
            'site_id' => $site->id,
            'signing_key_id' => $signingKeyId,
            'audience' => $site->normalized_domain,
            'jti' => 'jti-'.Str::lower(Str::random(32)),
            'token_hash' => hash('sha256', Str::random(64)),
            'scopes' => ['orders.read'],
            'permissions' => ['orders.read'],
            'issued_at' => now(),
            'expires_at' => $expiresAt,
            'revoked_at' => null,
        ]);
    }
}
