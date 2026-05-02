<?php

namespace Tests\Feature;

use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Services\SigningKeyService;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteMode;
use App\Domain\Sites\Models\SiteVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_sites_tokens_clients_redirects_and_audit_from_portal(): void
    {
        $owner = User::factory()->create();
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $dashboard = $this->actingAs($owner)->get('/portal');

        $dashboard
            ->assertOk()
            ->assertSee('Мои сайты')
            ->assertSee('API tokens')
            ->assertSee('Web clients')
            ->assertSee('Audit');

        $createSite = $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post('/portal/sites', [
                'domain' => 'https://Example.test/path?query=1',
                'display_name' => 'Example App',
            ]);

        /** @var Site $site */
        $site = Site::query()->where('owner_user_id', $owner->id)->firstOrFail();
        /** @var SiteVerification $dnsVerification */
        $dnsVerification = SiteVerification::query()
            ->where('site_id', $site->id)
            ->where('method', 'dns_txt')
            ->firstOrFail();

        $createSite
            ->assertOk()
            ->assertSee('example.test')
            ->assertSee('_idshka.example.test')
            ->assertSee('idshka-site-verification='.$dnsVerification->token);

        $site->update([
            'verification_status' => SiteVerificationStatus::Verified->value,
            'verified_at' => now(),
        ]);

        $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post("/portal/sites/{$site->id}/modes/".SiteModeType::ApiResource->value)
            ->assertOk()
            ->assertSee('API resource enabled');

        $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post("/portal/sites/{$site->id}/modes/".SiteModeType::WebClient->value)
            ->assertOk()
            ->assertSee('Web client enabled');

        $tokenResponse = $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post('/portal/api-tokens', [
                'site_id' => $site->id,
                'scopes' => 'orders.read',
                'permissions' => 'orders.read',
            ]);

        $tokenResponse
            ->assertOk()
            ->assertSee('Токен создан');

        $tokenHtml = $tokenResponse->getContent();
        $this->assertIsString($tokenHtml);
        $this->assertMatchesRegularExpression('/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', $tokenHtml);
        preg_match('/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', $tokenHtml, $tokenMatches);
        $rawToken = $tokenMatches[0];

        $this
            ->actingAs($owner)
            ->get('/portal')
            ->assertOk()
            ->assertDontSee($rawToken, false);

        $clientResponse = $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post('/portal/clients', [
                'site_id' => $site->id,
                'name' => 'Example Web',
                'redirect_uri' => 'https://example.test/auth/idshka/callback',
            ]);

        $clientResponse
            ->assertOk()
            ->assertSee('Client secret')
            ->assertSee('https://example.test/auth/idshka/callback');

        $clientHtml = $clientResponse->getContent();
        $this->assertIsString($clientHtml);
        $this->assertMatchesRegularExpression('/secret_[A-Za-z0-9]+/', $clientHtml);
        preg_match('/secret_[A-Za-z0-9]+/', $clientHtml, $secretMatches);
        $rawSecret = $secretMatches[0];

        /** @var OidcClient $client */
        $client = OidcClient::query()->where('owner_user_id', $owner->id)->firstOrFail();

        $this
            ->actingAs($owner)
            ->get('/portal')
            ->assertOk()
            ->assertSee($client->client_id)
            ->assertDontSee($rawSecret, false);

        $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post("/portal/clients/{$client->id}/redirect-uris", [
                'redirect_uri' => 'https://example.test/auth/idshka/tenant/callback',
            ])
            ->assertOk()
            ->assertSee('Redirect URI added')
            ->assertSee('https://example.test/auth/idshka/tenant/callback');

        $auditPage = $this->actingAs($owner)->get('/portal');

        $auditPage
            ->assertOk()
            ->assertSee('site.connected')
            ->assertSee('site.mode_enabled')
            ->assertSee('issuer.user_api_token_issued')
            ->assertSee('oidc.client_created')
            ->assertSee('oidc.redirect_uri_added');
    }

    public function test_portal_danger_actions_require_confirmation(): void
    {
        $owner = User::factory()->create();
        $this->app->make(SigningKeyService::class)->createActiveKey();
        $site = $this->createVerifiedSite($owner);

        $this
            ->actingAs($owner)
            ->post('/portal/api-tokens', [
                'site_id' => $site->id,
                'scopes' => 'orders.read',
                'permissions' => 'orders.read',
            ]);

        /** @var ApiToken $apiToken */
        $apiToken = ApiToken::query()->where('user_id', $owner->id)->firstOrFail();

        $this
            ->actingAs($owner)
            ->post("/portal/api-tokens/{$apiToken->id}/revoke")
            ->assertSessionHasErrors('confirm');

        $this->assertNull($apiToken->refresh()->revoked_at);

        $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post("/portal/api-tokens/{$apiToken->id}/revoke", [
                'confirm' => 'revoke',
            ])
            ->assertOk()
            ->assertSee('Token revoked');

        $this->assertNotNull($apiToken->refresh()->revoked_at);

        $this
            ->actingAs($owner)
            ->post('/portal/clients', [
                'site_id' => $site->id,
                'name' => 'Example Web',
                'redirect_uri' => 'https://example.test/auth/idshka/callback',
            ]);

        /** @var OidcClient $client */
        $client = OidcClient::query()->where('owner_user_id', $owner->id)->firstOrFail();

        $this
            ->actingAs($owner)
            ->post("/portal/clients/{$client->id}/revoke")
            ->assertSessionHasErrors('confirm');

        $this->assertNull($client->refresh()->revoked_at);

        $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post("/portal/clients/{$client->id}/revoke", [
                'confirm' => 'revoke',
            ])
            ->assertOk()
            ->assertSee('Client revoked');

        $this->assertNotNull($client->refresh()->revoked_at);
    }

    public function test_portal_web_client_form_uses_owner_site_for_default_redirect_uri(): void
    {
        $owner = User::factory()->create();
        $this->createVerifiedSite($owner, 'chat.ie0.ru', 'Chat');

        $this
            ->actingAs($owner)
            ->get('/portal')
            ->assertOk()
            ->assertSee('value="https://chat.ie0.ru/auth/idshka/callback"', false)
            ->assertSee('placeholder="https://chat.ie0.ru/auth/idshka/callback"', false)
            ->assertDontSee('https://example.test/auth/idshka/callback');
    }

    public function test_portal_api_token_form_lists_only_api_resource_sites(): void
    {
        $owner = User::factory()->create();
        $apiResourceSite = $this->createVerifiedSiteWithModes(
            owner: $owner,
            domain: 'chat.ie0.ru',
            displayName: 'APIshka',
            modes: [SiteModeType::ApiResource],
        );
        $webClientOnlySite = $this->createVerifiedSiteWithModes(
            owner: $owner,
            domain: 'localhost',
            displayName: 'Local Web',
            modes: [SiteModeType::WebClient],
        );

        $response = $this
            ->actingAs($owner)
            ->get('/portal');

        $response
            ->assertOk()
            ->assertSee('API tokens');

        $html = $response->getContent();
        $this->assertIsString($html);

        $apiTokenHeading = strpos($html, 'API tokens');
        $webClientHeading = strpos($html, 'Web clients');

        $this->assertIsInt($apiTokenHeading);
        $this->assertIsInt($webClientHeading);

        $apiTokenSection = substr($html, $apiTokenHeading, $webClientHeading - $apiTokenHeading);

        $this->assertStringContainsString('value="'.$apiResourceSite->id.'"', $apiTokenSection);
        $this->assertStringContainsString('chat.ie0.ru', $apiTokenSection);
        $this->assertStringNotContainsString('value="'.$webClientOnlySite->id.'"', $apiTokenSection);
    }

    public function test_portal_can_issue_api_token_with_custom_or_no_expiration(): void
    {
        $owner = User::factory()->create();
        $site = $this->createVerifiedSiteWithModes(
            owner: $owner,
            domain: 'chat.ie0.ru',
            displayName: 'APIshka',
            modes: [SiteModeType::ApiResource],
        );
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $customExpiration = now()->addDay()->setSecond(0)->setMicrosecond(0);

        $customResponse = $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post('/portal/api-tokens', [
                'site_id' => $site->id,
                'scopes' => 'orders.read',
                'permissions' => 'orders.read',
                'expires_mode' => 'at',
                'expires_at' => $customExpiration->format('Y-m-d\TH:i'),
            ]);

        $customResponse
            ->assertOk()
            ->assertSee('Токен создан')
            ->assertSee($customExpiration->toISOString());

        $this->assertDatabaseHas('api_tokens', [
            'site_id' => $site->id,
            'expires_at' => $customExpiration->toDateTimeString(),
        ]);

        $neverResponse = $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post('/portal/api-tokens', [
                'site_id' => $site->id,
                'scopes' => 'orders.read',
                'permissions' => 'orders.read',
                'expires_mode' => 'never',
            ]);

        $neverResponse
            ->assertOk()
            ->assertSee('Токен создан')
            ->assertSee('Never');

        $this->assertDatabaseHas('api_tokens', [
            'site_id' => $site->id,
            'expires_at' => null,
        ]);
    }

    public function test_portal_can_register_loopback_web_client_when_local_loopback_sites_are_enabled(): void
    {
        config(['sites.allow_loopback_domains' => true]);

        $owner = User::factory()->create();

        $createSite = $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post('/portal/sites', [
                'domain' => 'https://localhost:8443',
                'display_name' => 'Local HTTPS Client',
            ]);

        /** @var Site $site */
        $site = Site::query()->where('owner_user_id', $owner->id)->firstOrFail();

        $createSite
            ->assertOk()
            ->assertSee('localhost');

        $this->assertSame('localhost', $site->normalized_domain);

        $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post("/portal/sites/{$site->id}/verify", [
                'method' => 'file',
            ])
            ->assertOk()
            ->assertSee('Verification checked: verified');

        $this->assertSame(SiteVerificationStatus::Verified->value, $site->refresh()->verification_status);

        $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post("/portal/sites/{$site->id}/modes/".SiteModeType::WebClient->value)
            ->assertOk()
            ->assertSee('Web client enabled');

        $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post('/portal/clients', [
                'site_id' => $site->id,
                'name' => 'Local Demo Client',
                'redirect_uri' => 'https://localhost:8443/auth/idshka/callback',
            ])
            ->assertOk()
            ->assertSee('Client secret')
            ->assertSee('https://localhost:8443/auth/idshka/callback');
    }

    public function test_expired_site_verification_shows_fresh_retry_instructions(): void
    {
        $owner = User::factory()->create();
        $site = $this->createPendingSite($owner);

        /** @var SiteVerification $oldFileVerification */
        $oldFileVerification = SiteVerification::query()
            ->where('site_id', $site->id)
            ->where('method', 'file')
            ->firstOrFail();

        SiteVerification::query()
            ->where('site_id', $site->id)
            ->update(['expires_at' => now()->subMinute()]);

        $response = $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post("/portal/sites/{$site->id}/verify", [
                'method' => 'file',
            ]);

        /** @var SiteVerification $freshFileVerification */
        $freshFileVerification = SiteVerification::query()
            ->where('site_id', $site->id)
            ->where('method', 'file')
            ->latest('id')
            ->firstOrFail();

        $response
            ->assertOk()
            ->assertSee('Verification checked: expired. New verification instructions generated.')
            ->assertDontSee($oldFileVerification->token, false)
            ->assertSee($freshFileVerification->token);
    }

    private function createPendingSite(User $owner): Site
    {
        /** @var Site $site */
        $site = Site::query()->create([
            'id' => 'site_'.strtolower((string) str()->ulid()),
            'owner_user_id' => $owner->id,
            'display_name' => 'Pending',
            'domain' => 'chat.ie0.ru',
            'normalized_domain' => 'chat.ie0.ru',
            'verification_status' => SiteVerificationStatus::Pending->value,
        ]);

        foreach (['dns_txt', 'file'] as $method) {
            SiteVerification::query()->create([
                'site_id' => $site->id,
                'method' => $method,
                'token' => 'old-token',
                'expires_at' => now()->addMinutes(30),
                'status' => SiteVerificationStatus::Pending->value,
            ]);
        }

        return $site;
    }

    private function createVerifiedSite(User $owner, string $domain = 'example.test', string $displayName = 'Example App'): Site
    {
        return $this->createVerifiedSiteWithModes($owner, $domain, $displayName, [
            SiteModeType::ApiResource,
            SiteModeType::WebClient,
        ]);
    }

    /**
     * @param  list<SiteModeType>  $modes
     */
    private function createVerifiedSiteWithModes(
        User $owner,
        string $domain = 'example.test',
        string $displayName = 'Example App',
        array $modes = [],
    ): Site {
        /** @var Site $site */
        $site = Site::query()->create([
            'id' => 'site_'.strtolower((string) str()->ulid()),
            'owner_user_id' => $owner->id,
            'display_name' => $displayName,
            'domain' => $domain,
            'normalized_domain' => $domain,
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
}
