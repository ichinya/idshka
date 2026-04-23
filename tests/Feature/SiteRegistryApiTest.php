<?php

namespace Tests\Feature;

use App\Domain\Sites\Contracts\DnsTxtRecordLookup;
use App\Domain\Sites\Events\SiteModeEnabled;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteRegistryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication_to_create_site(): void
    {
        $response = $this->postJson('/api/v1/sites', [
            'domain' => 'apishka.ru',
            'display_name' => 'Apishka',
        ]);

        $response
            ->assertUnauthorized()
            ->assertHeader('X-Request-Id');
    }

    public function test_owner_can_create_site_and_receive_verification_instructions(): void
    {
        $owner = User::factory()->create();

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/v1/sites', [
                'domain' => 'https://Apishka.ru/path?query=1',
                'display_name' => 'Apishka',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('domain', 'apishka.ru')
            ->assertJsonPath('verified', false)
            ->assertJsonStructure([
                'site_id',
                'verification' => [
                    'dns_txt_name',
                    'dns_txt_value',
                    'file_url',
                    'file_body',
                    'expires_at',
                ],
            ]);
    }

    public function test_invalid_domain_returns_422(): void
    {
        $owner = User::factory()->create();

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/v1/sites', [
                'domain' => 'localhost',
                'display_name' => 'Localhost',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_domain');

        $this->assertDatabaseCount('sites', 0);
    }

    public function test_verified_domain_cannot_be_registered_by_another_owner(): void
    {
        $firstOwner = User::factory()->create();
        $secondOwner = User::factory()->create();

        $createResponse = $this
            ->actingAs($firstOwner)
            ->postJson('/api/v1/sites', [
                'domain' => 'apishka.ru',
            ]);

        $siteId = (string) $createResponse->json('site_id');

        Site::query()->whereKey($siteId)->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        $conflictResponse = $this
            ->actingAs($secondOwner)
            ->postJson('/api/v1/sites', [
                'domain' => 'apishka.ru',
            ]);

        $conflictResponse
            ->assertStatus(409)
            ->assertJsonPath('error', 'verified_domain_owned_by_another_user');
    }

    public function test_verify_endpoint_rejects_unsupported_method_with_422(): void
    {
        [$owner, $site] = $this->createSiteViaApi();

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/sites/{$site->id}/verify", [
                'method' => 'invalid',
            ]);

        $response->assertStatus(422);
    }

    public function test_dns_verification_marks_site_as_verified(): void
    {
        [$owner, $site] = $this->createSiteViaApi();
        $token = $this->verificationToken($site->id, 'dns_txt');

        $this->app->instance(DnsTxtRecordLookup::class, new class($token) implements DnsTxtRecordLookup
        {
            public function __construct(private readonly string $token) {}

            public function getTxtRecords(string $host): array
            {
                return ['idshka-site-verification='.$this->token];
            }
        });

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/sites/{$site->id}/verify", [
                'method' => 'dns_txt',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'verified')
            ->assertJsonPath('verified', true);
    }

    public function test_file_verification_marks_site_as_verified(): void
    {
        [$owner, $site] = $this->createSiteViaApi();
        $token = $this->verificationToken($site->id, 'file');

        Http::fake([
            'https://apishka.ru/.well-known/idshka-site-verification.txt' => Http::response($token, 200),
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/sites/{$site->id}/verify", [
                'method' => 'file',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'verified')
            ->assertJsonPath('verified', true);
    }

    public function test_expired_challenge_returns_expired_status(): void
    {
        [$owner, $site] = $this->createSiteViaApi();

        SiteVerification::query()
            ->where('site_id', $site->id)
            ->where('method', 'dns_txt')
            ->update(['expires_at' => now()->subMinute()]);

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/sites/{$site->id}/verify", [
                'method' => 'dns_txt',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'expired')
            ->assertJsonPath('error_code', 'verification_expired');
    }

    public function test_unverified_site_cannot_enable_modes(): void
    {
        [$owner, $site] = $this->createSiteViaApi();

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/sites/{$site->id}/modes/api_resource");

        $response
            ->assertForbidden()
            ->assertJsonPath('error', 'unverified_site_cannot_receive_production_credentials');
    }

    public function test_verified_site_can_enable_modes(): void
    {
        [$owner, $site] = $this->createSiteViaApi();
        $this->markVerified($site->id);

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/sites/{$site->id}/modes/web_client");

        $response
            ->assertOk()
            ->assertJsonPath('mode', 'web_client');
    }

    public function test_enable_mode_is_idempotent_and_dispatches_event_once(): void
    {
        Event::fake([SiteModeEnabled::class]);

        [$owner, $site] = $this->createSiteViaApi();
        $this->markVerified($site->id);

        $firstResponse = $this
            ->actingAs($owner)
            ->postJson("/api/v1/sites/{$site->id}/modes/api_resource");

        $secondResponse = $this
            ->actingAs($owner)
            ->postJson("/api/v1/sites/{$site->id}/modes/api_resource");

        $firstResponse
            ->assertOk()
            ->assertJsonPath('mode', 'api_resource');

        $secondResponse
            ->assertOk()
            ->assertJsonPath('mode', 'api_resource');

        $this->assertSame($firstResponse->json('enabled_at'), $secondResponse->json('enabled_at'));
        $this->assertDatabaseCount('site_modes', 1);
        Event::assertDispatchedTimes(SiteModeEnabled::class, 1);
    }

    public function test_foreign_owner_cannot_verify_or_enable_mode(): void
    {
        [$owner, $site] = $this->createSiteViaApi();
        $foreign = User::factory()->create();

        $verify = $this
            ->actingAs($foreign)
            ->postJson("/api/v1/sites/{$site->id}/verify", [
                'method' => 'dns_txt',
            ]);

        $mode = $this
            ->actingAs($foreign)
            ->postJson("/api/v1/sites/{$site->id}/modes/api_resource");

        $verify->assertForbidden();
        $mode->assertForbidden();
    }

    public function test_enable_mode_rejects_unsupported_mode(): void
    {
        [$owner, $site] = $this->createSiteViaApi();
        $this->markVerified($site->id);

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/sites/{$site->id}/modes/unknown_mode");

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', 'unsupported_mode');
    }

    /**
     * @return array{0: User, 1: Site}
     */
    private function createSiteViaApi(): array
    {
        $owner = User::factory()->create();

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/v1/sites', [
                'domain' => 'apishka.ru',
                'display_name' => 'Apishka',
            ]);

        $response->assertCreated();

        /** @var Site $site */
        $site = Site::query()->findOrFail((string) $response->json('site_id'));

        return [$owner, $site];
    }

    private function verificationToken(string $siteId, string $method): string
    {
        /** @var SiteVerification $verification */
        $verification = SiteVerification::query()
            ->where('site_id', $siteId)
            ->where('method', $method)
            ->latest('id')
            ->firstOrFail();

        return $verification->token;
    }

    private function markVerified(string $siteId): void
    {
        Site::query()->whereKey($siteId)->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);
    }
}
