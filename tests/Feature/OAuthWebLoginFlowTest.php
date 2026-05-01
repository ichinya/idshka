<?php

namespace Tests\Feature;

use App\Contracts\Auth\JwtClaims;
use App\Domain\Issuer\Models\OAuthAuthorizationCode;
use App\Domain\Issuer\Services\SigningKeyService;
use App\Domain\Issuer\Services\TokenIssuer;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteMode;
use App\Domain\Sites\Services\SiteIdFactory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Tests\TestCase;

class OAuthWebLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_route_uses_web_auth_and_oauth_throttle_middleware(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/oauth/authorize', 'GET'));

        $middleware = $route->gatherMiddleware();

        $this->assertContains('web', $middleware);
        $this->assertContains('auth:web', $middleware);
        $this->assertContains('throttle:oauth-authorize', $middleware);
    }

    public function test_authorization_code_pkce_flow_issues_tokens_and_userinfo(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create([
            'name' => 'Web Login User',
            'email' => 'web-login@example.com',
        ]);
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $this->app->make(SigningKeyService::class)->createActiveKey();
        [$verifier, $challenge] = $this->pkcePair();

        $authorizeResponse = $this
            ->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid profile email',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $authorizeResponse->assertRedirect();
        $redirectQuery = [];
        parse_str((string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

        $this->assertSame('state-123', $redirectQuery['state']);
        $this->assertNotEmpty($redirectQuery['code']);
        $this->assertDatabaseHas('oauth_authorization_codes', [
            'oidc_client_id' => $client->id,
            'user_id' => $user->id,
            'site_id' => $site->id,
            'code_hash' => hash('sha256', (string) $redirectQuery['code']),
            'nonce' => 'nonce-123',
        ]);

        $tokenResponse = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret-value',
            'code' => $redirectQuery['code'],
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'code_verifier' => $verifier,
        ]);

        $tokenResponse
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('expires_in', 600)
            ->assertJsonStructure([
                'access_token',
                'id_token',
                'token_type',
                'expires_in',
                'scope',
            ]);

        $idToken = (new Parser(new JoseEncoder))->parse((string) $tokenResponse->json('id_token'));
        $this->assertSame('JWT', $idToken->headers()->get('typ'));
        $this->assertSame(JwtClaims::TOKEN_TYPE_ID_TOKEN, $idToken->claims()->get('token_type'));
        $this->assertSame('nonce-123', $idToken->claims()->get('nonce'));
        $this->assertSame((string) $user->id, $idToken->claims()->get('sub'));
        $this->assertSame($site->id, $idToken->claims()->get('site_id'));
        $audClaim = $idToken->claims()->get('aud');
        $audiences = is_array($audClaim) ? $audClaim : [$audClaim];
        $this->assertContains($client->client_id, $audiences);

        $userinfoResponse = $this->getJson('/oauth/userinfo', [
            'Authorization' => 'Bearer '.$tokenResponse->json('access_token'),
        ]);

        $userinfoResponse
            ->assertOk()
            ->assertJsonPath('sub', (string) $user->id)
            ->assertJsonPath('email', 'web-login@example.com')
            ->assertJsonPath('name', 'Web Login User');

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret-value',
            'code' => $redirectQuery['code'],
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'code_verifier' => $verifier,
        ])->assertStatus(401)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_authorize_rejects_redirect_uri_mismatch(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        [, $challenge] = $this->pkcePair();

        $response = $this
            ->actingAs($user)
            ->getJson('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://evil.apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', 'redirect_uri_mismatch');
    }

    public function test_authorize_guest_receives_oauth_error_shape(): void
    {
        $siteOwner = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        [, $challenge] = $this->pkcePair();

        $this
            ->getJson('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]))
            ->assertStatus(401)
            ->assertJsonPath('error', 'authentication_required')
            ->assertJsonStructure(['error', 'message', 'request_id']);
    }

    public function test_authorize_preserves_registered_redirect_uri_query_parameters(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $redirectUri = 'https://apishka.ru/auth/idshka/callback?tenant=alpha';
        $this->addRedirectUri($client, $redirectUri);
        [, $challenge] = $this->pkcePair();

        $response = $this
            ->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => $redirectUri,
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $redirectQuery = [];
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

        $response->assertRedirect();
        $this->assertSame('alpha', $redirectQuery['tenant']);
        $this->assertSame('state-123', $redirectQuery['state']);
        $this->assertNotEmpty($redirectQuery['code']);
    }

    public function test_token_endpoint_rejects_bad_pkce_verifier(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $this->app->make(SigningKeyService::class)->createActiveKey();
        [, $challenge] = $this->pkcePair();

        $authorizeResponse = $this
            ->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $redirectQuery = [];
        parse_str((string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret-value',
            'code' => $redirectQuery['code'],
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'code_verifier' => 'wrong-horse-battery-staple-verifier-43-character-minimum',
        ])->assertStatus(401)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_authorize_requires_s256_pkce(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        [, $challenge] = $this->pkcePair();

        $this
            ->actingAs($user)
            ->getJson('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'plain',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonStructure(['fields' => ['code_challenge_method']]);
    }

    public function test_authorize_rejects_malformed_pkce_challenge(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');

        $this
            ->actingAs($user)
            ->getJson('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => 'short',
                'code_challenge_method' => 'S256',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonStructure(['fields' => ['code_challenge']]);
    }

    public function test_authorize_rejects_scope_without_openid(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        [, $challenge] = $this->pkcePair();

        $this
            ->actingAs($user)
            ->getJson('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'profile email',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_scope');
    }

    public function test_token_endpoint_rejects_weak_pkce_verifier_without_consuming_code(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $this->app->make(SigningKeyService::class)->createActiveKey();
        $rawCode = 'weak-authorization-code';
        $weakVerifier = 'short';

        $authorizationCode = OAuthAuthorizationCode::query()->create([
            'oidc_client_id' => $client->id,
            'user_id' => $user->id,
            'site_id' => $site->id,
            'code_hash' => hash('sha256', $rawCode),
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'scopes' => ['openid'],
            'nonce' => 'nonce-123',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $weakVerifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'expires_at' => now()->addMinutes(5),
            'consumed_at' => null,
        ]);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret-value',
            'code' => $rawCode,
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'code_verifier' => $weakVerifier,
        ])->assertStatus(422)
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonStructure(['fields' => ['code_verifier']]);

        $this->assertNull($authorizationCode->refresh()->consumed_at);
    }

    public function test_token_endpoint_rejects_invalid_client_secret_without_consuming_code(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $this->app->make(SigningKeyService::class)->createActiveKey();
        [$verifier, $challenge] = $this->pkcePair();

        $authorizeResponse = $this
            ->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $authorizeResponse->assertRedirect();
        $redirectQuery = [];
        parse_str((string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'wrong-secret',
            'code' => $redirectQuery['code'],
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'code_verifier' => $verifier,
        ])->assertStatus(401)
            ->assertJsonPath('error', 'invalid_client');

        $this->assertDatabaseHas('oauth_authorization_codes', [
            'code_hash' => hash('sha256', (string) $redirectQuery['code']),
            'consumed_at' => null,
        ]);
    }

    public function test_token_endpoint_authenticates_client_before_redirect_uri_checks(): void
    {
        $siteOwner = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        [$verifier] = $this->pkcePair();

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'wrong-secret',
            'code' => 'arbitrary-code',
            'redirect_uri' => 'https://evil.apishka.ru/auth/idshka/callback',
            'code_verifier' => $verifier,
        ])->assertStatus(401)
            ->assertJsonPath('error', 'invalid_client');
    }

    public function test_token_endpoint_rejects_expired_authorization_code(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $this->app->make(SigningKeyService::class)->createActiveKey();
        [$verifier, $challenge] = $this->pkcePair();

        $authorizeResponse = $this
            ->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $redirectQuery = [];
        parse_str((string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);
        $codeHash = hash('sha256', (string) $redirectQuery['code']);

        OAuthAuthorizationCode::query()
            ->where('code_hash', $codeHash)
            ->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret-value',
            'code' => $redirectQuery['code'],
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'code_verifier' => $verifier,
        ])->assertStatus(401)
            ->assertJsonPath('error', 'invalid_grant');

        $this->assertDatabaseHas('oauth_authorization_codes', [
            'code_hash' => $codeHash,
            'consumed_at' => null,
        ]);
    }

    public function test_token_endpoint_rejects_authorization_code_bound_to_other_client(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $otherClient = $this->createClient($site, $siteOwner, clientSecret: 'other-client-secret');
        $this->app->make(SigningKeyService::class)->createActiveKey();
        [$verifier, $challenge] = $this->pkcePair();

        $authorizeResponse = $this
            ->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $redirectQuery = [];
        parse_str((string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $otherClient->client_id,
            'client_secret' => 'other-client-secret',
            'code' => $redirectQuery['code'],
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'code_verifier' => $verifier,
        ])->assertStatus(401)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_token_endpoint_rejects_registered_but_wrong_redirect_uri(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $this->addRedirectUri($client, 'https://apishka.ru/auth/idshka/alternate-callback');
        $this->app->make(SigningKeyService::class)->createActiveKey();
        [$verifier, $challenge] = $this->pkcePair();

        $authorizeResponse = $this
            ->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $redirectQuery = [];
        parse_str((string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret-value',
            'code' => $redirectQuery['code'],
            'redirect_uri' => 'https://apishka.ru/auth/idshka/alternate-callback',
            'code_verifier' => $verifier,
        ])->assertStatus(401)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_userinfo_rejects_user_api_token_type(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $this->app->make(SigningKeyService::class)->createActiveKey();

        $issuedToken = $this->app->make(TokenIssuer::class)->issueUserApiToken(
            userId: $user->id,
            siteId: $site->id,
            audience: 'apishka.ru',
            scopes: ['openid'],
            permissions: [],
        );

        $this->getJson('/oauth/userinfo', [
            'Authorization' => 'Bearer '.$issuedToken->rawToken,
        ])->assertStatus(401)
            ->assertJsonPath('error', 'invalid_token');
    }

    public function test_userinfo_returns_claims_according_to_scopes(): void
    {
        $siteOwner = User::factory()->create();
        $user = User::factory()->create([
            'name' => 'Scoped User',
            'email' => 'scoped@example.com',
        ]);
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $this->app->make(SigningKeyService::class)->createActiveKey();
        [$verifier, $challenge] = $this->pkcePair();

        $authorizeResponse = $this
            ->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $redirectQuery = [];
        parse_str((string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

        $tokenResponse = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret-value',
            'code' => $redirectQuery['code'],
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'code_verifier' => $verifier,
        ]);

        $idToken = (new Parser(new JoseEncoder))->parse((string) $tokenResponse->json('id_token'));
        $this->assertFalse($idToken->claims()->has('email'));
        $this->assertFalse($idToken->claims()->has('name'));

        $this->getJson('/oauth/userinfo', [
            'Authorization' => 'Bearer '.$tokenResponse->json('access_token'),
        ])->assertOk()
            ->assertJsonPath('sub', (string) $user->id)
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('name');
    }

    public function test_oauth_web_login_flow_does_not_log_raw_secrets(): void
    {
        $loggedArguments = [];
        $this->captureLogArguments($loggedArguments);

        $siteOwner = User::factory()->create();
        $user = User::factory()->create();
        $site = $this->createSite($siteOwner->id, verified: true, webClientMode: true);
        $client = $this->createClient($site, $siteOwner, clientSecret: 'client-secret-value');
        $this->app->make(SigningKeyService::class)->createActiveKey();
        [$verifier, $challenge] = $this->pkcePair();

        $authorizeResponse = $this
            ->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $client->client_id,
                'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
                'scope' => 'openid profile email',
                'state' => 'state-123',
                'nonce' => 'nonce-123',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]));

        $redirectQuery = [];
        parse_str((string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

        $tokenResponse = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret-value',
            'code' => $redirectQuery['code'],
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'code_verifier' => $verifier,
        ]);

        $tokenResponse->assertOk();

        $accessToken = (string) $tokenResponse->json('access_token');
        $idToken = (string) $tokenResponse->json('id_token');

        $this->getJson('/oauth/userinfo', [
            'Authorization' => 'Bearer '.$accessToken,
        ])->assertOk();

        $this->assertLogsDoNotContainSecrets(
            $loggedArguments,
            'client-secret-value',
            $verifier,
            (string) $redirectQuery['code'],
            $accessToken,
            $idToken,
        );
    }

    private function createClient(Site $site, User $owner, string $clientSecret): OidcClient
    {
        /** @var OidcClient $client */
        $client = OidcClient::query()->create([
            'site_id' => $site->id,
            'owner_user_id' => $owner->id,
            'client_id' => 'client_'.strtolower((string) str()->ulid()),
            'client_secret_hash' => Hash::make($clientSecret),
            'name' => 'Apishka Web',
        ]);

        OidcRedirectUri::query()->create([
            'oidc_client_id' => $client->id,
            'redirect_uri' => 'https://apishka.ru/auth/idshka/callback',
            'redirect_uri_hash' => hash('sha256', 'https://apishka.ru/auth/idshka/callback'),
        ]);

        return $client;
    }

    private function addRedirectUri(OidcClient $client, string $redirectUri): void
    {
        OidcRedirectUri::query()->create([
            'oidc_client_id' => $client->id,
            'redirect_uri' => $redirectUri,
            'redirect_uri_hash' => hash('sha256', $redirectUri),
        ]);
    }

    private function createSite(int $ownerId, bool $verified, bool $webClientMode): Site
    {
        $site = Site::query()->create([
            'id' => app(SiteIdFactory::class)->make(),
            'owner_user_id' => $ownerId,
            'display_name' => 'Apishka',
            'domain' => 'apishka.ru',
            'normalized_domain' => 'apishka.ru',
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

    /**
     * @return array{0: string, 1: string}
     */
    private function pkcePair(): array
    {
        $verifier = 'correct-horse-battery-staple-verifier-43-character-minimum';
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return [$verifier, $challenge];
    }

    /**
     * @param  array<int, mixed>  $loggedArguments
     */
    private function captureLogArguments(array &$loggedArguments): void
    {
        Log::shouldReceive('shareContext')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function (...$arguments) use (&$loggedArguments): void {
                $loggedArguments[] = ['shareContext', $arguments];
            });

        foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'] as $level) {
            Log::shouldReceive($level)
                ->zeroOrMoreTimes()
                ->andReturnUsing(function (...$arguments) use (&$loggedArguments, $level): void {
                    $loggedArguments[] = [$level, $arguments];
                });
        }
    }

    /**
     * @param  array<int, mixed>  $loggedArguments
     */
    private function assertLogsDoNotContainSecrets(array $loggedArguments, string ...$secrets): void
    {
        $encodedArguments = json_encode($loggedArguments, JSON_PARTIAL_OUTPUT_ON_ERROR);

        $this->assertIsString($encodedArguments);

        foreach ($secrets as $secret) {
            if ($secret !== '') {
                $this->assertStringNotContainsString($secret, $encodedArguments);
            }
        }
    }
}
