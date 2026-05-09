# portal-frontend-redesign Specification

## Purpose
TBD - created by archiving change 09-portal-frontend-redesign. Update Purpose after archive.
## Requirements
### Requirement: Portal Workspace Navigation

The portal SHALL expose separate Account, Developer, and Audit workspaces through authenticated Blade/Tailwind pages while preserving compatible entry points for existing browser flows.

#### Scenario: Authenticated user opens portal root

- **GIVEN** an authenticated user has a valid web session
- **WHEN** the user requests `GET /portal`
- **THEN** the application SHALL redirect to `GET /portal/account` or render an equivalent workspace selector
- **AND** the response SHALL use the shared portal shell navigation.

#### Scenario: Guest opens a portal page

- **GIVEN** a guest has no authenticated web session
- **WHEN** the guest requests any Account, Developer, or Audit portal page
- **THEN** the application SHALL require first-party web authentication before rendering portal data.

#### Scenario: Existing portal write route is submitted

- **GIVEN** an authenticated owner submits an existing portal mutation route for sites, verification, modes, API tokens, OIDC clients, redirect URIs, or revocation
- **WHEN** the route is handled after the redesign
- **THEN** the application SHALL keep the existing route name available or provide a compatibility alias
- **AND** the mutation SHALL use the same domain action, validation, CSRF protection, named rate limit, and owner checks as before the redesign.

### Requirement: Account Workspace

The Account workspace SHALL show end-user identity, provider linking, sessions, personal API tokens, and connected application context without mixing developer site controls into the same page.

#### Scenario: User opens Account overview

- **GIVEN** an authenticated user exists
- **WHEN** the user requests `GET /portal/account`
- **THEN** the page SHALL show profile summary, linked social account summary, session or current-device summary, user API token summary, and recent account-relevant audit events.

#### Scenario: User opens Social accounts page

- **GIVEN** an authenticated user can link supported Socialite providers
- **WHEN** the user requests `GET /portal/account/social`
- **THEN** the page SHALL show supported provider states for Google, VK, and Yandex where configured
- **AND** provider link or unlink controls SHALL call the existing Socialite link/unlink routes.

#### Scenario: User opens Account tokens page

- **GIVEN** an authenticated user has zero or more user API tokens
- **WHEN** the user requests `GET /portal/account/tokens`
- **THEN** the page SHALL list token metadata such as site, audience, `jti`, scopes, expiry, and revoked state
- **AND** the page SHALL NOT show raw token strings except immediately after a successful token creation flash.

#### Scenario: User opens Sessions page

- **GIVEN** the platform uses Laravel web sessions
- **WHEN** the user requests `GET /portal/account/sessions`
- **THEN** the page SHALL show current-session or stored-session metadata that can be safely derived from the existing session store
- **AND** it SHALL show an empty state when no additional session records are available.

### Requirement: Developer Workspace

The Developer workspace SHALL manage owned connected sites, domain verification, credentials, redirect URIs, API gateway guidance, and web-login integration guidance.

#### Scenario: Owner opens Developer overview

- **GIVEN** an authenticated owner has zero or more connected sites
- **WHEN** the owner requests `GET /portal/developer`
- **THEN** the page SHALL show owned site counts, verified domain counts, active credential counts, mode summaries, and recent developer-relevant audit events.

#### Scenario: Owner opens owned site page

- **GIVEN** an authenticated owner owns a connected site
- **WHEN** the owner requests a Developer page for that site
- **THEN** the page SHALL show only that owned site's verification, mode, token/client, redirect URI, and integration context.

#### Scenario: Foreign user opens site page

- **GIVEN** a connected site belongs to another owner
- **WHEN** an authenticated foreign user requests a Developer page for that site
- **THEN** the application SHALL fail closed and SHALL NOT reveal the site's existence, credentials, redirect URIs, verification tokens, or audit context.

#### Scenario: Owner opens credentials page

