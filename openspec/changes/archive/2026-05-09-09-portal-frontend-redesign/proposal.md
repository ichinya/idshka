# Proposal: 09-portal-frontend-redesign

## Why

The current portal concentrates account identity, connected site management, token/client operations, and audit history into one dashboard page. The backend already has the domain actions, issuer routes, Socialite flows, audit events, and portal mutations needed for the MVP, but the product surface does not clearly separate end-user identity work from developer integration work.

This change turns the portal into a Blade/Tailwind product surface with three workspaces: Account, Developer, and Audit. The refactor must preserve the current Laravel backend behavior, issuer endpoints, token/client secret handling, owner checks, rate limits, and audit events.

## What Changes

- Add a reusable portal shell with sidebar navigation, topbar, page headers, empty states, status badges, cards, and code snippet components.
- Split read-only portal pages into Account, Developer, and Audit route groups while preserving existing portal mutation route names or adding compatibility aliases/redirects where current tests and forms depend on them.
- Move account identity views into an Account workspace for profile, Socialite links, sessions, user API tokens, and authorized/connected sites.
- Move site owner setup into a Developer workspace for connected sites, domain verification, modes, credentials, redirect URIs, gateway snippets, and web-login guidance.
- Move audit history into an Audit workspace with filters, owner-scoped event visibility, and escaped metadata details.
- Keep raw API tokens and client secrets one-time only, with later pages showing only non-secret metadata.
- Add route, ownership, rendering, secret-handling, rate-limit, audit, and existing issuer regression coverage.

## Scope

### In Scope

- Laravel Blade and Tailwind portal views.
- Portal route organization under `/portal/account`, `/portal/developer`, and `/portal/audit`.
- Focused portal controllers or invokable actions for read pages and existing mutations.
- Reuse of existing domain actions for sites, verification, mode enablement, API token issue/revoke, OIDC client creation/revoke, and redirect URI creation.
- CSP-safe JavaScript through `resources/js/app.js` for small interactions such as mobile navigation, copy buttons, and autofilled redirect URI behavior.
- Tests for route availability, auth guards, owner scoping, page rendering, audit filters/details, one-time secrets, and existing OAuth issuer behavior.

### Out of Scope

- Replacing Blade with React, Vue, Next, or another SPA framework.
- Rewriting issuer internals, token format, JWKS, OAuth endpoints, gateway contract, or Socialite backend flows.
- Adding new external Socialite providers from scratch.
- Building a new policy DSL, marketplace, billing, or enterprise provisioning UI.
- Implementing gateway runtime beyond developer-facing snippets and existing documentation links.

## Approach

Use the existing portal dashboard and `PortalController` as migration source material. First introduce the layout and reusable components, then split route groups and read models without changing domain actions. Keep browser redirects stable during the refactor: `/portal` should redirect to `/portal/account`, and existing write routes should continue to resolve or redirect to the new workspace pages.

The implementation should prefer small controllers and shared query helpers over moving business rules into views. Audit metadata must be rendered through escaped Blade output or JSON pretty-printing that is escaped before display. Inline scripts should be removed from Blade because the application document CSP allows only `script-src 'self'`.

## Legacy Source

This change is refined from existing legacy AI Factory artifacts:

- `.ai-factory/plans/09-portal-frontend-redesign.md`
- `.ai-factory/plans/09-portal-frontend-redesign/context.md`
- `.ai-factory/plans/09-portal-frontend-redesign/task.md`
- `.ai-factory/plans/09-portal-frontend-redesign/rules.md`
- `.ai-factory/plans/09-portal-frontend-redesign/verify.md`
- `.ai-factory/plans/09-portal-frontend-redesign/status.yaml`

Supporting context also exists in:

- `docs/PORTAL_FRONTEND_SPEC.md`
- `docs/PORTAL_ROUTES.md`
- `docs/PORTAL_COMPONENTS.md`
- `docs/UI_COPY.md`
- `.ai-factory/rules/portal_frontend.md`
