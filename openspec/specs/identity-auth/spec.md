# identity-auth Specification

## Purpose
TBD - created by archiving change backfill-plans-01-05-specs. Update Purpose after archive.
## Requirements
### Requirement: Session Authentication
The identity layer SHALL support first-party session registration, login, and logout.

#### Scenario: User registers successfully
- **WHEN** a guest submits valid registration data
- **THEN** the service SHALL create the user, hash the password, start an authenticated session, and return JSON user metadata.

#### Scenario: User logs in successfully
- **WHEN** a guest submits valid email and password credentials
- **THEN** the service SHALL authenticate the user, regenerate the session, and return JSON user metadata.

#### Scenario: User logs out
- **WHEN** an authenticated user requests logout
- **THEN** the service SHALL terminate the session and return a deterministic JSON success response.

### Requirement: Socialite Login
The identity layer SHALL support external Socialite login through Google, VKontakte, and Yandex providers.

#### Scenario: Social login starts
- **WHEN** a guest requests `/auth/{provider}/redirect` for a supported provider
- **THEN** the service SHALL store a login intent in session and redirect to that provider's Socialite driver.

#### Scenario: Social callback creates an account
- **WHEN** a supported provider callback returns a new valid provider identity
- **THEN** the service SHALL create a local user, create a linked social account, authenticate the user, and dispatch audit events.

#### Scenario: Social callback matches an existing provider identity
- **WHEN** a supported provider callback returns an already linked provider identity
- **THEN** the service SHALL reuse the existing local user instead of creating a duplicate user.

#### Scenario: Unsupported provider is requested
- **WHEN** a client requests an unknown provider
- **THEN** the service SHALL return a deterministic error response and SHALL NOT create local identity records.

### Requirement: Social Account Linking
Authenticated users SHALL be able to link and unlink supported external provider identities.

#### Scenario: Authenticated user links a provider
- **WHEN** an authenticated user completes a supported provider link callback
- **THEN** the service SHALL attach that provider identity to the authenticated user if it is not owned by another user.

#### Scenario: Link callback has stale authentication context
- **WHEN** a link callback cannot prove the same authenticated user that started the link flow
- **THEN** the service SHALL reject the callback and SHALL NOT link the social account.

#### Scenario: User unlinks a provider
- **WHEN** an authenticated user requests unlink for a supported provider
- **THEN** the service SHALL remove that user's social account link and dispatch an audit event.

### Requirement: Identity Secret Handling
The identity layer SHALL protect provider secrets and avoid logging sensitive values.

#### Scenario: Provider tokens are stored
- **WHEN** a provider returns access or refresh tokens
- **THEN** the service SHALL store those tokens encrypted and SHALL NOT persist raw tokens in plain text columns.

#### Scenario: Identity events are logged
- **WHEN** registration, login, social login, link, or unlink events occur
- **THEN** logs SHALL include non-secret identifiers and SHALL NOT include provider access tokens, refresh tokens, passwords, or raw provider payload secrets.

