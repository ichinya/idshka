# Design: 09-portal-frontend-redesign

## Current Implementation

Portal browser functionality currently lives mostly in:

- `routes/web.php`
- `app/Http/Controllers/Portal/PortalController.php`
- `resources/views/portal/dashboard.blade.php`
- `tests/Feature/PortalManagementFlowTest.php`
- `tests/Feature/SecurityRateLimitTest.php`
- `tests/Feature/AuthSocialiteFlowTest.php`

The dashboard loads owned sites, API tokens, OIDC clients, redirect URIs, and recent audit events in one controller action. Existing POST routes already call the correct domain actions for site creation, verification, mode enablement, API token issue/revoke, client creation/revoke, and redirect URI creation. The refactor should preserve those action boundaries and move page composition into focused controllers and views.

## Route Model

Target route structure:

- `GET /portal` redirects to `GET /portal/account`.
- `GET /portal/account` shows the Account overview.
- `GET /portal/account/social` shows linked Socialite providers and provider actions.
- `GET /portal/account/sessions` shows current/known sessions from the existing `sessions` table where available.
- `GET /portal/account/tokens` shows user API tokens and token creation/revoke UI.
- `GET /portal/developer` shows developer overview.
- `GET /portal/developer/sites` lists owned sites.
- `GET /portal/developer/sites/create` shows site creation UI.
- `GET /portal/developer/sites/{site}` shows owned site overview.
- `GET /portal/developer/sites/{site}/verification` shows DNS TXT and well-known file instructions and verification actions.
- `GET /portal/developer/sites/{site}/credentials` shows web client credentials, one-time secret flash messages, and revoke UI.
- `GET /portal/developer/sites/{site}/redirect-uris` shows redirect URI management.
- `GET /portal/developer/sites/{site}/gateway` shows API-only gateway snippets and trusted header contract.
- `GET /portal/developer/sites/{site}/web-login` shows Authorization Code + PKCE integration snippets.
- `GET /portal/audit` shows filterable audit events.
- `GET /portal/audit/{event}` shows an owner-scoped event detail page.

Existing write routes should remain available by their current names until all forms and tests are migrated:

- `portal.sites.store`
- `portal.sites.verify`
- `portal.sites.modes.store`
- `portal.api-tokens.store`
- `portal.api-tokens.revoke`
- `portal.clients.store`
- `portal.clients.redirect-uris.store`
- `portal.clients.revoke`

When redirect targets move, browser flows should land on the closest workspace page, not the old dashboard.

## Controller Boundaries

Read controllers should prepare page data and enforce ownership:

- Account controllers read the authenticated user, social accounts, session rows, user API tokens, and user-visible connected applications/sites.
- Developer controllers read owned sites, site modes, verifications, OIDC clients, redirect URIs, and integration snippets.
- Audit controllers read only events where the authenticated user is the actor or where the event belongs to a site owned by the authenticated user.

Mutation controllers should continue to call existing domain actions. If the existing `PortalController` is split, keep behavior equivalent for validation, throttling middleware, one-time flash data, and fail-closed foreign-resource handling.

## View and Component Model

Add a portal layout and shared components:

- `resources/views/layouts/portal.blade.php`
- `resources/views/portal/components/sidebar.blade.php`
- `resources/views/portal/components/topbar.blade.php`
- `resources/views/portal/components/page-header.blade.php`
- `resources/views/portal/components/empty-state.blade.php`
- `resources/views/portal/components/status-badge.blade.php`
- `resources/views/portal/components/card.blade.php`
- `resources/views/portal/components/code-snippet.blade.php`
- `resources/views/portal/components/warning-callout.blade.php`

Use neutral SaaS styling, stable card/table dimensions, status badges for verified/unverified/active/revoked/mode states, and contextual empty states on every list page. Account controls and Developer controls must not share the same page except through high-level navigation.

## JavaScript and CSP

The document CSP is `script-src 'self'`, so portal interactivity must not rely on inline `<script>` blocks. Move small behavior into `resources/js/app.js`, for example:

- Mobile sidebar toggling.
- Copy-to-clipboard buttons for code snippets and ids.
- Redirect URI autofill based on selected site.
- Temporary copied state labels.

Blade views may use `data-*` attributes for hooks. Alpine.js may be introduced only if it is installed and loaded through Vite; otherwise use plain JavaScript.

## Security and Secret Handling

- Raw API tokens and raw client secrets are shown only from flash data immediately after creation.
- Later pages show only token/client metadata, ids, `jti`, expiry, revoke state, redirect URIs, and site context.
- Audit metadata must be escaped. If JSON is pretty-printed, render it through escaped Blade output inside a code block.
- All site-specific developer pages must return not found or forbidden for non-owned sites according to existing project behavior.
- Existing CSRF protection and named rate-limit middleware must remain on all write routes.
- Developer snippets must state that upstream resources must not trust client-supplied `X-Idshka-*` headers.

## Data Considerations

No database migration is required for the core redesign. The sessions page can use the existing `sessions` table created by Laravel. If session listing cannot reliably deserialize session payloads, ship a minimal page that shows current session metadata and an empty state for other sessions, then leave broader session management for a later change.

Audit filtering should use existing indexed columns: `user_id`, `site_id`, `category`, `action`, and `occurred_at`. Avoid JSON metadata filtering unless a later migration adds indexes.

## Test Strategy

Add or update feature tests for:

- Guest portal access redirects to login.
- `/portal` redirects to `/portal/account`.
- Account, Developer, and Audit pages render for authenticated users.
- Developer site pages are owner-scoped.
- Audit list filters do not error and audit detail is owner-scoped.
- One-time API token and client secret display remains one-time.
- Existing portal write flows still create sites, verify sites, enable modes, issue/revoke tokens, create/revoke clients, and add redirect URIs.
- Existing portal rate-limit tests still reject before state mutation.
- Existing OAuth issuer endpoints and Socialite browser redirects still pass.

Run `php artisan test --without-tty`, `npm run build`, `composer validate --strict`, `git diff --check`, and OpenSpec validation before marking the change complete.

## Risks

- Route renaming can break login/register intended redirects and existing form actions.
- Moving dashboard data into multiple pages can accidentally remove owner scoping or one-time secret behavior.
- Inline JavaScript will be blocked by the current CSP.
- Audit detail pages can leak foreign site events if scoping is too broad.
- The sessions page can overpromise if it treats Laravel session payloads as a stable device-management model.