- **GIVEN** an authenticated owner manages OIDC web clients for an owned site
- **WHEN** the owner requests the credentials page
- **THEN** the page SHALL show client ids, names, redirect URI summaries, and active/revoked state
- **AND** the page SHALL NOT show raw client secrets except immediately after a successful client creation flash.

#### Scenario: Owner opens integration guide pages

- **GIVEN** an authenticated owner views API-only or Web Login integration guidance
- **WHEN** the owner requests gateway or web-login guide pages
- **THEN** the pages SHALL document the relevant public endpoints, token/header contract, PKCE/state/nonce expectations, and local-session handoff
- **AND** gateway guidance SHALL state that upstream applications must not trust client-supplied `X-Idshka-*` headers.

### Requirement: Audit Workspace

The Audit workspace SHALL provide owner-scoped, searchable security and product event visibility without exposing events that belong only to other users or sites.

#### Scenario: User opens audit list

- **GIVEN** an authenticated user has zero or more visible audit events
- **WHEN** the user requests `GET /portal/audit`
- **THEN** the page SHALL show a filterable list of events scoped to that user or to sites owned by that user.

#### Scenario: User filters audit list

- **GIVEN** audit events exist for the authenticated user or owned sites
- **WHEN** the user filters by date, category/event type, site, actor, or supported severity
- **THEN** the page SHALL return matching scoped events
- **AND** it SHALL show an empty state when no events match.

#### Scenario: User opens audit detail

- **GIVEN** an audit event is visible to the authenticated user
- **WHEN** the user requests `GET /portal/audit/{event}`
- **THEN** the page SHALL show event time, category, action, summary, user/site context, and escaped metadata JSON.

#### Scenario: User opens foreign audit detail

- **GIVEN** an audit event is not visible to the authenticated user
- **WHEN** the user requests `GET /portal/audit/{event}`
- **THEN** the application SHALL fail closed and SHALL NOT reveal the event metadata.

### Requirement: Portal UI Components and Empty States

The redesigned portal SHALL use shared Blade components and contextual empty states for repeated UI patterns.

#### Scenario: Portal page renders a repeated UI block

- **GIVEN** a portal page needs a card, status badge, page header, code snippet, warning callout, table action, or empty state
- **WHEN** the page is implemented
- **THEN** it SHALL use a shared Blade component or an equivalent local partial rather than duplicating large markup blocks.

#### Scenario: Portal list has no records

- **GIVEN** a portal list page has no sites, social accounts, sessions, tokens, clients, redirect URIs, or audit events to display
- **WHEN** the page renders
- **THEN** it SHALL show a contextual empty state with an appropriate next action when one exists.

#### Scenario: Portal needs lightweight browser behavior

- **GIVEN** a portal page needs mobile navigation, copy-to-clipboard, redirect URI autofill, or copied-state feedback
- **WHEN** the behavior is implemented
- **THEN** JavaScript SHALL be loaded from bundled assets compatible with `script-src 'self'`
- **AND** Blade templates SHALL NOT rely on inline scripts for that behavior.

### Requirement: Portal Regression Coverage

The redesign SHALL be covered by feature and build tests that prove route availability, owner scoping, secret handling, and existing issuer behavior.

#### Scenario: Portal route test suite runs

- **GIVEN** the portal redesign is implemented
- **WHEN** the feature test suite runs
- **THEN** tests SHALL cover guest redirects, `/portal` redirect behavior, Account pages, Developer pages, Audit pages, owner-scoped site access, owner-scoped audit access, and empty states.

#### Scenario: Secret handling test suite runs

- **GIVEN** API tokens and client secrets can be created through the portal
- **WHEN** tests create those credentials and later reload portal pages
- **THEN** tests SHALL prove raw API tokens and raw client secrets appear only immediately after creation and are absent from later pages.

#### Scenario: Compatibility regression suite runs

- **GIVEN** existing portal and issuer behavior must remain intact
- **WHEN** tests run after the redesign
- **THEN** existing site creation, verification, mode enablement, token issue/revoke, client creation/revoke, redirect URI creation, rate-limit, Socialite, and OAuth issuer tests SHALL continue to pass.

