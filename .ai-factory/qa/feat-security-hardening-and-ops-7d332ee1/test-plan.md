## Test Plan: Security Hardening and Operations

**Date:** 2026-05-03
**Branch / Version:** `feat/security-hardening-and-ops`
**Environment:** local Docker Compose or equivalent development environment

---

### 1. Testing Goal

Verify that the hardening change works as a production security baseline: sensitive endpoints are rate-limited before mutation, logs and operational evidence never expose raw secrets, issuer signing keys can be safely rotated and retired, gateway JWKS behavior fails closed, runbooks guide safe operations, and CI gates cover the same risk areas.

---

### 2. Test Scope

**In Scope** — we test:

- Laravel rate-limit configuration and named middleware on auth, Socialite, API, OAuth, portal, token, client, redirect URI, JWKS, and verification routes.
- Deterministic throttling behavior for JSON/API/OAuth requests and browser portal forms.
- Secret-safe logging and audit context across auth, Socialite, issuer, OAuth, portal, site verification, revoke, command, and gateway flows.
- Issuer signing-key operations: status, prepare, activate-next, retire-expired, force-retire, rollback, JWKS cache invalidation, and non-expiring-token blockers.
- OpenResty gateway JWT/JWKS behavior: valid token, invalid signature, wrong audience, expired token, not-before token, unknown `kid`, JWKS unavailable, TTL expiry, and header sanitization.
- Operations and security runbooks for backup/restore, leaked credentials, leaked signing key, compromised `APP_KEY`, and gateway header trust failure.
- CI workflow structure for least-privilege permissions, dependency checks, config/route smoke, Compose validation, ingress smoke, and gateway smoke.

**Out of Scope** — we don't test:

- Billing, marketplace, SCIM, enterprise provisioning, or non-MVP grant types because they are not part of this change.
- Real external Socialite provider consoles beyond checking local callback/linking behavior and runbook instructions.
- Production deployment automation, external SIEM, Prometheus, OpenTelemetry, and signed gateway context implementation because the OpenSpec change explicitly keeps them out of scope.
- Browser visual design polish unless it blocks portal throttling usability.

---

### 3. Test Types

| Type | Priority | Area |
|------|----------|------|
| Functional | 🔴 High | Rate limits, throttling responses, key lifecycle commands, gateway validation, runbook procedures |
| Security | 🔴 High | Secret redaction, fail-closed gateway behavior, key retirement, revoke/rotate procedures |
| Negative | 🟡 Medium | Exceeded limits, invalid limiter config, missing/unknown `kid`, JWKS down, malformed/expired JWT, destructive command without confirmation |
| Edge cases | 🟡 Medium | Non-expiring API tokens, array `aud`, cache TTL boundary, portal redirects, config cache boot |
| Regression | 🟡 Medium | Existing login, Socialite, OAuth, site registry, portal, token issue/revoke, JWKS, gateway upstream context |
| Performance | 🟢 Low | Gateway cache refresh/TTL behavior under repeated protected requests |

---

### 4. Test Data

| Category | Data | Purpose |
|----------|------|---------|
| Valid owner | `owner@example.test` with an authenticated browser session | Portal, site registry, token/client management |
| Valid site | `example.test` with `api_resource` and `web_client` modes enabled as needed | Token audience, gateway, OAuth client flows |
| Valid OAuth client | `client_id` for `example.test`, exact redirect URI `https://example.test/auth/idshka/callback` | OAuth authorize/token and client secret flows |
| Rate-limit env | `SECURITY_RATE_LIMIT_TOKEN_ISSUE_PER_MINUTE=1`, `SECURITY_RATE_LIMIT_PORTAL_CLIENT_WRITE_PER_MINUTE=1`, invalid value such as `not-an-int` | Boundary and invalid config checks |
| Gateway URL | `http://127.0.0.1:8081/protected` | Protected upstream checks |
| JWKS URL | `http://127.0.0.1:8080/oauth/jwks.json` | JWKS publication and unavailable checks |
| Safe identifiers | `request_id`, `user_id`, `site_id`, `client_id`, `token_id`, `jti`, `kid` | Evidence without raw secret values |

---

### 5. Preconditions

- [ ] Application boots with the updated `.env.example` variables available for local override.
- [ ] PostgreSQL and Redis are reachable.
- [ ] A local owner user can authenticate.
- [ ] A verified site exists or can be created and verified for `example.test`.
- [ ] At least one active issuer signing key exists or can be created by the operator command.
- [ ] Gateway example can reach Laravel JWKS internally.
- [ ] Log access is available for application and gateway containers.
- [ ] Test evidence location is prepared and will store only safe identifiers.

---

### 6. Acceptance Criteria

- [ ] All 🔴 high-priority cases pass.
- [ ] Any exceeded limit rejects before domain mutation.
- [ ] Every rejection that should be traceable includes `request_id`.
- [ ] No evidence contains raw JWTs, client secrets, authorization codes, PKCE verifiers, provider tokens, private key material, passwords, site challenge tokens, or `APP_KEY` values.
- [ ] JWKS publishes only public key material and never a retired key after forced retirement or TTL expiry.
- [ ] Gateway rejects unknown/stale/unavailable signing-key material instead of proxying upstream.
- [ ] Operator runbooks clearly distinguish revoke/recreate from true rotation where applicable.
- [ ] Existing core auth, OAuth, portal, and gateway happy paths still work.

---

### 7. Plan Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Rate-limit tests accidentally use the same browser/session state and hide a mutation | High | Capture before/after counts for the exact target entity and use explicit request ids. |
| Log inspection leaks raw test credentials into QA evidence | High | Store only presence/absence conclusions and safe identifiers, not full request bodies or secrets. |
| Gateway smoke depends on timing around JWKS TTL | Medium | Use a known short TTL and include one extra second beyond the configured value before checking stale rejection. |
| Key rotation checks affect shared local data | Medium | Use isolated local data or a disposable Compose stack, and record only `kid`/status/timestamp evidence. |
| CI behavior differs from local Docker runtime | Medium | Treat CI workflow review and local smoke as separate checks, and compare environment assumptions. |

### 8. Checklist

| Check | Priority |
|-------|----------|
| Confirm all named limiters exist and are attached to intended routes | High |
| Confirm API/OAuth throttles return `429` JSON with `request_id` | High |
| Confirm portal throttles redirect with error and flashed `request_id` | High |
| Confirm exceeded limits do not create/revoke/verify/link/unlink state | High |
| Confirm invalid limiter env values fall back safely and log only safe config diagnostics | Medium |
| Confirm logs include `request_id` and safe identifiers across changed flows | High |
| Confirm logs and smoke failures redact raw secrets | High |
| Confirm key prepare/activate publishes next/active keys correctly | High |
| Confirm retire-expired blocks non-expiring token keys | High |
| Confirm force-retire and rollback clear JWKS cache and update publication | High |
| Confirm gateway validates happy path and strips spoofed `X-Idshka-*` headers | High |
| Confirm gateway rejects expired, not-before, wrong audience, invalid signature, unknown `kid`, and JWKS unavailable cases | High |
| Confirm runbooks preserve safe evidence rules | Medium |
| Confirm CI workflow contains required gates and least-privilege permissions | Medium |
| Confirm README/docs navigation points to new runbooks | Low |
