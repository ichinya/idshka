# Tasks

## Settings

- Testing: yes
- Logging: verbose
- Docs: yes
- Roadmap Linkage: Security / Auth / Secrets; Observability / Logs / Metrics; CI/CD / Delivery
- Runtime State: `.ai-factory/state/08-security-hardening-and-ops/`
- QA Evidence: `.ai-factory/qa/08-security-hardening-and-ops/`

## 1. Planning and artifacts

- [x] 1.1 Confirm `openspec/changes/08-security-hardening-and-ops/specs/security-hardening-ops/spec.md` matches the implementation scope and re-run OpenSpec validation before editing product code. Logging: record only validation command, status, and artifact paths under runtime state if needed.
- [x] 1.2 Regenerate or confirm generated rules for `08-security-hardening-and-ops` after spec changes. Logging: report generated rule freshness without editing generated files by hand.

## 2. Configurable production rate limits

- [x] 2.1 Add centralized Laravel rate-limit configuration with env overrides for auth login/register, Socialite redirect/callback/link/unlink, site registry writes, OAuth authorize/token/userinfo/JWKS, API token issue/revoke, portal site writes, portal credential issue/revoke, portal client/redirect URI writes, and verification checks. Files: `config/security.php` or equivalent, `.env.example`, `app/Providers/AppServiceProvider.php`. Logging: limiter setup should log only limiter names and safe config diagnostics when invalid configuration is detected.
- [x] 2.2 Apply explicit throttling middleware to portal write routes and any security-sensitive route currently missing a named limiter. Files: `routes/web.php`, `routes/api.php`, `routes/oauth.php`, `bootstrap/app.php` if middleware aliases are needed. Logging: throttled responses include `request_id`; no payload secrets are logged.
- [x] 2.3 Add feature tests proving throttled requests fail before credential, token, client, redirect URI, verification, or revoke state changes. Files: `tests/Feature/*RateLimit*Test.php` or focused additions to existing feature suites. Logging: tests must assert raw JWTs, client secrets, authorization codes, PKCE verifiers, and private keys are absent from captured logs.
- [x] 2.4 Make throttling responses deterministic for both JSON endpoints and browser portal forms. Files: `bootstrap/app.php`, `app/Http/Middleware/*` if a custom response helper is needed, portal feature tests. Logging: rejected requests must expose/flash a `request_id` and must not log submitted credential payloads.

## 3. Secret-safe structured logs

- [x] 3.1 Audit Laravel auth, Socialite, portal, issuer, OAuth, site verification, revoke, audit listener, and probe logs for `request_id` coverage and non-secret context. Files: `app/Http/*`, `app/Domain/*`, `routes/probes.php`. Logging: add or preserve structured keys such as `user_id`, `site_id`, `client_id`, `token_id`, `jti`, `kid`, `status_code`, and `error_class`; never log raw secrets.
- [x] 3.2 Add or extend tests that capture logger calls and assert required request correlation plus secret redaction across token issue, OAuth token exchange, portal credential creation, revoke, Socialite callback, and signing-key operations. Files: `tests/Feature/*`, `tests/Unit/Issuer/*`. Logging: tests should check context keys rather than exact full messages where possible.
- [x] 3.3 Thread `request_id` through gateway JWKS cache/fetch logging and confirm gateway errors never include raw JWTs. Files: `infra/openresty/demo-resource/lua/jwks_cache.lua`, `infra/openresty/demo-resource/lua/jwt_validate.lua`, `infra/openresty/demo-resource/smoke.sh`. Logging: gateway logs include `request_id`, `kid`, and safe error reasons only.
- [x] 3.4 Introduce a small safe log context/redaction helper if it reduces duplicated secret filtering across controllers, domain services, console commands, and tests. Files: `app/Support/*` or equivalent local convention, affected callers, tests. Logging: helper must redact raw JWTs, client secrets, authorization codes, PKCE verifiers, Socialite tokens, passwords, private keys, and site verification challenge tokens.

## 4. Issuer signing-key rotation operations

- [x] 4.1 Extend `SigningKeyService` and keep `JwksService` aligned with safe lifecycle operations for prepare-next, activate-next, newest-active selection for signing, old-active JWKS publication, retire-expired, non-expiring-token retirement blockers, force-retire-by-kid, rollback-to-previous, status listing, and JWKS cache invalidation. Files: `app/Domain/Issuer/Services/SigningKeyService.php`, `app/Domain/Issuer/Services/JwksService.php`, `app/Domain/Issuer/Enums/SigningKeyStatus.php` if needed, issuer tests. Logging: log `key_id`, `kid`, old/new status, `activated_at`, `retired_at`, token blocker counts, and command outcome; never log private key material.
- [x] 4.2 Add Artisan commands for operators, for example `idshka:keys:status`, `idshka:keys:prepare`, `idshka:keys:activate-next`, `idshka:keys:retire-expired`, and `idshka:keys:force-retire {kid}` with clear dry-run or confirmation behavior for destructive operations. Files: `app/Console/Commands/*` or `routes/console.php` following project conventions. Logging: commands emit safe operator output and structured logs with `kid` only.
- [x] 4.3 Add unit/feature tests for key rotation, JWKS publication, token issuance after activation, old-key JWKS availability until token expiry, non-expiring API token retirement blockers, forced compromise handling, rollback, newest-active signing selection, and cache clearing. Files: `tests/Unit/Issuer/*SigningKey*Test.php`, `tests/Feature/IssuerApiFlowTest.php`. Logging: tests assert no private key, raw JWT, or decrypted PEM appears in logs or command output.

