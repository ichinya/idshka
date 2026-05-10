# Tasks

## Settings

- Testing: yes
- Logging: minimal for page reads, preserve existing safe structured logging for mutations
- Docs: yes
- Runtime State: `.ai-factory/state/09-portal-frontend-redesign/`
- QA Evidence: `.ai-factory/qa/09-portal-frontend-redesign/`

## 1. Planning and validation

- [x] 1.1 Confirm this OpenSpec change validates before product edits and keep `proposal.md`, `design.md`, `tasks.md`, and `specs/portal-frontend-redesign/spec.md` aligned with the intended Blade/Tailwind refactor.
- [x] 1.2 Review existing portal behavior in `routes/web.php`, `app/Http/Controllers/Portal/PortalController.php`, `resources/views/portal/dashboard.blade.php`, `tests/Feature/PortalManagementFlowTest.php`, `tests/Feature/SecurityRateLimitTest.php`, and `tests/Feature/AuthSocialiteFlowTest.php` before changing routes.
- [x] 1.3 Preserve existing backend domain actions, issuer endpoints, Socialite flows, gateway contract, named rate limits, and one-time secret behavior as non-negotiable compatibility constraints.

## 2. Portal shell and shared components

- [x] 2.1 Add `resources/views/layouts/portal.blade.php` with responsive sidebar/topbar slots, current workspace highlighting, flash notices, validation errors, logout action, and Vite asset loading.
- [x] 2.2 Add reusable Blade components under `resources/views/portal/components/` for sidebar, topbar, page header, card, status badge, empty state, code snippet, copy button, and warning callout.
- [x] 2.3 Move inline portal JavaScript into `resources/js/app.js` using `data-*` hooks for mobile navigation, copy buttons, and redirect URI autofill so the current `script-src 'self'` CSP remains valid.
- [x] 2.4 Keep Tailwind styling quiet, dense, and utilitarian: no SPA framework, no nested page cards, no oversized marketing hero, and stable table/card dimensions for mobile and desktop.

## 3. Route and controller split

- [x] 3.1 Refactor `/portal` routes into Account, Developer, and Audit GET route groups while making `/portal` redirect to `/portal/account`.
- [x] 3.2 Preserve current write route names or add compatibility aliases for `portal.sites.store`, `portal.sites.verify`, `portal.sites.modes.store`, `portal.api-tokens.store`, `portal.api-tokens.revoke`, `portal.clients.store`, `portal.clients.redirect-uris.store`, and `portal.clients.revoke`.
- [x] 3.3 Split read controllers into focused Account, Developer, and Audit controllers, keeping business rules in existing domain actions and avoiding a new monolithic portal controller.
- [x] 3.4 Update login/register redirect targets in `LoginController` and `RegisterUserController` only if route names change; browser tests must still prove successful auth lands in the portal.

## 4. Account workspace

- [x] 4.1 Add Account overview at `resources/views/portal/account/overview.blade.php` with profile summary, linked provider count, active token count, owned/connected site summary, and recent account events.
- [x] 4.2 Add Social accounts page showing configured Google, VK, and Yandex provider states, existing link/unlink actions, and an empty state when no providers are linked.
- [x] 4.3 Add Sessions page using the existing `sessions` table where reliable; otherwise show current-session metadata plus a clear empty state for broader device management.
- [x] 4.4 Add Account tokens page for user API token creation, listing, and revoke, preserving one-time raw token flash display and later metadata-only lists.

## 5. Developer workspace

- [x] 5.1 Add Developer overview and site list pages showing owned sites, verification status, mode badges for API-only/Web Login/Both, credential counts, and empty states.
- [x] 5.2 Add site create/show pages that reuse `CreateSiteAction` behavior and display DNS TXT plus well-known file verification instructions.
- [x] 5.3 Add verification page that calls the existing verification action, handles expired challenge retry messaging, and preserves owner fail-closed behavior.
- [x] 5.4 Add credentials page for OIDC clients with create/revoke UI, one-time client secret warning, active/revoked status, and no later raw secret display.
- [x] 5.5 Add redirect URI page that lists exact redirect URIs and reuses existing redirect URI validation and creation behavior.
- [x] 5.6 Add gateway guide page with Authorization header usage, JWKS URL, trusted `X-Idshka-*` header contract, and a warning that upstream apps must not trust client-supplied identity headers.
- [x] 5.7 Add web-login guide page documenting `/oauth/authorize`, `/oauth/token`, `/oauth/jwks.json`, `/oauth/userinfo`, PKCE, state/nonce validation, and local session creation after callback.

## 6. Audit workspace

- [x] 6.1 Add Audit list page with filters for date range, category/event type, site, actor/user, and severity if severity is derivable from existing event data.
- [x] 6.2 Scope audit results to events for the authenticated user or sites owned by the authenticated user.
- [x] 6.3 Add Audit detail page with escaped metadata JSON, event timestamps, user/site context, category/action, summary, and a not-found response for foreign events.
- [x] 6.4 Add empty state for no audit events and no filter matches.

## 7. Tests and verification

- [x] 7.1 Update portal feature tests for guest redirects, `/portal` redirect, Account pages, Developer pages, Audit pages, and owner-scoped site/audit access.
- [x] 7.2 Preserve and update existing full portal management flow tests for site creation, verification, mode enablement, API token issue/revoke, OIDC client creation/revoke, redirect URI creation, and audit visibility.
- [x] 7.3 Preserve and update rate-limit tests so throttled portal writes still fail before credential, redirect URI, verification, token, client, or revoke state changes.
- [x] 7.4 Add assertions that raw API tokens and raw client secrets are displayed only immediately after creation and are not shown on later GET pages.
- [x] 7.5 Run `composer validate --strict`, `php artisan test --without-tty`, `npm run build`, `git diff --check`, and `openspec validate 09-portal-frontend-redesign --type change --strict --json --no-interactive --no-color`.

## Commit Plan

- Checkpoint 1: OpenSpec and route shell. Suggested commit: `feat: define portal workspace redesign`
- Checkpoint 2: Account and Developer workspaces. Suggested commit: `feat: split portal account and developer workspaces`
- Checkpoint 3: Audit workspace and tests. Suggested commit: `feat: add portal audit workspace`
- Final checkpoint: verification and polish. Suggested commit: `test: cover redesigned portal flows`
