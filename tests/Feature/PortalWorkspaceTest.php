<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Services\SigningKeyService;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteMode;
use App\Domain\Sites\Models\SiteVerification;
use App\Http\Controllers\Portal\Account\SessionController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PortalWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_root_redirects_to_account_workspace(): void
    {
        $owner = User::factory()->create();

        $this
            ->actingAs($owner)
            ->get('/portal')
            ->assertRedirect('/portal/account');

        $this
            ->actingAs($owner)
            ->get('/portal/account')
            ->assertOk()
            ->assertSee('Account')
            ->assertSee('Developer')
            ->assertSee('Audit')
            ->assertSee($owner->email);
    }

    public function test_account_developer_and_audit_workspaces_render_owned_data(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $foreign = User::factory()->create(['email' => 'foreign@example.com']);
        $ownedSite = $this->createVerifiedSiteWithModes($owner, 'apishka.ru', [SiteModeType::ApiResource, SiteModeType::WebClient]);
        $foreignSite = $this->createVerifiedSiteWithModes($foreign, 'foreign.test', [SiteModeType::ApiResource]);
        $client = $this->createClient($owner, $ownedSite);
        $ownEvent = $this->createAuditEvent($owner, $ownedSite, [
            'category' => 'site',
            'action' => 'site.connected',
            'summary' => 'Connected apishka.ru',
            'metadata' => ['unsafe' => '<script>alert(1)</script>'],
        ]);
        $this->createAuditEvent($foreign, $foreignSite, [
            'category' => 'site',
            'action' => 'site.connected',
            'summary' => 'Connected foreign.test',
        ]);

        $this
            ->actingAs($owner)
            ->get('/portal/account/social')
            ->assertOk()
            ->assertSee('Social accounts')
            ->assertSee('Google')
            ->assertSee('VK')
            ->assertSee('Yandex');

        $this
            ->actingAs($owner)
            ->get('/portal/account/sessions')
            ->assertOk()
            ->assertSee('Sessions')
            ->assertSee('Current session');

        $this
            ->actingAs($owner)
            ->get('/portal/developer')
            ->assertOk()
            ->assertSee('Developer')
            ->assertSee('apishka.ru')
            ->assertDontSee('foreign.test');

        $this
            ->actingAs($owner)
            ->get("/portal/developer/sites/{$ownedSite->id}/verification")
            ->assertOk()
            ->assertSee('_idshka.apishka.ru')
            ->assertSee('/.well-known/idshka-site-verification.txt');

        $this
            ->actingAs($owner)
            ->get("/portal/developer/sites/{$ownedSite->id}/credentials")
            ->assertOk()
            ->assertSee($client->client_id)
            ->assertSee('Secrets are shown only once');

        $this
            ->actingAs($owner)
            ->get('/portal/audit?category=site')
            ->assertOk()
            ->assertSee('site.connected')
            ->assertSee('Connected apishka.ru')
            ->assertDontSee('Connected foreign.test');

        $this
            ->actingAs($owner)
            ->get("/portal/audit/{$ownEvent->id}")
            ->assertOk()
            ->assertSee('site.connected')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_account_sessions_skip_database_listing_for_non_database_session_store(): void
    {
        config(['session.driver' => 'redis']);
        $owner = User::factory()->create();
        $request = Request::create('/portal/account/sessions');
        $request->setUserResolver(static fn (): User => $owner);
        $request->setLaravelSession(new Store('test-session', new ArraySessionHandler(120)));
        $queries = [];

        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $view = app(SessionController::class)($request);
        $data = $view->getData();

        $this->assertSame('redis', $data['sessionStore']);
        $this->assertFalse($data['sessionsAreEnumerable']);
        $this->assertCount(0, $data['sessions']);
        $this->assertFalse(collect($queries)->contains(
            static fn (string $sql): bool => str_contains(strtolower($sql), 'sessions')
        ));
    }

    public function test_audit_to_filter_includes_events_through_selected_day(): void
    {
        $owner = User::factory()->create();
        $site = $this->createVerifiedSiteWithModes($owner, 'apishka.ru', [SiteModeType::ApiResource]);
        $this->createAuditEvent($owner, $site, [
            'category' => 'site',
            'action' => 'site.inside_day',
            'summary' => 'Inside selected day',
            'occurred_at' => '2026-05-09 23:59:59',
        ]);
        $this->createAuditEvent($owner, $site, [
            'category' => 'site',
            'action' => 'site.after_day',
            'summary' => 'After selected day',
            'occurred_at' => '2026-05-10 00:00:01',
        ]);

        $this
            ->actingAs($owner)
            ->get('/portal/audit?to=2026-05-09')
            ->assertOk()
            ->assertSee('Inside selected day')
            ->assertDontSee('After selected day');
    }

    public function test_developer_site_and_audit_detail_pages_are_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $foreign = User::factory()->create();
        $foreignSite = $this->createVerifiedSiteWithModes($foreign, 'foreign.test', [SiteModeType::WebClient]);
        $foreignEvent = $this->createAuditEvent($foreign, $foreignSite, [
            'category' => 'oidc',
            'action' => 'oidc.client_created',
            'summary' => 'Foreign client created',
        ]);

        $this
            ->actingAs($owner)
            ->get("/portal/developer/sites/{$foreignSite->id}")
            ->assertNotFound();

        $this
            ->actingAs($owner)
            ->get("/portal/audit/{$foreignEvent->id}")
            ->assertNotFound();
    }

    public function test_raw_tokens_and_client_secrets_are_one_time_on_workspace_pages(): void
    {
        $owner = User::factory()->create();
        $site = $this->createVerifiedSiteWithModes($owner, 'apishka.ru', [SiteModeType::ApiResource, SiteModeType::WebClient]);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $tokenResponse = $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post('/portal/api-tokens', [
                'site_id' => $site->id,
                'scopes' => 'orders.read',
                'permissions' => 'orders.read',
            ]);

        $tokenHtml = $tokenResponse->getContent();
        $this->assertIsString($tokenHtml);
        $this->assertMatchesRegularExpression('/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', $tokenHtml);
        preg_match('/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', $tokenHtml, $tokenMatches);
        $rawToken = $tokenMatches[0];

        $this
            ->actingAs($owner)
            ->get('/portal/account/tokens')
            ->assertOk()
            ->assertDontSee($rawToken, false);

        $clientResponse = $this
            ->actingAs($owner)
            ->followingRedirects()
            ->post('/portal/clients', [
                'site_id' => $site->id,
                'name' => 'Example Web',
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            ]);

        $clientHtml = $clientResponse->getContent();
        $this->assertIsString($clientHtml);
        $this->assertMatchesRegularExpression('/secret_[A-Za-z0-9]+/', $clientHtml);
        preg_match('/secret_[A-Za-z0-9]+/', $clientHtml, $secretMatches);
        $rawSecret = $secretMatches[0];

        $this
            ->actingAs($owner)
            ->get("/portal/developer/sites/{$site->id}/credentials")
            ->assertOk()
            ->assertDontSee($rawSecret, false);
    }

    /**
     * @param  list<SiteModeType>  $modes
     */
    private function createVerifiedSiteWithModes(User $owner, string $domain, array $modes): Site
    {
        /** @var Site $site */
        $site = Site::query()->create([
            'id' => 'site_'.strtolower((string) str()->ulid()),
            'owner_user_id' => $owner->id,
            'display_name' => ucfirst(strtok($domain, '.')),
            'domain' => $domain,
            'normalized_domain' => $domain,
            'verification_status' => SiteVerificationStatus::Verified->value,
            'verified_at' => now(),
        ]);

        foreach (['dns_txt', 'file'] as $method) {
            SiteVerification::query()->create([
                'site_id' => $site->id,
                'method' => $method,
                'token' => 'token_'.$method.'_'.$site->id,
                'expires_at' => now()->addMinutes(30),
                'status' => SiteVerificationStatus::Verified->value,
                'verified_at' => now(),
            ]);
        }

        foreach ($modes as $mode) {
            SiteMode::query()->create([
                'site_id' => $site->id,
                'mode' => $mode->value,
                'enabled_at' => now(),
            ]);
        }

        return $site;
    }

    private function createClient(User $owner, Site $site): OidcClient
    {
        /** @var OidcClient $client */
        $client = OidcClient::query()->create([
            'site_id' => $site->id,
            'owner_user_id' => $owner->id,
            'client_id' => 'client_test_'.$site->id,
            'client_secret_hash' => Hash::make('secret_test'),
            'name' => 'Example Web',
        ]);

        OidcRedirectUri::query()->create([
            'oidc_client_id' => $client->id,
            'redirect_uri' => 'https://'.$site->normalized_domain.'/auth/idshka/callback',
            'redirect_uri_hash' => hash('sha256', 'https://'.$site->normalized_domain.'/auth/idshka/callback'),
        ]);

        return $client;
    }

    /**
     * @param  array{category: string, action: string, summary: string, metadata?: array<string, mixed>, occurred_at?: mixed}  $attributes
     */
    private function createAuditEvent(User $user, Site $site, array $attributes): AuditEvent
    {
        /** @var AuditEvent $event */
        $event = AuditEvent::query()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'category' => $attributes['category'],
            'action' => $attributes['action'],
            'summary' => $attributes['summary'],
            'metadata' => $attributes['metadata'] ?? ['site_id' => $site->id],
            'occurred_at' => $attributes['occurred_at'] ?? now(),
        ]);

        return $event;
    }
}
