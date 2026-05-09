# Plan 09 — Portal frontend redesign

## Goal

Refactor the current one-page portal into a polished Laravel Blade/Tailwind portal with three clear workspaces:

1. **Account** — end-user identity area: profile, linked social accounts, sessions, user API tokens, connected apps/sites.
2. **Developer** — website owner area: connect a site, choose API-only or Web Login mode, verify domain, manage credentials, redirect URIs, gateway snippets, scopes and tokens.
3. **Audit** — searchable security and product audit trail for user actions, developer actions and token/site events.

The implementation must preserve the current Laravel backend, issuer routes and domain actions. This plan is primarily a UI/UX and controller/view refactor.

## Current state observed

- Laravel application already has portal, auth, site, OAuth issuer and audit-related code.
- OAuth issuer routes are loaded from `routes/oauth.php` under `/oauth` through `bootstrap/app.php`.
- `GET /oauth/authorize`, `POST /oauth/token`, `GET /oauth/jwks.json`, `GET /oauth/userinfo` are present.
- Socialite-specific controllers exist as separate controllers, so the previous monolithic `SocialiteController` concern is not a blocker.
- The portal is still too concentrated around a dashboard-style page and controller.
- CI is currently red on the inspected `main` commit and must be green before this plan is marked complete.

## Scope

### In scope

- New portal shell: sidebar, topbar, page headers, empty states, cards, badges, alerts.
- Split portal routes into Account, Developer and Audit groups.
- Split portal views into focused Blade files.
- Add dashboard overview pages for each workspace.
- Keep existing forms/actions where possible and move them into the correct workspace.
- Add responsive layout for desktop and mobile.
- Add tests for route availability, auth guards and basic page rendering.

### Out of scope

- Implementing new external social providers from scratch.
- Replacing Laravel Blade with React/Next/Vue.
- Rewriting issuer/token internals.
- Gateway runtime implementation beyond showing developer integration snippets.

## Success criteria

- `/portal` redirects to `/portal/account` or shows a clean workspace selector.
- Account, Developer and Audit are separate navigation areas.
- A user can manage profile/social links/sessions/tokens without seeing developer-only controls.
- A developer can connect and configure `apishka.ru` without mixing this with personal identity screens.
- Audit has its own searchable/filterable page.
- Existing domain actions and issuer endpoints continue to work.
- Tests pass locally and in CI.
