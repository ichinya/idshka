---
artifact_type: spec
plan_id: "04-token-issuer-and-jwks"
title: "Token issuer and JWKS"
artifact_status: archived
owner: aif-verify
created_at: 2026-04-23
updated_at: 2026-04-26
source_issue: null
source_plan: "04-token-issuer-and-jwks"
---

# Spec: 04-token-issuer-and-jwks

> Finalized specification archived from plan verification.

## Summary

Delivered the API-only issuer slice for `idshka.ru`: user API token issuance for verified owned `api_resource` sites, public JWKS publication, signing key persistence, token metadata persistence, idempotent revoke, issuer audit events, dedicated rate limits, and feature/unit coverage.

## Status

| Field | Value |
|-------|-------|
| **Completed** | 2026-04-26 |
| **Verdict** | pass-with-notes |
| **Files Changed** | 46 |
| **Tests Added** | 4 |

## Implementation

### Scope Delivered

- [x] `lcobucci/jwt` dependency and issuer configuration.
- [x] Auth contracts for JWT claims, headers, scopes, and permissions.
- [x] `signing_keys`, `api_tokens`, and `revoked_jti` schema and models.
- [x] `SigningKeyService`, `JwksService`, `TokenIssuer`, `IssueUserApiTokenAction`, and `RevocationService`.
- [x] `POST /api/v1/user/api-tokens`.
- [x] `POST /api/v1/user/api-tokens/{id}/revoke`.
- [x] Public `GET /oauth/jwks.json`.
- [x] Audit listener/events, rate limiters, docs sync, and tests.

### Key Changes

| File | Change Type | Description |
|------|-------------|-------------|
| `composer.json` | modified | Added `lcobucci/jwt`. |
| `config/issuer.php` | created | Issuer, key, JWKS, revoke, and API resource configuration. |
| `app/Contracts/Auth/*` | created | JWT claims/header/scope/permission contracts. |
| `app/Domain/Issuer/*` | created | Issuer models, services, action, events, DTOs, and exceptions. |
| `app/Domain/ApiResources/*` | created | API resource access resolver and eligibility contract. |
| `app/Http/Controllers/Api/Issuer/*` | created | Token issue and revoke HTTP endpoints. |
| `app/Http/Controllers/OAuth/PublicJwksController.php` | created | Public JWKS endpoint. |
| `database/migrations/2026_04_23_130*.php` | created | Issuer persistence schema. |
| `routes/api.php`, `routes/oauth.php` | modified | Registered token and JWKS routes. |
| `tests/Feature/IssuerApiFlowTest.php` | created | End-to-end issuer API flow coverage. |
| `tests/Unit/Auth/JwtClaimsTest.php` | created | Claims payload coverage. |
| `tests/Unit/Issuer/JwksServiceTest.php` | created | JWKS public-key shape coverage. |
| `tests/Unit/Issuer/RevocationServiceTest.php` | created | Revoke idempotency and DB-first denylist coverage. |
| `docs/API_FLOWS.md`, `docs/GATEWAY_CONTRACT.md`, `docs/LARAVEL_MODULES.md` | modified | Synced issuer/JWKS behavior with external contracts. |

### Dependencies Added

| Package | Version | Purpose |
|---------|---------|---------|
| `lcobucci/jwt` | `^5.6` | JWT creation and signing. |

## Verification

### Final Results

| Check | Status | Notes |
|-------|--------|-------|
| Task Completeness | PASS | T1-T10 complete. |
| Rules Compliance | PASS | Issuer/security/Laravel rules satisfied. |
| Code Quality | PASS | Composer validation, tests, Pint, Vite build, Compose config, and `git diff --check` passed. |
| Architecture | PASS | Domain logic stays in `app/Domain/*`; controllers remain thin. |
| Documentation | PASS with notes | User-facing docs are synced; roadmap context still has stale plan-04 language. |

### Findings Resolved

| ID | Severity | Issue | Resolution |
|----|----------|-------|------------|
| I001 | important | Earlier issuer verification gaps around real site IDs, validation error shape, docs sync, and whitespace. | Fixed in `.ai-factory/plans/04-token-issuer-and-jwks/fixes/2026-04-23-16.58-issuer-verification-gaps.md`. |
| I002 | important | Revoke lifecycle needed owner-scoped lookup and DB-first denylist behavior. | Fixed in `fix(issuer): harden token revoke lifecycle`; tests cover foreign token concealment and cache-write failure. |

### Findings Accepted

| ID | Severity | Issue | Reason for deferral |
|----|----------|-------|---------------------|
| N001 | note | `.ai-factory/ROADMAP.md` still describes plan 04 as pending in some sections. | Context drift only; implementation and docs contracts pass. Refresh with `$aif-roadmap`. |
| N002 | note | `references/CONTEXT-GATES-AND-OWNERSHIP.md` was not found. | Ownership gates were checked from available project rules and skill instructions. Restore the reference if the workflow requires it. |

## Decisions Made

1. **Short-lived direct JWT** - User API tokens use short TTL with optional `jti` denylist instead of a heavier opaque-token exchange.
2. **Public-only JWKS** - JWKS publishes active/next public keys only and fails closed when active signing key state is invalid.
3. **DB-first revoke** - Revoke writes durable DB state before optional Redis denylist caching.

## References

| Type | Reference |
|------|-----------|
| Original Plan Folder | `.ai-factory/plans/04-token-issuer-and-jwks/` |
| Original Plan File | `.ai-factory/plans/04-token-issuer-and-jwks.md` |
| Verification Report | `.ai-factory/specs/04-token-issuer-and-jwks/verify.md` |
| Branch | `main` |

---

*Archived: 2026-04-26*
*Duration: 2026-04-23 - 2026-04-26*
