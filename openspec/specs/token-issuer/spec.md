# token-issuer Specification

## Purpose
TBD - created by archiving change backfill-plans-01-05-specs. Update Purpose after archive.
## Requirements
### Requirement: Signing Keys and JWKS
The issuer SHALL manage signing keys and publish public-only JWKS for token verification.

#### Scenario: Active signing key is required
- **WHEN** a token or JWKS operation requires an active key
- **THEN** the issuer SHALL use a non-retired active signing key with a non-empty `kid`.

#### Scenario: Signing key is stored
- **WHEN** a signing key is created
- **THEN** the private key material SHALL be encrypted at rest and public key material SHALL be available for JWKS publication.

#### Scenario: JWKS is requested
- **WHEN** a client requests the public JWKS endpoint
- **THEN** the issuer SHALL return active and prepared-next public keys only and SHALL NOT include private key material.

#### Scenario: Active signing key is unavailable
- **WHEN** JWKS cannot prove valid active key state
- **THEN** the endpoint SHALL fail closed with deterministic JSON.

### Requirement: User API Token Issuance
The issuer SHALL issue short-lived user API JWTs only for eligible verified API resource sites.

#### Scenario: Eligible owner requests a token
- **WHEN** an authenticated owner requests a token for a verified owned site with `api_resource` mode
- **THEN** the issuer SHALL issue a signed Bearer JWT and return the raw token once with token metadata.

#### Scenario: Token claims are created
- **WHEN** a user API token is issued
- **THEN** the JWT SHALL include issuer, audience, subject, site id, `token_type=user_api`, scope, permissions, jti, issued-at, not-before, and expiry claims.

#### Scenario: Token header is created
- **WHEN** a user API token is issued
- **THEN** the JWT header SHALL include an allowed algorithm, `kid`, and `typ=JWT`.

#### Scenario: Token metadata is persisted
- **WHEN** a user API token is issued
- **THEN** the database SHALL persist metadata and a token hash, and SHALL NOT store the raw token.

### Requirement: API Resource Eligibility
The issuer SHALL fail closed for sites or scopes that are not eligible for user API tokens.

#### Scenario: Site is unverified
- **WHEN** a token is requested for an unverified site
- **THEN** the issuer SHALL reject the request and SHALL NOT issue a token.

#### Scenario: Site belongs to another owner
- **WHEN** a token is requested for a site not owned by the authenticated user
- **THEN** the issuer SHALL reject the request and SHALL NOT reveal unnecessary token or site state.

#### Scenario: API resource mode is missing
- **WHEN** a token is requested for a verified site without `api_resource` mode
- **THEN** the issuer SHALL reject the request and SHALL NOT issue a token.

#### Scenario: Requested scope or permission is not allowed
- **WHEN** a token request includes scopes or permissions outside the issuer allow list
- **THEN** the issuer SHALL return a deterministic validation error.

### Requirement: Token Revocation
The issuer SHALL support owner-scoped idempotent token revocation with durable denylist state.

#### Scenario: Owner revokes a token
- **WHEN** the owner revokes an existing token
- **THEN** the issuer SHALL set `api_tokens.revoked_at`, create a `revoked_jti` record, and return JSON revoke metadata.

#### Scenario: Revoke is repeated
- **WHEN** the owner revokes the same token more than once
- **THEN** the issuer SHALL return success without duplicating denylist records.

#### Scenario: Foreign token id is requested
- **WHEN** a user requests revoke for a token they do not own
- **THEN** the issuer SHALL return the same not-found shape used for missing tokens.

#### Scenario: Cache denylist write fails
- **WHEN** optional Redis denylist caching fails after durable DB revoke
- **THEN** the revoke operation SHALL remain committed and SHALL log only non-secret metadata.

### Requirement: Issuer Audit and Throttling
Issuer endpoints SHALL emit non-secret audit events and apply explicit rate limits.

#### Scenario: Token lifecycle event occurs
- **WHEN** a token is issued or revoked
- **THEN** the issuer SHALL record audit metadata including user id, site id, audience, jti, kid where applicable, and timestamps without raw token material.

#### Scenario: Issuer endpoint is called repeatedly
- **WHEN** clients call token issue, revoke, or JWKS endpoints
- **THEN** the platform SHALL apply endpoint-specific rate limits.

