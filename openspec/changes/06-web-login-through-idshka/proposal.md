# Proposal: 06-web-login-through-idshka

## Intent

- [x] T2 - OIDC client and redirect URI schema/models
  - Files: `database/migrations/*_create_oidc_clients_table.php`, `database/migrations/*_create_oidc_redirect_uris_table.php`, `app/Domain/OidcClients/Models/OidcClient.php`, `app/Domain/OidcClients/Models/OidcRedirectUri.php`.
  - Deliverable: persist `site_id`, `owner_user_id`, generated `client_id`, hashed `client_secret`, display name, revoked status and exact redirect URIs with indexes on `site_id`, `owner_user_id`, `client_id`.
  - Logging: migration/model work does not log; future service logs must include only client id, site id and redirect URI hash when needed.
  - Depends on: T1.

## Scope

- OIDC client and redirect URI persistence for verified `web_client` sites.
- Strict OAuth authorize request validation.
- Short-lived one-time authorization codes with S256 PKCE.
- `POST /oauth/token` for `authorization_code` only.
- `id_token` and short-lived web access token issuance.
- `GET /oauth/userinfo` for the web access token.
- Minimal `apishka-web-laravel` example that uses only HTTP endpoints, not internal PHP classes.
- Feature/unit tests and docs sync.

## Approach

Review legacy plan notes and refine the OpenSpec change design.

## Legacy source

Migrated from:
- .ai-factory/plans/06-web-login-through-idshka.md
- .ai-factory/plans/06-web-login-through-idshka/task.md
- .ai-factory/plans/06-web-login-through-idshka/context.md
- .ai-factory/plans/06-web-login-through-idshka/rules.md
- .ai-factory/plans/06-web-login-through-idshka/verify.md
- .ai-factory/plans/06-web-login-through-idshka/status.yaml

## Legacy plan notes

# 06-web-login-through-idshka

## Цель
Реализовать web login для подключенного сайта: `apishka.ru` уводит пользователя на `idshka.ru`, `idshka.ru` выдает Authorization Code после first-party login, callback `apishka.ru` обменивает code на tokens через Authorization Code + PKCE, а web-client создает локальную сессию.

## Branch and Date
- Branch: `feature/06-web-login-through-idshka`
- Refined: `2026-04-26`
- Mode: full companion plan

## Settings
- Testing: yes, mandatory for public OAuth/OIDC endpoints.
- Logging: verbose security-oriented logs without raw secrets.
- Docs: yes, docs must be updated in the same implementation.
- Roadmap linkage: `06-web-login-through-idshka`

## Area
- `web_client`
- `issuer`
- `laravel`
- `socialite`
- `security`

## Current Context
- `routes/oauth.php` currently exposes only `GET /oauth/jwks.json`.
- `app/Domain/OidcClients` is a skeleton and needs executable models/services.
- `app/Domain/Issuer` already has signing keys, JWKS and user API token issuance.
- `03-site-registry-and-modes` provides verified sites and `web_client` mode.
- `examples/apishka-web-laravel` is currently a README placeholder.

## Scope
- OIDC client and redirect URI persistence for verified `web_client` sites.
- Strict OAuth authorize request validation.
- Short-lived one-time authorization codes with S256 PKCE.
- `POST /oauth/token` for `authorization_code` only.
- `id_token` and short-lived web access token issuance.
- `GET /oauth/userinfo` for the web access token.
- Minimal `apishka-web-laravel` example that uses only HTTP endpoints, not internal PHP classes.
- Feature/unit tests and docs sync.

## Out of Scope
- Refresh tokens.
- Full portal UI for client/redirect URI management; plan `07` owns UI.
- Generic OAuth grant types other than `authorization_code`.
- Marketplace, SCIM, billing or enterprise provisioning.

