---
artifact_type: spec
plan_id: "02-user-auth-socialite"
title: "User auth Socialite"
artifact_status: archived
owner: aif-verify
created_at: 2026-04-23
updated_at: 2026-04-23
source_issue: null
source_plan: "02-user-auth-socialite"
---

# Spec: 02-user-auth-socialite

> Finalized specification archived from plan verification.

## Summary

Implemented first-party Laravel session auth and external Socialite login for `idshka.ru`: registration, login, logout, Socialite redirect/callback, account link/unlink, Google/VK/Yandex provider configuration, encrypted provider-token storage, and login audit events.

## Status

| Field | Value |
|-------|-------|
| **Completed** | 2026-04-23 |
| **Verdict** | pass |
| **Files Changed** | 45 |
| **Tests Added** | 8 |

## Implementation

### Scope Delivered

- [x] Added `social_accounts` migration with provider identity uniqueness and per-user provider uniqueness.
- [x] Added session auth endpoints: `POST /register`, `POST /login`, `POST /logout`.
- [x] Added Socialite login endpoints: `/auth/{provider}/redirect` and `/auth/{provider}/callback`.
- [x] Added account linking/unlinking endpoints for authenticated users.
- [x] Added Google, VKontakte, and Yandex provider configuration/adapters.
- [x] Added encrypted provider access/refresh token persistence.
- [x] Added identity audit events and listener registration.
- [x] Added feature tests for public auth and Socialite flows.

### Key Changes

| File | Change Type | Description |
|------|-------------|-------------|
| `database/migrations/2026_04_23_120000_create_social_accounts_table.php` | created | Added social identity persistence and unique constraints. |
| `app/Domain/Identity/*` | created | Added Socialite domain model, provider enum, actions, events, and profile services. |
| `app/Domain/Audit/Listeners/RecordIdentityAuditEvent.php` | created | Added audit logging listener for identity events. |
| `app/Http/Controllers/Auth/*` | created | Added session auth and Socialite HTTP handlers. |
| `app/Http/Requests/Auth/*` | created | Added registration/login validation requests. |
| `routes/web.php` | modified | Registered auth and Socialite routes with explicit guards/throttles. |
| `config/services.php` | modified | Added Google, VKontakte, and Yandex Socialite service config. |
| `tests/Feature/AuthSocialiteFlowTest.php` | created | Added feature coverage for registration, login, logout, Socialite login, repeated login reuse, link/unlink, unsupported providers, and token log safety. |
| `tests/Feature/FoundationSmokeTest.php` | modified | Added request-id sanitization regression coverage after shared string-normalization fix. |

### Dependencies Added

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/socialite` | v5.26.1 | OAuth login client integration. |
| `socialiteproviders/vkontakte` | 5.1.0 | VKontakte Socialite provider. |
| `socialiteproviders/yandex` | 4.1.0 | Yandex Socialite provider. |

## Verification

### Final Results

| Check | Status | Notes |
|-------|--------|-------|
| Task Completeness | PASS | All 7 planned outputs implemented. |
| Acceptance Criteria | PASS | Socialite login, uniqueness, repeat sign-in reuse, and token log safety verified. |
| Rules Compliance | PASS | Public auth/Socialite routes have feature tests and no raw provider-token logging. |
| Code Quality | PASS | Composer validation, tests, Pint, PHP syntax, and build passed. |
| Architecture | PASS | Identity logic stays in `App\Domain\Identity`; HTTP controllers remain thin. |
| Roadmap | PASS | Work matches roadmap slice `02-user-auth-socialite`. |

### Findings Resolved

| ID | Severity | Issue | Resolution |
|----|----------|-------|------------|
| V001 | high | Public auth/Socialite endpoints lacked feature/smoke tests. | Added `AuthSocialiteFlowTest` with public auth, Socialite, reuse, link/unlink, unsupported provider, and token safety coverage. |
| V002 | medium | Socialite tests exposed `Str::limit(...)->toString()` runtime crash. | Replaced direct `Str::limit()` usage with string return handling in affected code. |
| V003 | low | Pint formatting blocked final verification. | Formatted `AuthSocialiteFlowTest.php` and authorized `app/Domain/Sites` Pint drift. |

## Decisions Made

1. **Use mocked Socialite providers in feature tests** to prove flow behavior deterministically without external OAuth calls.
2. **Do not silently merge existing users by matching Socialite email** because account linking policy must be explicit.
3. **Store provider tokens encrypted and assert they are not logged** to satisfy security rules.
4. **Keep Socialite limited to external login** and leave issuer/JWKS/token-server work for later roadmap slices.

## Lessons Learned

### What Went Well

- Feature tests caught a real Laravel string-handling bug before finalization.
- The Identity domain remained separate from issuer/token concerns.
- Audit events are registered and visible through Laravel event discovery.

### What to Improve

- Run repository-wide Pint before `$aif-verify` to avoid format-only verification failures.
- Add feature tests in the same implementation pass as public auth endpoints.

## References

| Type | Reference |
|------|-----------|
| Original Plan | `.ai-factory/plans/02-user-auth-socialite/` |
| Plan File | `.ai-factory/plans/02-user-auth-socialite.md` |
| Issue | `n/a` |
| PR | `n/a` |
| Branch | `main` |

---

*Archived: 2026-04-23*
*Duration: 2026-04-23 - 2026-04-23*