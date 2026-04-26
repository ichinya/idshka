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
use Illuminate\Support\Str;
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
            'display_name' => 'Apishka',
            'domain' => 'apishka.ru',
            'normalized_domain' => 'apishka.ru',
            'verification_status' => SiteVerificationStatus::Verified->value,
            'verified_at' => now(),
        ]);

        $activeKey = $this->app->make(SigningKeyService::class)->createActiveKey();

        $apiToken = ApiToken::query()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'signing_key_id' => $activeKey->id,
            'audience' => 'apishka.ru',
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

    private function siteId(): string
    {
        return app(SiteIdFactory::class)->make();
    }
}
