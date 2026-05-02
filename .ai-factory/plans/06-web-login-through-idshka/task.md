# Task

## T1 - OAuth/OIDC config and contracts
- Status: completed
- Deliverable: add web-client token TTLs, allowed OIDC scopes, token type contracts/classes for `id_token` and web access token, deterministic OAuth error helpers, and the explicit no-refresh-token MVP decision.
- Files: `config/issuer.php`, `app/Contracts/Auth/*`, `app/Domain/Issuer/DTO/*`, `app/Domain/Issuer/Exceptions/*`.
- Logging: non-secret debug/info logs only; never log authorization codes, client secrets, raw tokens or private key material.
- Depends on: none.

## T2 - OIDC client and redirect URI schema/models
- Status: completed
- Deliverable: migrations and models for `oidc_clients` and `oidc_redirect_uris` with hashed client secrets, exact redirect URI storage, revoked state and required indexes.
- Files: `database/migrations/*_create_oidc_clients_table.php`, `database/migrations/*_create_oidc_redirect_uris_table.php`, `app/Domain/OidcClients/Models/OidcClient.php`, `app/Domain/OidcClients/Models/OidcRedirectUri.php`.
- Logging: no logging in migrations/models; downstream service logs use only client id, site id and redirect URI hash.
- Depends on: T1.

## T3 - Client resolver, secret verification and redirect URI matcher
- Status: completed
- Deliverable: services/actions that resolve active clients, verify hashed client secrets, require verified site with `web_client` mode, and perform strict redirect URI matching with no wildcards.
- Files: `app/Domain/OidcClients/Actions/*`, `app/Domain/OidcClients/Services/*`, `app/Domain/OidcClients/Contracts/*`.
- Logging: warn with reason code and non-secret identifiers on client/site/mode/redirect failures.
- Depends on: T2 and `03-site-registry-and-modes`.

## T4 - Authorize request validation and route
- Status: completed
- Deliverable: `GET /oauth/authorize` under `web`, `auth:web`, `throttle:oauth-authorize`; validate `response_type=code`, `client_id`, exact `redirect_uri`, `scope`, `state`, `nonce`, `code_challenge`, `code_challenge_method=S256`.
- Files: `routes/oauth.php`, `app/Http/Requests/OAuth/AuthorizeRequest.php`, `app/Http/Controllers/OAuth/AuthorizeController.php`, `app/Providers/AppServiceProvider.php`.
- Logging: info start/completion with user id, client id and site id; warning for deterministic failures; do not dump query strings.
- Depends on: T3.

## T5 - Authorization code service with PKCE
- Status: completed
- Deliverable: one-time short-lived authorization codes, raw code returned once, stored only as hash, bound to client/user/site/redirect URI/scope/nonce/PKCE challenge and consumed transactionally.
- Files: `database/migrations/*_create_oauth_authorization_codes_table.php`, `app/Domain/Issuer/Services/AuthorizationCodeService.php`, `app/Domain/Issuer/Services/PkceService.php`, `app/Domain/Issuer/Models/OAuthAuthorizationCode.php`.
- Logging: log lifecycle by code record id/hash prefix only; never log raw code or verifier.
- Depends on: T4.

## T6 - Token endpoint for authorization code exchange
- Status: completed
- Deliverable: `POST /oauth/token` with `throttle:oauth-token`, supporting only `grant_type=authorization_code`, verifying client secret, redirect URI, code lifetime/consumption and PKCE verifier.
- Files: `routes/oauth.php`, `app/Http/Requests/OAuth/TokenRequest.php`, `app/Http/Controllers/OAuth/TokenController.php`, `app/Providers/AppServiceProvider.php`.
- Logging: info start/completion by client id and code record id; warning for invalid grant/client/PKCE; never log secrets, codes, verifiers or tokens.
- Depends on: T5.

## T7 - ID token and web access token issuance
- Status: completed
- Deliverable: signed `id_token` with nonce and user identity claims plus short-lived web access token for `userinfo`, both with `kid`, `alg=RS256`, `typ=JWT`; no refresh token.
- Files: `app/Domain/Issuer/Services/TokenIssuer.php` or dedicated web token issuer service, `app/Contracts/Auth/*`, `config/issuer.php`.
- Logging: log token metadata only: user id, client id, site id, token type, jti, kid and expiry.
- Depends on: T6 and `04-token-issuer-and-jwks`.

## T8 - Userinfo endpoint
- Status: completed
- Deliverable: `GET /oauth/userinfo` validates Bearer web access token and returns `sub`, profile and email claims according to scopes.
- Files: `routes/oauth.php`, `app/Http/Controllers/OAuth/UserInfoController.php`, `app/Domain/Issuer/Services/WebAccessTokenValidator.php`.
- Logging: info/warn with jti, client id, user id and scope count only; never log the token.
- Depends on: T7.

## T9 - Minimal `laravel-web-client` example
- Status: completed
- Deliverable: example docs/snippets for login redirect, callback, token exchange, id token validation, userinfo call and local session creation through public HTTP endpoints.
- Files: `examples/laravel-web-client/README.md` and minimal example files if useful.
- Logging: sample logs must redact `client_secret`, code and tokens.
- Depends on: T8.

## T10 - Tests, docs sync and verification evidence
- Status: completed
- Deliverable: feature/unit coverage for authorize, token, PKCE, one-time code, redirect mismatch, nonce/id token, userinfo and secret/log safety; update external docs.
- Files: `tests/Feature/OAuthWebLoginFlowTest.php`, `tests/Unit/Issuer/PkceServiceTest.php`, `tests/Unit/OidcClients/*`, `docs/API_FLOWS.md`, `docs/LARAVEL_MODULES.md`, `docs/SOCIALITE.md`, `docs/README.md`.
- Logging: tests must assert raw code/client secret/token values are not written to logs.
- Depends on: T1 through T9.
