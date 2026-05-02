<?php

namespace Tests\Unit\OidcClients;

use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use App\Domain\OidcClients\Services\OidcClientResolver;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteMode;
use App\Domain\Sites\Services\SiteIdFactory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OidcClientResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_requires_exact_redirect_uri_and_web_client_mode(): void
    {
        $owner = User::factory()->create();
        $site = $this->createSite($owner->id, verified: true, webClientMode: true);
        $client = OidcClient::query()->create([
            'site_id' => $site->id,
            'owner_user_id' => $owner->id,
            'client_id' => 'client_01kq000000000000000000000000000001',
            'client_secret_hash' => Hash::make('secret-value'),
            'name' => 'Example Web',
        ]);
        OidcRedirectUri::query()->create([
            'oidc_client_id' => $client->id,
            'redirect_uri' => 'https://example.test/auth/idshka/callback',
            'redirect_uri_hash' => hash('sha256', 'https://example.test/auth/idshka/callback'),
        ]);

        $resolved = app(OidcClientResolver::class)->resolveForAuthorize(
            clientId: 'client_01kq000000000000000000000000000001',
            redirectUri: 'https://example.test/auth/idshka/callback',
        );

        $this->assertSame($client->id, $resolved->client->id);
        $this->assertSame($site->id, $resolved->site->id);
        $this->assertSame('https://example.test/auth/idshka/callback', $resolved->redirectUri->redirect_uri);

        $this->expectExceptionMessage('redirect_uri_mismatch');

        app(OidcClientResolver::class)->resolveForAuthorize(
            clientId: 'client_01kq000000000000000000000000000001',
            redirectUri: 'https://evil.example.test/auth/idshka/callback',
        );
    }

    public function test_resolver_rejects_verified_site_without_web_client_mode(): void
    {
        $owner = User::factory()->create();
        $site = $this->createSite($owner->id, verified: true, webClientMode: false);
        OidcClient::query()->create([
            'site_id' => $site->id,
            'owner_user_id' => $owner->id,
            'client_id' => 'client_01kq000000000000000000000000000002',
            'client_secret_hash' => Hash::make('secret-value'),
            'name' => 'Example Web',
        ]);

        $this->expectExceptionMessage('web_client_mode_required');

        app(OidcClientResolver::class)->resolveForAuthorize(
            clientId: 'client_01kq000000000000000000000000000002',
            redirectUri: 'https://example.test/auth/idshka/callback',
        );
    }

    public function test_resolver_rejects_unverified_site(): void
    {
        $owner = User::factory()->create();
        $site = $this->createSite($owner->id, verified: false, webClientMode: true);
        OidcClient::query()->create([
            'site_id' => $site->id,
            'owner_user_id' => $owner->id,
            'client_id' => 'client_01kq000000000000000000000000000003',
            'client_secret_hash' => Hash::make('secret-value'),
            'name' => 'Example Web',
        ]);

        $this->expectExceptionMessage('unverified_site');

        app(OidcClientResolver::class)->resolveForAuthorize(
            clientId: 'client_01kq000000000000000000000000000003',
            redirectUri: 'https://example.test/auth/idshka/callback',
        );
    }

    public function test_resolver_rejects_revoked_client(): void
    {
        $owner = User::factory()->create();
        $site = $this->createSite($owner->id, verified: true, webClientMode: true);
        OidcClient::query()->create([
            'site_id' => $site->id,
            'owner_user_id' => $owner->id,
            'client_id' => 'client_01kq000000000000000000000000000004',
            'client_secret_hash' => Hash::make('secret-value'),
            'name' => 'Example Web',
            'revoked_at' => now(),
        ]);

        $this->expectExceptionMessage('invalid_client');

        app(OidcClientResolver::class)->resolveForAuthorize(
            clientId: 'client_01kq000000000000000000000000000004',
            redirectUri: 'https://example.test/auth/idshka/callback',
        );
    }

    public function test_resolver_verifies_hashed_client_secret(): void
    {
        $owner = User::factory()->create();
        $site = $this->createSite($owner->id, verified: true, webClientMode: true);
        $client = OidcClient::query()->create([
            'site_id' => $site->id,
            'owner_user_id' => $owner->id,
            'client_id' => 'client_01kq000000000000000000000000000005',
            'client_secret_hash' => Hash::make('secret-value'),
            'name' => 'Example Web',
        ]);

        $resolver = app(OidcClientResolver::class);

        $this->assertTrue($resolver->verifyClientSecret($client, 'secret-value'));
        $this->assertFalse($resolver->verifyClientSecret($client, 'wrong-secret'));
    }

    private function createSite(int $ownerId, bool $verified, bool $webClientMode): Site
    {
        $site = Site::query()->create([
            'id' => app(SiteIdFactory::class)->make(),
            'owner_user_id' => $ownerId,
            'display_name' => 'Example App',
            'domain' => 'example.test',
            'normalized_domain' => 'example.test',
            'verification_status' => $verified
                ? SiteVerificationStatus::Verified->value
                : SiteVerificationStatus::Pending->value,
            'verified_at' => $verified ? now() : null,
        ]);

        if ($webClientMode) {
            SiteMode::query()->create([
                'site_id' => $site->id,
                'mode' => SiteModeType::WebClient->value,
                'enabled_at' => now(),
            ]);
        }

        return $site;
    }
}
