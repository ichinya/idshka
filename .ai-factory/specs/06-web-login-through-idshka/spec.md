---
artifact_type: spec
plan_id: "06-web-login-through-idshka"
title: "Web login through IDShka"
artifact_status: archived
owner: aif-verify
created_at: 2026-04-26
updated_at: 2026-04-26
source_issue: null
source_plan: "06-web-login-through-idshka"
---

# Spec: 06-web-login-through-idshka

> Finalized specification archived from plan verification.

## Summary

Implemented the web-client login slice for connected sites: `example.test` can redirect users to `idshka.ru`, receive an Authorization Code after first-party login, exchange it through Authorization Code + S256 PKCE, validate a signed `id_token`, call `userinfo` with a short-lived web access token, and create a local web session using only public HTTP/OAuth endpoints.

## Status

| Field | Value |
|-------|-------|
| **Completed** | 2026-04-26 |
| **Verdict** | pass-with-notes |
| **Files Changed** | 38 |
| **Tests Added** | 3 |

## Implementation

### Scope Delivered

- [x] OAuth/OIDC issuer config for web-client TTLs, allowed OIDC scopes, token types, and no-refresh-token MVP behavior.
- [x] `oidc_clients`, `oidc_redirect_uris`, and `oauth_authorization_codes` schema/models.
- [x] OIDC client resolver with hashed secret verification, verified-site ownership checks, required `web_client` mode, and exact redirect URI matching.
- [x] `GET /oauth/authorize` with `web`, `auth:web`, `throttle:oauth-authorize`, strict request validation, state preservation, and non-secret logs.
- [x] One-time short-lived authorization codes stored only as hashes and bound to client, redirect URI, user, site, scopes, nonce, and PKCE challenge.
- [x] `POST /oauth/token` for `authorization_code` only, including client secret, redirect URI, code lifetime/consumption, and S256 PKCE checks.
- [x] Signed `id_token` plus short-lived web access token issuance with distinct token type and `typ=JWT`.
- [x] `GET /oauth/userinfo` with Bearer web access token validation and scope-limited claims.
- [x] HTTP-only `examples/laravel-web-client` documentation for redirect, callback, token exchange, ID token validation, userinfo, and local session creation.
- [x] Feature/unit coverage for happy path, fail-closed cases, one-time code handling, scope behavior, and raw-secret log safety.

### Key Changes

| File | Change Type | Description |
|------|-------------|-------------|
| `.env.example`, `config/issuer.php` | modified | Added web login token/code TTL configuration and OIDC scope settings. |
| `app/Contracts/Auth/JwtClaims.php`, `app/Contracts/Auth/OidcScopes.php` | modified/created | Added OIDC token type and scope contracts. |
| `database/migrations/2026_04_26_160*.php` | created | Added OIDC client, redirect URI, and authorization code persistence. |
| `app/Domain/OidcClients/*` | created | Added OIDC client models, DTO, exception, and resolver service. |
| `app/Domain/Issuer/DTO/*` | created | Added authorization-code and web-login token DTOs. |
| `app/Domain/Issuer/Services/AuthorizationCodeService.php` | created | Issues and consumes hashed one-time authorization codes. |
| `app/Domain/Issuer/Services/PkceService.php` | created | Verifies S256 PKCE challenges and rejects unsupported methods. |
| `app/Domain/Issuer/Services/WebAccessTokenValidator.php` | created | Validates web access tokens for `userinfo`. |
| `app/Domain/Issuer/Services/TokenIssuer.php` | modified | Added signed ID token and web access token issuance. |
| `app/Http/Requests/OAuth/*` | created | Added authorize/token validation request classes. |
| `app/Http/Controllers/OAuth/*` | created | Added authorize, token, and userinfo endpoints. |
| `routes/oauth.php`, `app/Providers/AppServiceProvider.php` | modified | Registered OAuth routes and rate limiters. |
| `tests/Feature/OAuthWebLoginFlowTest.php` | created | Covers web login flow and fail-closed OAuth behavior. |
| `tests/Unit/Issuer/PkceServiceTest.php` | created | Covers S256 PKCE and plain-method rejection. |
| `tests/Unit/OidcClients/OidcClientResolverTest.php` | created | Covers exact redirect URI, mode, verified site, revoked client, and secret checks. |
| `docs/API_FLOWS.md`, `docs/LARAVEL_MODULES.md`, `docs/SOCIALITE.md`, `docs/README.md` | modified | Synced docs with implemented web-client OAuth/OIDC behavior. |
| `examples/laravel-web-client/README.md` | modified | Added HTTP-only Laravel client example guidance. |

### Dependencies Added

| Package | Version | Purpose |
|---------|---------|---------|
| None | n/a | Reused existing Laravel and JWT stack. |

## Verification

### Final Results

| Check | Status | Notes |
|-------|--------|-------|
| Task Completeness | PASS | T1-T10 complete and verified against implementation. |
| Rules Compliance | PASS | Fail-closed OAuth behavior, exact redirect matching, secret handling, and logging rules are satisfied. |
| Code Quality | PASS | Composer validation, tests, Pint, Vite build, Compose config, and `git diff --check` passed. |
| Architecture | PASS | Domain logic is in `app/Domain/*`; controllers stay thin; external example uses public HTTP/JWKS only. |
| Documentation | PASS | API, modules, Socialite, docs index, and example README match implemented behavior. |
| Roadmap | PASS with notes | Plan linkage is present, but `.ai-factory/ROADMAP.md` still contains stale priority text. |

### Fresh Evidence

| Command | Result |
|---------|--------|
| `composer validate --strict` | passed |
| `php artisan route:list --path=oauth -v` | passed: authorize, token, userinfo, and JWKS routes registered |
| `php artisan test --without-tty --filter=OAuthWebLoginFlowTest` | passed: 12 tests, 68 assertions |
| `php artisan test --without-tty --filter="PkceServiceTest|OidcClientResolverTest|JwtClaimsTest"` | passed: 9 tests, 17 assertions |
| `php artisan test --without-tty` | passed: 65 tests, 419 assertions |
| `php vendor/bin/pint --test` | passed |
| `npm run build` | passed |
| `docker compose config` | passed |
| `git diff --check` | passed |
| changed-area unfinished marker scan | no matches |

### Findings Accepted

| ID | Severity | Issue | Reason for deferral |
|----|----------|-------|---------------------|
| N001 | note | `.ai-factory/ROADMAP.md` still lists plan 06 as a next strategic priority and has stale slice text. | Context drift only; implementation, docs, and plan linkage pass. Refresh with `$aif-roadmap check`. |

## Decisions Made

1. **Authorization Code + S256 PKCE only** - no implicit flow, password grant, client credentials, or refresh tokens in this MVP.
2. **Hash-only authorization code storage** - raw codes are returned once and never persisted in clear text.
3. **Exact redirect URI matching** - wildcard, host-only, suffix, and mismatched redirect URIs fail closed.
4. **Distinct web access token type** - `userinfo` rejects existing `user_api` tokens.
5. **HTTP-only client example** - connected web apps integrate through public endpoints instead of internal PHP classes.

## References

| Type | Reference |
|------|-----------|
| Original Plan Folder | `.ai-factory/plans/06-web-login-through-idshka/` |
| Original Plan File | `.ai-factory/plans/06-web-login-through-idshka.md` |
| Verification Report | `.ai-factory/specs/06-web-login-through-idshka/verify.md` |
| Branch | `feature/06-web-login-through-idshka` |

---

*Archived: 2026-04-26*
*Duration: 2026-04-26 - 2026-04-26*
