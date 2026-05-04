## Change Summary

**Commits:** 0 committed changes on `feat/security-hardening-and-ops`; analysis covers current working tree.
**Changed files:** 85 working-tree entries: 64 tracked modified, 21 untracked.
**Risk level:** 🔴 High

---

### What Changed

Implemented production-oriented security hardening and operations coverage for `idshka.ru`: configurable rate limits, deterministic throttling responses, secret-safe request-correlated logging, issuer signing-key lifecycle operations, OpenResty gateway stale-key fail-closed behavior, CI security gates, and operator runbooks.

The change affects identity, issuer, OAuth/OIDC, portal, gateway, CI, docs, OpenSpec artifacts, and QA evidence. It is security-sensitive because it changes rejection behavior, logging behavior, key publication/retirement behavior, and gateway trust boundaries.

---

### Affected Areas

| Component | Change type | Description |
|-----------|-------------|-------------|
| OpenSpec artifacts | Changed | `08-security-hardening-and-ops` proposal/design/spec/tasks now describe rate limits, safe logs, key rotation, runbooks, CI gates, and gateway stale-key policy. |
| Generated rules | Changed | OpenSpec-derived rule files were regenerated for the hardening scope. |
| Laravel rate limits | Added | New `config/security.php` env-backed limits and named Laravel limiters for auth, Socialite, OAuth, JWKS, token issue/revoke, site registry, portal writes, client writes, redirect URI writes, and verification checks. |
| HTTP throttling behavior | Changed | JSON/API/OAuth throttles return deterministic `429` with `request_id`; portal form throttles redirect back with errors and flashed `request_id`. |
| Secret-safe logging | Changed | New `App\Support\SafeLogContext` redacts secret-like keys and injects current request id where available; auth, issuer, portal, audit, Socialite, site verification, and gateway contexts were updated. |
| Issuer signing keys | Added | `SigningKeyService` now supports prepare-next, activate-next, newest-active signing, JWKS publication windows, retire-expired, non-expiring-token blockers, force-retire, rollback, status listing, and JWKS cache invalidation. |
| Operator commands | Added | `idshka:keys:*` commands expose safe key lifecycle operations and avoid printing private material. |
| OpenResty gateway | Changed | JWKS cache stores `cached_at`/`expires_at`, refreshes unknown `kid`, deletes expired keys, fails closed on JWKS errors, and logs safe `request_id`/`kid` context. |
| Gateway smoke | Changed | Smoke flow now checks cache TTL expiry, unknown `kid`, JWKS unavailable fail-closed behavior, request id propagation, header sanitization, and raw token leak prevention. |
| Documentation | Added/Changed | New `docs/OPERATIONS.md` and `docs/SECURITY_RUNBOOK.md`; README/docs/API/gateway docs link to backup, restore, incident, and gateway trust procedures. |
| CI workflow | Changed | Workflow now has least-privilege permissions, dependency audits, route/config smoke, hardening checks, Docker Compose validation, ingress smoke, and gateway smoke. |
| QA evidence | Added | Verification report exists under `.ai-factory/qa/08-security-hardening-and-ops/verify.md`; this QA packet is branch-scoped. |

---

### Risks

🔴 **Critical** (must verify):

- Rate-limit boundaries may reject legitimate security-sensitive flows or fail to block mutation after the limit is exceeded.
- Throttling responses may leak submitted credentials, authorization codes, PKCE verifiers, JWTs, client secrets, or private key material through logs, errors, session flashes, or smoke failure output.
- Signing-key lifecycle mistakes can break all token issuance, remove active JWKS keys too early, or keep compromised keys published after force retirement.
- Gateway stale-key behavior can accidentally trust a retired key after TTL expiry or fail open when JWKS is unavailable.
- `APP_KEY`, encrypted Socialite token data, signing private material, and restore evidence have high blast radius if operational docs or incident procedures encourage unsafe handling.

🟡 **Medium** (should verify):

- Existing login/register, Socialite, OAuth authorize/token/userinfo, portal credential, site verification, and API token revoke flows may regress because middleware and logging were touched broadly.
- Invalid rate-limit env values may behave differently in local and production config cache.
- CI additions can fail because of environment drift, dependency audit instability, Docker availability, or gateway timing.
- Gateway cache TTL checks are time-sensitive and can be flaky if the runtime clock, env var, or Compose service startup is inconsistent.
- Generated OpenSpec rule artifacts changed and should stay aligned with canonical specs.

🟢 **Low** (nice to verify):

- Documentation navigation links may drift if docs pages move.
- Safe log context may over-redact harmless diagnostic values, reducing operational usefulness.
- Operator command wording and status rows may be confusing for on-call users even when behavior is correct.

---

### Testing Recommendations

**First priority:**

- [ ] Verify that every security-sensitive endpoint has the intended named limiter and that rejected requests do not mutate users, sessions, tokens, clients, redirect URIs, verification status, or revoke state.
- [ ] Verify that JSON/API/OAuth throttles and portal form throttles expose `request_id` without exposing submitted secrets.
- [ ] Verify key prepare, activate, retire, force-retire, rollback, JWKS publication, and non-expiring token blocker behavior from an operator perspective.
- [ ] Verify gateway valid-token, spoofed-header, cache TTL, unknown `kid`, expired/not-before token, invalid signature, wrong audience, and JWKS unavailable behavior.
- [ ] Verify incident and restore runbooks using only safe identifiers in evidence.

**Regression:**

- [ ] Re-check existing email/password login/register/logout.
- [ ] Re-check Socialite redirect/callback/link/unlink behavior.
- [ ] Re-check OAuth authorize/token/userinfo/JWKS behavior for normal valid flows.
- [ ] Re-check portal site/client/token/redirect URI management.
- [ ] Re-check API resource gateway upstream context headers for valid users.
