# site-registry Specification

## Purpose
TBD - created by archiving change backfill-plans-01-05-specs. Update Purpose after archive.
## Requirements
### Requirement: Site Registry Persistence
The platform SHALL persist connected sites, verification challenges, and enabled site modes with ownership and uniqueness constraints.

#### Scenario: Owner creates a site
- **WHEN** an authenticated owner submits a valid domain
- **THEN** the service SHALL normalize the domain, create a site owned by that user, and create verification challenges for DNS TXT and well-known file methods.

#### Scenario: Duplicate owned domain is submitted
- **WHEN** the same owner submits an already registered normalized domain
- **THEN** the service SHALL reject the request with a deterministic conflict response.

#### Scenario: Verified domain belongs to another owner
- **WHEN** a user submits a domain that is already verified for another owner
- **THEN** the service SHALL reject the request with a deterministic conflict response.

### Requirement: Domain Verification
The platform SHALL verify site ownership through DNS TXT or well-known file challenge checks.

#### Scenario: DNS TXT verification succeeds
- **WHEN** the expected TXT record exists for the challenge host
- **THEN** the service SHALL mark the verification and site as verified.

#### Scenario: Well-known file verification succeeds
- **WHEN** the expected verification token is served from the normalized public host
- **THEN** the service SHALL mark the verification and site as verified.

#### Scenario: Challenge is expired
- **WHEN** a verification challenge is checked after its expiry
- **THEN** the service SHALL mark the challenge expired and SHALL NOT mark the site verified.

#### Scenario: Verification would conflict with another verified owner
- **WHEN** a site is being verified for a domain already verified by another owner
- **THEN** the service SHALL fail closed and SHALL NOT mark the site verified.

### Requirement: Site Mode Enablement
The platform SHALL enable `api_resource` and `web_client` modes only for verified sites owned by the authenticated user.

#### Scenario: Verified owner enables a supported mode
- **WHEN** the owner of a verified site requests a supported mode
- **THEN** the service SHALL enable that mode idempotently and return JSON mode metadata.

#### Scenario: Unverified site requests a production mode
- **WHEN** a mode is requested for an unverified site
- **THEN** the service SHALL fail closed and SHALL NOT grant production credentials or mode state.

#### Scenario: Foreign user requests verification or mode enablement
- **WHEN** a user who does not own the site requests verification or mode enablement
- **THEN** the service SHALL return a forbidden response.

#### Scenario: Unsupported mode is requested
- **WHEN** a client requests an unsupported mode value
- **THEN** the service SHALL return a deterministic validation error.

### Requirement: Site Registry Audit
Site registry actions SHALL emit audit events for security-relevant lifecycle changes.

#### Scenario: Site lifecycle event occurs
- **WHEN** a site is connected, verified, or has a mode enabled
- **THEN** the service SHALL log an audit event with site id, owner id, and non-secret context.

