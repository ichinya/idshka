---
artifact_type: spec
plan_id: "05-api-resource-gateway-for-apishka"
title: "API resource gateway for Apishka"
artifact_status: archived
owner: aif-verify
created_at: 2026-04-25
updated_at: 2026-04-26
source_issue: null
source_plan: "05-api-resource-gateway-for-apishka"
---

# Spec: 05-api-resource-gateway-for-apishka

> Finalized specification archived from plan verification.

## Summary

Implemented an OpenResty gateway reference for `api.apishka.ru`: local RS256 JWT validation against Laravel JWKS, fail-closed token and claim checks, sanitization of client-supplied `X-Idshka-*`, trusted context header injection, minimal internal `apishka-api` upstream, reproducible gateway smoke script, CI smoke integration, and documentation sync.

## Status

| Field | Value |
|-------|-------|
| **Completed** | 2026-04-26 |
| **Verdict** | pass-with-notes |
| **Files Changed** | 22 |
| **Tests Added** | 1 |

## Implementation

### Scope Delivered

- [x] Added explicit OpenResty runtime dependency on `openssl` for RS256 verification.
- [x] Replaced placeholder gateway behavior with protected proxy flow through `access_by_lua_block`.
- [x] Added JWKS fetch/cache module with `kid` selection and fail-closed outage behavior.
- [x] Added JWT validation for Bearer format, `alg`, `kid`, signature, `iss`, `aud`, `exp`, `nbf`, `sub`, `site_id`, `token_type`, `scope`, `permissions`, and `jti`.
- [x] Added trusted context header injection after clearing incoming `X-Idshka-*` and `Authorization`.
- [x] Added internal `apishka-api` echo upstream for smoke verification.
- [x] Added gateway smoke coverage for valid token, missing token, invalid signature, wrong audience, expired/not-before token, raw token leak prevention, and header sanitization.
- [x] Added CI gateway smoke step and synced README/docs.
- [x] Deferred signed context and edge revoke cache to later hardening phases, as documented.

### Key Changes

| File | Change Type | Description |
|------|-------------|-------------|
| `infra/openresty/apishka/Dockerfile` | created | Pins the OpenResty image path and installs `openssl` explicitly. |
| `infra/openresty/apishka/nginx.conf` | modified | Defines `/healthz`, internal JWKS subrequest, JWT validation, trusted header injection, and proxying to `apishka-api`. |
| `infra/openresty/apishka/lua/jwks_cache.lua` | created | Fetches and caches public JWKS keys by `kid`. |
| `infra/openresty/apishka/lua/jwt_validate.lua` | created | Validates JWT structure, signature, issuer, audience, required claims, and validity windows. |
| `infra/openresty/apishka/lua/context_headers.lua` | created | Sanitizes spoofed auth context and writes gateway-generated `X-Idshka-*` headers. |
| `examples/apishka-api/nginx.conf` | created | Provides an internal upstream endpoint for smoke tests. |
| `infra/openresty/apishka/smoke.sh` | created | Runs the reproducible gateway contract smoke flow. |
| `routes/console.php` | modified | Adds local-only smoke token issuance command. |
| `compose.yml` | modified | Builds the gateway image and adds internal `apishka-api`. |
| `.github/workflows/ci.yml` | modified | Adds the gateway smoke step to CI. |
| `docs/API_FLOWS.md`, `docs/GATEWAY_CONTRACT.md`, `docs/README.md` | modified | Documents implemented gateway behavior and deferred hardening. |

### Dependencies Added

| Package | Version | Purpose |
|---------|---------|---------|
| `openssl` | Alpine package | Gateway runtime RS256 signature verification. |

## Verification

### Final Results

| Check | Status | Notes |
|-------|--------|-------|
| Task Completeness | PASS | T1..T9 verified against implementation and docs. |
| Rules Compliance | PASS | Fail-closed behavior, trusted header boundary, deterministic errors, and no raw token propagation are covered. |
| Code Quality | PASS | Pint, Composer validation, Laravel tests, Vite build, Compose config, and whitespace checks passed. |
| Architecture | PASS | Gateway and examples remain external consumers under `infra/` and `examples/`; Laravel core is only used through HTTP/JWKS and a local smoke command. |
| Documentation | PASS | Gateway docs, API flows, README, Lua README, and example upstream README match the implemented reference. |

### Fresh Evidence

| Command | Result |
|---------|--------|
| `php vendor/bin/pint --test` | passed |
| `composer validate --strict` | passed |
| `php artisan test --without-tty` | passed: 43 tests, 324 assertions |
| `npm run build` | passed |
| `docker compose config` | passed |
| `git diff --check` | passed with only CRLF normalization warnings in existing markdown files |
| changed-file unfinished marker scan | no matches |
| `docker compose -p idshka_aif_verify up -d --build` | passed |
| `bash infra/openresty/apishka/smoke.sh "docker compose -p idshka_aif_verify"` | passed |
| `docker compose -p idshka_aif_verify down -v` | cleanup completed |

### Findings Accepted

| ID | Severity | Issue | Reason for deferral |
|----|----------|-------|---------------------|
| O001 | optional | Signed context is not implemented in this slice. | Plan T6 allowed explicit deferral; docs route it to future hardening. |
| O002 | optional | Edge revoke cache / online introspection are not implemented in gateway. | This is documented as later security-hardening work. |
| O003 | note | Working tree also contains prerequisite phase 04 changes. | Verification scoped plan 05 separately and did not revert prerequisite changes. |

## Decisions Made

1. **Use local JWKS verification at the edge** because API-only consumers should not trust raw public JWTs directly.
2. **Use explicit `openssl` runtime verification** to avoid relying on accidental Lua crypto modules in the base image.
3. **Keep `apishka-api` internal-only** so the example demonstrates the trusted gateway boundary.
4. **Defer signed context and revoke cache** because the current Compose topology has a private network boundary and the hardening is already planned later.

## Lessons Learned

### What Went Well

- The gateway contract became executable through a Docker Compose smoke path.
- The smoke script proves the main security boundary: fail-closed JWT validation and spoofed header replacement.

### What to Improve

- Add explicit smoke cases for unknown `kid`, unsupported `alg`, wrong `iss`, and missing critical claims when hardening coverage expands.
- Add a production-oriented gateway config templating strategy before moving beyond the local reference.

## References

| Type | Reference |
|------|-----------|
| Original Plan | `.ai-factory/plans/05-api-resource-gateway-for-apishka/` |
| Plan File | `.ai-factory/plans/05-api-resource-gateway-for-apishka.md` |
| Issue | `n/a` |
| PR | `n/a` |
| Branch | `feature/05-api-resource-gateway-for-apishka` |

---

*Archived: 2026-04-26*
*Duration: 2026-04-25 - 2026-04-26*