## 5. Backup, restore, and security runbooks

- [x] 5.1 Create operations documentation for backup/restore of PostgreSQL, Redis revoke denylist cache expectations, storage/runtime config, `APP_KEY`, signing keys, users, sites, modes, token metadata, OIDC clients, redirect URIs, authorization codes, audit events, and JWKS cache rebuild. Files: `docs/OPERATIONS.md` or `docs/BACKUP_RESTORE.md`, `docs/README.md`. Logging: docs must state that restore drills capture evidence without dumping raw secrets.
- [x] 5.2 Create security incident runbooks for leaked API token, leaked client secret, leaked authorization code, leaked Socialite provider token, leaked signing key, compromised `APP_KEY`, and gateway header trust failure. Files: `docs/SECURITY_RUNBOOK.md`, `docs/API_FLOWS.md`, `docs/GATEWAY_CONTRACT.md`. Logging: runbooks use non-secret identifiers such as `jti`, `client_id`, `kid`, `site_id`, and request ids; leaked client secret handling must state whether the current flow is revoke/recreate or true rotation.
- [x] 5.3 Add lightweight documentation checks or tests for required runbook sections and links from docs index pages. Files: `tests/Feature` or script checks if an existing docs-check pattern exists. Logging: checks print missing section names only.

## 6. CI security checks

- [x] 6.1 Extend `.github/workflows/ci.yml` with deterministic security gates such as least-privilege workflow permissions, `composer audit`, `npm audit` with a defined severity threshold, route/config smoke, and hardening-specific test groups while preserving existing composer validate, npm build, Laravel tests, Docker Compose config, and gateway smoke. Logging: CI output must not echo tokens or secrets.
- [x] 6.2 Add hardening smoke coverage for rate-limit behavior, gateway unknown `kid`, JWKS unavailable/fail-closed behavior where feasible, and runbook/link checks. Files: `.github/workflows/ci.yml`, `infra/openresty/demo-resource/smoke.sh`, tests. Logging: smoke failures show status/body snippets with `request_id`, not raw JWTs.

## 7. Gateway stale-key policy

- [x] 7.1 Implement explicit JWKS refresh and stale-key policy in the OpenResty reference: known cached keys are trusted only within configured TTL, unknown `kid` triggers refresh, fetch/decode failures fail closed, and no stale key is trusted past the configured window. Files: `infra/openresty/demo-resource/lua/jwks_cache.lua`, `infra/openresty/demo-resource/nginx.conf`. Logging: logs include `request_id`, `kid`, cache outcome, and fail-closed reason.
- [x] 7.2 Update gateway docs and OpenSpec/base docs if external behavior changes. Files: `docs/GATEWAY_CONTRACT.md`, `docs/API_FLOWS.md`, `infra/openresty/demo-resource/lua/README.md`, relevant `openspec/specs/**/spec.md` only if base behavior is changed. Logging: docs must state that raw JWTs and JWK private material are never logged.
- [x] 7.3 Add gateway smoke assertions for unknown key id, JWKS fetch failure or documented blocker, cache TTL behavior, and request id propagation in gateway errors. Files: `infra/openresty/demo-resource/smoke.sh`, CI workflow. Logging: smoke script must avoid printing valid raw tokens on failure.

## 8. Verification

- [x] 8.1 Run focused tests for changed Laravel feature/unit suites, gateway smoke, `composer validate --strict`, frontend build if touched, Docker Compose config, and OpenSpec validation. Logging: capture command names and pass/fail status only in QA evidence.
- [x] 8.2 Run full `php artisan test --without-tty`, `npm run build`, CI-equivalent smoke commands, `git diff --check`, and a debug-marker/env scan over touched files before `/aif-verify 08-security-hardening-and-ops`. Logging: record failures with non-secret context and leave QA evidence under `.ai-factory/qa/08-security-hardening-and-ops/`.

## Commit Plan

- Checkpoint 1: rate limits and structured logs. Suggested commit: `feat: harden identity rate limits and request logging`
- Checkpoint 2: key rotation and runbooks. Suggested commit: `feat: add issuer key rotation operations`
- Checkpoint 3: CI and gateway stale-key policy. Suggested commit: `feat: strengthen security hardening checks`
- Final checkpoint: verification artifacts and docs polish. Suggested commit: `docs: document security operations runbooks`