## Acceptance Criteria
- `GET /oauth/authorize` validates `client_id`, exact `redirect_uri`, `response_type=code`, `scope`, `state`, `nonce`, `code_challenge` and `code_challenge_method=S256`.
- Unknown client, revoked client, unverified site, missing `web_client` mode, invalid scope, wildcard/mismatched redirect URI and missing PKCE fail closed.
- Valid authorize request for an authenticated user redirects to the registered callback with `code` and original `state`.
- Authorization code is short-lived, stored only as a hash, bound to client, redirect URI, user, nonce and PKCE challenge, and can be consumed once.
- `POST /oauth/token` verifies client secret, redirect URI, code lifetime, one-time consumption and PKCE verifier.
- `id_token` contains required issuer, audience/client id, subject, site id, nonce, `iat`, `nbf`, `exp`, `jti`, `kid` and `typ=JWT`.
- Web access token can call `GET /oauth/userinfo`; wrong token type, expired token and missing scopes fail closed.
- Raw authorization code, client secret, id token, access token and private key material are never logged.
- Example Laravel web-client documents callback/session creation using only `idshka.ru` HTTP endpoints.

## Tasks

### Phase 1 - Contracts and Persistence
- [x] T1 - OAuth/OIDC config and contracts
  - Files: `config/issuer.php`, `app/Contracts/Auth/*`, `app/Domain/Issuer/DTO/*`, `app/Domain/Issuer/Exceptions/*`, docs references.
  - Deliverable: add web-client token TTLs, allowed OIDC scopes, token type constants/classes for `id_token` and web access token, deterministic OAuth error helpers, and no-refresh-token MVP decision.
  - Logging: add non-secret debug/info logs for config-driven issuer decisions; never log codes, secrets or tokens.
  - Depends on: none.

- [x] T2 - OIDC client and redirect URI schema/models
  - Files: `database/migrations/*_create_oidc_clients_table.php`, `database/migrations/*_create_oidc_redirect_uris_table.php`, `app/Domain/OidcClients/Models/OidcClient.php`, `app/Domain/OidcClients/Models/OidcRedirectUri.php`.
  - Deliverable: persist `site_id`, `owner_user_id`, generated `client_id`, hashed `client_secret`, display name, revoked status and exact redirect URIs with indexes on `site_id`, `owner_user_id`, `client_id`.
  - Logging: migration/model work does not log; future service logs must include only client id, site id and redirect URI hash when needed.
  - Depends on: T1.

- [x] T3 - Client resolver, secret verification and redirect URI matcher
  - Files: `app/Domain/OidcClients/Actions/*`, `app/Domain/OidcClients/Services/*`, `app/Domain/OidcClients/Contracts/*`.
  - Deliverable: resolve active client by `client_id`, verify hashed client secret, require verified owned site with `web_client` mode, and perform exact redirect URI matching without wildcard support.
  - Logging: warn on client/site/mode/redirect failures with reason codes and non-secret ids only.
  - Depends on: T2 and `03-site-registry-and-modes`.

### Phase 2 - Provider Endpoints
- [x] T4 - Authorize request validation and route
  - Files: `routes/oauth.php`, `app/Http/Requests/OAuth/AuthorizeRequest.php`, `app/Http/Controllers/OAuth/AuthorizeController.php`, `app/Providers/AppServiceProvider.php`.
  - Deliverable: `GET /oauth/authorize` behind `web`, `auth:web` and `throttle:oauth-authorize`; validate `response_type=code`, `client_id`, `redirect_uri`, `scope`, `state`, `nonce`, `code_challenge`, `code_challenge_method=S256`.
  - Logging: info start/completion with user id, client id and site id; warning for deterministic validation/eligibility failures; no query string dumps.
  - Depends on: T3.

- [x] T5 - Authorization code service with PKCE
  - Files: `database/migrations/*_create_oauth_authorization_codes_table.php`, `app/Domain/Issuer/Services/AuthorizationCodeService.php`, `app/Domain/Issuer/Services/PkceService.php`, `app/Domain/Issuer/Models/OAuthAuthorizationCode.php`.
  - Deliverable: generate raw code once, store only code hash, bind client/user/site/redirect URI/scope/nonce/code challenge, enforce short TTL and one-time consumption with transaction/lock.
  - Logging: log code lifecycle by code id/hash prefix only; never log raw code or verifier.
  - Depends on: T4.

