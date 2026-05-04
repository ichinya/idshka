# Design: Security Hardening and Operations

## Technical Approach

This change hardens the existing Laravel + OpenResty implementation without changing the core product architecture.

Rate limits will be centralized in Laravel configuration and applied by named limiters in `app/Providers/AppServiceProvider.php` and route middleware. Existing limiters such as `auth-login`, `auth-social`, `site-registry`, `token-issue`, `token-revoke`, `oauth-authorize`, `oauth-token`, `oauth-userinfo`, and `jwks-public` should remain stable where possible, while portal-specific write flows get explicit limiter names instead of inheriting only `auth:web`. Throttling behavior must be deterministic for JSON clients and browser portal users: rejected requests return or redirect with a traceable `request_id` and do not reach domain actions.

Structured logging will build on `App\Http\Middleware\AssignRequestId`, `Log::shareContext`, and existing domain log contexts. Implementation should patch gaps rather than introduce a new logging stack: request/flow logs must include `request_id` and safe identifiers, while tests assert raw secrets never appear in captured logs. A small application helper for safe log context/redaction is acceptable if it reduces duplicate secret filtering across controllers, services, commands, and tests. Gateway Lua logging should thread `request_id` through JWKS fetch/cache code, not just JWT validation.

Key rotation will extend `App\Domain\Issuer\Services\SigningKeyService`, keep `App\Domain\Issuer\Services\JwksService` aligned with the lifecycle, and expose operator-facing Artisan commands. Rotation should support preparing a `next` key, activating it, selecting the newest usable active key for new signatures, keeping previous active public keys published until signed tokens expire, retiring expired keys, force-retiring compromised keys, rollback, clearing JWKS cache, and showing current key state. Because user API tokens may be non-expiring, automatic retirement must detect blocking tokens with `expires_at = null` and require explicit operator action before removing that key from JWKS. All operations must log only `kid`, database key id, status, and timestamps.

Operational docs will live under `docs/` with links from `docs/README.md`, `docs/API_FLOWS.md`, and `docs/GATEWAY_CONTRACT.md` as needed. `.ai-factory/SECURITY.md` is a planning/security context artifact and should not be the only user-facing runbook.

CI will keep the existing composer, npm, test, build, Docker Compose, and gateway smoke coverage, then add hardening checks that are deterministic in GitHub Actions. Prefer locked dependency audit commands and explicit smoke assertions over broad scanners that need extra services or secrets.

## Data / Artifact Model

- `config/security.php` or a similarly named Laravel config file should hold endpoint limit defaults and env overrides. It must not contain secrets.
- Existing persisted entities remain the source of truth: `users`, `social_accounts`, `sites`, `site_modes`, `site_verifications`, `api_tokens`, `revoked_jti`, `oidc_clients`, `oidc_redirect_uris`, `oauth_authorization_codes`, `audit_events`, and `signing_keys`.
- Signing keys keep encrypted private material in the database and public material in JWKS. Rotation may use existing `status`, `activated_at`, and `retired_at` fields; multiple `active` keys with different activation/retirement windows are acceptable if `requireActiveKey()` deterministically selects the newest usable key. Add a migration only if the current model cannot represent the safe lifecycle.
- Non-expiring API tokens are an explicit key-retirement constraint. Runbooks and commands must either block automatic retirement for their signing key or require a force/compromise path with documented residual impact.
- Runtime state for this OpenSpec change belongs under `.ai-factory/state/08-security-hardening-and-ops/`.
- QA evidence belongs under `.ai-factory/qa/08-security-hardening-and-ops/`.

## Integration Points

- Laravel rate limiting: `app/Providers/AppServiceProvider.php`, `routes/web.php`, `routes/api.php`, `routes/oauth.php`, `bootstrap/app.php`, and relevant feature tests.
- Deterministic throttling responses: Laravel throttle middleware/exception handling plus portal redirect handling where browser UX needs session errors instead of JSON.
- Portal credential flows: `app/Http/Controllers/Portal/PortalController.php`, portal routes, and `tests/Feature/PortalManagementFlowTest.php`.
- OAuth/OIDC flows: `app/Http/Controllers/OAuth/*`, `app/Http/Requests/OAuth/*`, `app/Domain/OidcClients/*`, and `tests/Feature/OAuthWebLoginFlowTest.php`.
- Issuer token and key flows: `app/Domain/Issuer/*`, `routes/console.php` or command classes under `app/Console/Commands`, and issuer unit/feature tests.
- Gateway runtime: `infra/openresty/demo-resource/nginx.conf`, `infra/openresty/demo-resource/lua/jwks_cache.lua`, `infra/openresty/demo-resource/lua/jwt_validate.lua`, `infra/openresty/demo-resource/smoke.sh`, and gateway docs.
- CI: `.github/workflows/ci.yml`.
- Docs: `docs/API_FLOWS.md`, `docs/GATEWAY_CONTRACT.md`, `docs/README.md`, plus new security/operations runbook pages as needed.

## Alternatives Considered

- Keep limiter thresholds hard-coded in `AppServiceProvider`. Rejected because production tuning then requires code changes and makes verification harder.
- Replace Laravel logging with a new structured logger package. Rejected for this slice because the current `Log::shareContext` approach is sufficient and avoids unnecessary dependencies.
- Add a new `rotating` signing-key status immediately. Deferred until implementation proves existing `active` plus future `retired_at` cannot safely publish old keys through the token TTL window.
- Trust stale gateway keys after JWKS failure. Rejected for this change because project rules require fail-closed behavior unless a separate threat model approves stale trust.

## Risks

- Portal form throttling must remain user-friendly for browser redirects while still producing deterministic 429 behavior and no state mutation.
- Key rotation commands can create production-impacting states; command output must be explicit and tests must cover active/next/retired transitions, multiple active keys, and non-expiring token blockers.
- Log tests can become brittle if they assert full message bodies. Prefer checking absence of raw secrets and presence of required context keys.
- CI audit commands may require lockfile consistency and network access. Keep thresholds and failure messages clear.
