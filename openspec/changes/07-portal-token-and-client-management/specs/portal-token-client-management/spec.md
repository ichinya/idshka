# portal-token-client-management Specification

## ADDED Requirements

### Requirement: Self-Service Portal Dashboard
Authenticated owners SHALL manage connected sites, API tokens, OIDC web clients, redirect URIs, and audit events through a Blade/Tailwind portal.

#### Scenario: Owner opens the portal
- **WHEN** an authenticated owner requests `GET /portal`
- **THEN** the portal SHALL show owned sites, API token metadata, web client metadata, redirect URIs, and recent audit events without exposing raw tokens or client secrets.

#### Scenario: Owner creates a site from the portal
- **WHEN** an owner submits a domain and optional display name through the portal
- **THEN** the platform SHALL create the site through the site registry domain action and SHALL show DNS TXT and well-known file verification instructions.

#### Scenario: Owner enables site modes from the portal
- **WHEN** an owner enables `api_resource` or `web_client` mode for a verified owned site
- **THEN** the platform SHALL use the existing site mode domain action and SHALL reject unverified or foreign sites fail-closed.

### Requirement: Portal Secret Handling
The portal SHALL display newly issued secrets only once and SHALL persist only non-secret metadata.

#### Scenario: API token is issued from the portal
- **WHEN** an owner creates an API token for a verified owned `api_resource` site
- **THEN** the portal SHALL flash the raw Bearer token once and later lists SHALL show only metadata such as token id, audience, `jti`, expiry, and revoke state.

#### Scenario: Web client is created from the portal
- **WHEN** an owner creates an OIDC web client for a verified owned `web_client` site
- **THEN** the portal SHALL flash the generated client secret once, persist a hashed secret, and later lists SHALL show only client id, site, redirect URIs, and revoke state.

### Requirement: Portal Danger Actions
The portal SHALL require explicit confirmation for dangerous credential lifecycle actions.

#### Scenario: API token revoke lacks confirmation
- **WHEN** an owner submits a token revoke request without `confirm=revoke`
- **THEN** the portal SHALL reject the request and SHALL NOT revoke the token.

#### Scenario: Web client revoke lacks confirmation
- **WHEN** an owner submits a web client revoke request without `confirm=revoke`
- **THEN** the portal SHALL reject the request and SHALL NOT revoke the client.

### Requirement: Durable Portal Audit
Security-relevant portal actions SHALL be visible to the owner through durable audit events.

#### Scenario: Portal lifecycle events occur
- **WHEN** site, token, OIDC client, redirect URI, or revoke lifecycle actions complete
- **THEN** the platform SHALL persist non-secret audit metadata and SHALL show those events in the portal audit table.