- [x] T6 - Token endpoint for authorization code exchange
  - Files: `routes/oauth.php`, `app/Http/Requests/OAuth/TokenRequest.php`, `app/Http/Controllers/OAuth/TokenController.php`, `app/Providers/AppServiceProvider.php`.
  - Deliverable: `POST /oauth/token` with `throttle:oauth-token`; support only `grant_type=authorization_code`; verify client secret, redirect URI, one-time code and PKCE verifier; return deterministic JSON errors.
  - Logging: info start/completion by client id and code record id; warning for invalid grant/client/PKCE; no raw secret/code/verifier/token logs.
  - Depends on: T5.

- [x] T7 - ID token and web access token issuance
  - Files: `app/Domain/Issuer/Services/TokenIssuer.php` or dedicated web token issuer service, `app/Contracts/Auth/*`, `config/issuer.php`, unit tests.
  - Deliverable: issue signed `id_token` with nonce and user identity claims, plus short-lived web access token for `userinfo`; include `kid`, `alg=RS256`, `typ=JWT`; no refresh token.
  - Logging: issuer logs token metadata only: user id, client id, site id, token type, jti, kid and expiry.
  - Depends on: T6 and `04-token-issuer-and-jwks`.

- [x] T8 - Userinfo endpoint
  - Files: `routes/oauth.php`, `app/Http/Controllers/OAuth/UserInfoController.php`, `app/Domain/Issuer/Services/WebAccessTokenValidator.php`.
  - Deliverable: `GET /oauth/userinfo` validates Bearer web access token and returns `sub`, and profile/email claims according to scopes.
  - Logging: info/warn with jti, client id, user id and scope count only; no token logging.
  - Depends on: T7.

### Phase 3 - Example, Docs and Verification
- [x] T9 - Minimal `apishka-web-laravel` example
  - Files: `examples/apishka-web-laravel/README.md` and minimal example snippets/files if useful.
  - Deliverable: document login redirect, callback, token exchange, id token validation, userinfo call and local session creation using only public HTTP/OAuth endpoints.
  - Logging: example must redact `client_secret`, code and tokens in any sample logs.
  - Depends on: T8.

- [x] T10 - Tests, docs sync and verification evidence
  - Files: `tests/Feature/OAuthWebLoginFlowTest.php`, `tests/Unit/Issuer/PkceServiceTest.php`, `tests/Unit/OidcClients/*`, `docs/API_FLOWS.md`, `docs/LARAVEL_MODULES.md`, `docs/SOCIALITE.md`, `docs/README.md`.
  - Deliverable: cover happy path and fail-closed cases for authorize, token, PKCE, one-time code, redirect mismatch, nonce/id token, userinfo and secret/log safety.
  - Logging: tests must assert no raw code/client secret/token values are written to logs for sensitive flows.
  - Depends on: T1 through T9.

## Commit Plan
- Commit 1: `feat(oidc): add web client contracts and persistence` - T1-T3.
- Commit 2: `feat(oauth): implement authorization code pkce flow` - T4-T7.
- Commit 3: `feat(oauth): add userinfo example and verification` - T8-T10.

## Verification Commands
- `composer validate --strict`
- `php artisan route:list --path=oauth`
- `php artisan test --without-tty --filter=OAuthWebLoginFlowTest`
- `php artisan test --without-tty --filter=PkceServiceTest`
- `php artisan test --without-tty`
- `php vendor/bin/pint --test`
- `npm run build`
- `docker compose config`
- `git diff --check`

## Roadmap Linkage
- Milestone: `06-web-login-through-idshka`
- Rationale: this is the next strategic phase after issuer/JWKS and gateway, closing the web-client login flow described in `.ai-factory/ROADMAP.md`.
