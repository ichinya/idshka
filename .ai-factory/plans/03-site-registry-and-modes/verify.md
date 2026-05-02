# Verify

- [x] Creating `example.test` returns `201` with DNS + file verification instructions.
- [x] Re-registering a verified domain by another owner is rejected (fail closed).
- [x] `POST /api/v1/sites/{site}/verify` with `dns_txt` sets status to `verified` when record matches.
- [x] `POST /api/v1/sites/{site}/verify` with `file` sets status to `verified` when file body matches.
- [x] Expired challenge does not verify domain and returns deterministic error.
- [x] `api_resource` and `web_client` can be enabled only after verification.
- [x] Unverified site cannot receive production credentials (domain guard).
- [x] Feature tests cover owner boundaries, validation, and HTTP `401/403/422` behavior.

## Result

- Verdict: `PASS`
- Verified at: `2026-04-22`
- Checks:
  - `composer validate --no-check-publish`
  - `php artisan test`
  - `php vendor/bin/pint --test` on changed PHP files
- Notes:
  - `git diff main...HEAD` was empty because changes are uncommitted; verification scope was derived from tracked + untracked working tree files.

## Re-verify After aif-fix (`2026-04-22`)

- [x] Route hardening applied: `web` + `auth:web` + `throttle:site-registry`, plus `can:manage,site` on site-bound endpoints.
- [x] Verification race guard applied: fail-closed on conflicting verified owner inside transaction.
- [x] File verification SSRF guard applied: public-IP-only resolution + redirect hardening (`withoutRedirecting`), with testing environment compatibility.
- [x] Quality gates passed after fixes:
  - `composer validate --no-check-publish`
  - `php artisan test`
  - `php vendor/bin/pint --test` on changed PHP files
