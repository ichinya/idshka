---
artifact_type: spec
plan_id: "03-site-registry-and-modes"
title: "Site registry and modes"
artifact_status: archived
owner: aif-verify
created_at: 2026-04-22
updated_at: 2026-04-22
source_issue: null
source_plan: "03-site-registry-and-modes"
---

# Spec: 03-site-registry-and-modes

> Finalized specification archived from plan verification.

## Summary

Implemented site onboarding for `idshka.ru`: site persistence, domain verification (DNS TXT + well-known file), explicit mode enablement (`api_resource`, `web_client`), centralized fail-closed verified-site guard, audit events, and feature tests for owner/security boundaries.

## Status

| Field | Value |
|-------|-------|
| **Completed** | 2026-04-22 |
| **Verdict** | pass |
| **Files Changed** | 42 |
| **Tests Added** | 11 |

## Implementation

### Scope Delivered

- [x] Added `sites`, `site_verifications`, `site_modes` migrations with indexes and ownership constraints
- [x] Added `App\\Domain\\Sites` models, enums, actions, services, and contracts
- [x] Added API endpoints for site creation, domain verification, and mode enablement
- [x] Added centralized verified-site lookup guard for production-credential gating
- [x] Added audit events/listener integration for site registry actions
- [x] Updated API flow docs for mode endpoints and responses
- [x] Added feature tests for success and fail-closed behavior

### Key Changes

| File | Change Type | Description |
|------|-------------|-------------|
| `database/migrations/2026_04_22_230000_create_sites_table.php` | created | Created `sites` table with domain uniqueness and verification status. |
| `database/migrations/2026_04_22_230100_create_site_verifications_table.php` | created | Added verification token/TTL/status storage for DNS/file methods. |
| `database/migrations/2026_04_22_230200_create_site_modes_table.php` | created | Added explicit site mode enablement table with idempotent uniqueness. |
| `app/Domain/Sites/*` | created | Implemented domain logic for normalization, verification checks, mode gating, and contracts. |
| `app/Http/Controllers/Api/Sites/*` | created | Added HTTP handlers for create/verify/mode flows with deterministic error payloads. |
| `routes/api.php` | modified | Registered `/api/v1/sites`, `/api/v1/sites/{site}/verify`, `/api/v1/sites/{site}/modes/{mode}` routes. |
| `tests/Feature/SiteRegistryApiTest.php` | created | Added end-to-end feature coverage for owner boundaries and fail-closed behavior. |

### Dependencies Added

| Package | Version | Purpose |
|---------|---------|---------|
| - | - | No new Composer dependencies were required. |

## Verification

### Final Results

| Check | Status | Notes |
|-------|--------|-------|
| Task Completeness | PASS | T1..T10 implemented and validated via code/tests/docs. |
| Rules Compliance | PASS | Laravel-first, fail-closed, contracts-first, audit-by-default preserved. |
| Code Quality | PASS | `composer validate`, `php artisan test`, `pint --test` passed. |
| Architecture | PASS | Changes stay inside modular boundaries (`Domain` + thin API controllers). |
| Documentation | PASS | `docs/API_FLOWS.md` updated for mode enable endpoints. |

### Findings Resolved

| ID | Severity | Issue | Resolution |
|----|----------|-------|------------|
| V001 | important | Verify scope was ambiguous using only `main...HEAD` | Verification switched to tracked+untracked working-tree scope. |

## Decisions Made

1. **Use working-tree verification scope** because implementation is uncommitted yet and `main...HEAD` returned empty.
2. **Keep verified-site eligibility centralized** through `VerifiedSiteLookup` to enforce fail-closed behavior across downstream modules.

## Lessons Learned

### What Went Well

- Domain slice for Sites was implemented with clear action/service/controller boundaries.
- Feature tests provide direct evidence for acceptance and security gates.

### What to Improve

- Keep plan file acceptance checkboxes synchronized automatically during implement runs to reduce manual drift.

## References

| Type | Reference |
|------|-----------|
| Original Plan | `.ai-factory/plans/03-site-registry-and-modes/` |
| Plan File | `.ai-factory/plans/03-site-registry-and-modes.md` |
| Issue | `n/a` |
| PR | `n/a` |
| Branch | `feat/03-site-registry-and-modes` |

---

*Archived: 2026-04-22*
*Duration: 2026-04-22 - 2026-04-22*
