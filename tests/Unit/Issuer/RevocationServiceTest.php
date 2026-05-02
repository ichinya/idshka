<?php

namespace Tests\Unit\Issuer;

use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Services\RevocationService;
use App\Domain\Issuer\Services\SigningKeyService;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Services\SiteIdFactory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class RevocationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_revoke_is_idempotent_and_persists_revoked_jti(): void
    {
        $owner = User::factory()->create();

        $site = Site::query()->create([
            'id' => $this->siteId(),
            'owner_user_id' => $owner->id,
            'display_name' => 'Example App',
            'domain' => 'example.test',
            'normalized_domain' => 'example.test',
            'verification_status' => SiteVerificationStatus::Verified->value,
            'verified_at' => now(),
        ]);

        $activeKey = $this->app->make(SigningKeyService::class)->createActiveKey();

        $apiToken = ApiToken::query()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'signing_key_id' => $activeKey->id,
            'audience' => 'example.test',
            'jti' => 'jti-'.Str::lower(Str::random(32)),
            'token_hash' => hash('sha256', Str::random(64)),
            'scopes' => ['orders.read'],
            'permissions' => ['orders.read'],
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'revoked_at' => null,
        ]);

        $service = $this->app->make(RevocationService::class);
        $service->revokeForUser($owner->id, $apiToken);
        $service->revokeForUser($owner->id, $apiToken->refresh());

        $this->assertDatabaseHas('api_tokens', [
            'id' => $apiToken->id,
            'jti' => $apiToken->jti,
        ]);
        $this->assertDatabaseCount('revoked_jti', 1);
    }

    public function test_revoke_commits_when_denylist_cache_write_fails(): void
    {
        $owner = User::factory()->create();

        $site = Site::query()->create([
            'id' => $this->siteId(),
            'owner_user_id' => $owner->id,
            'display_name' => 'Example App',
            'domain' => 'example.test',
            'normalized_domain' => 'example.test',
            'verification_status' => SiteVerificationStatus::Verified->value,
            'verified_at' => now(),
        ]);

        $activeKey = $this->app->make(SigningKeyService::class)->createActiveKey();

        $apiToken = ApiToken::query()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'signing_key_id' => $activeKey->id,
            'audience' => 'example.test',
            'jti' => 'jti-'.Str::lower(Str::random(32)),
            'token_hash' => hash('sha256', Str::random(64)),
            'scopes' => ['orders.read'],
            'permissions' => ['orders.read'],
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'revoked_at' => null,
        ]);

        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new RuntimeException('redis unavailable'));

        $revokedToken = $this->app->make(RevocationService::class)->revokeForUser($owner->id, $apiToken);

        $this->assertTrue($revokedToken->isRevoked());
        $this->assertDatabaseHas('revoked_jti', [
            'jti' => $apiToken->jti,
        ]);
    }

    public function test_revoke_non_expiring_token_persists_permanent_revoked_jti(): void
    {
        $owner = User::factory()->create();

        $site = Site::query()->create([
            'id' => $this->siteId(),
            'owner_user_id' => $owner->id,
            'display_name' => 'Example App',
            'domain' => 'example.test',
            'normalized_domain' => 'example.test',
            'verification_status' => SiteVerificationStatus::Verified->value,
            'verified_at' => now(),
        ]);

        $activeKey = $this->app->make(SigningKeyService::class)->createActiveKey();

        $apiToken = ApiToken::query()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'signing_key_id' => $activeKey->id,
            'audience' => 'example.test',
            'jti' => 'jti-'.Str::lower(Str::random(32)),
            'token_hash' => hash('sha256', Str::random(64)),
            'scopes' => ['orders.read'],
            'permissions' => ['orders.read'],
            'issued_at' => now(),
            'expires_at' => null,
            'revoked_at' => null,
        ]);

        Cache::shouldReceive('forever')
            ->once()
            ->withArgs(fn (string $key, bool $value): bool => str_ends_with($key, $apiToken->jti) && $value);

        $revokedToken = $this->app->make(RevocationService::class)->revokeForUser($owner->id, $apiToken);

        $this->assertTrue($revokedToken->isRevoked());
        $this->assertDatabaseHas('revoked_jti', [
            'jti' => $apiToken->jti,
            'expires_at' => null,
        ]);
    }

    private function siteId(): string
    {
        return app(SiteIdFactory::class)->make();
    }
}
