# Generated OpenSpec Rules

View: Change OpenSpec Rules
Source of truth: OpenSpec canonical specs
Generated files are derived guidance and are safe to delete, overwrite, and regenerate.
Change: 08-security-hardening-and-ops

## Source Fingerprints
- sha256:f925257fe2ff8fb910daa62671387e2102ffde9a6d4480ab925bce4277edc476 openspec/changes/08-security-hardening-and-ops/specs/security-hardening-ops/spec.md

## ADDED Requirements

### Requirement: Backup Restore and Incident Runbooks

Source:
- Kind: change
- Path: openspec/changes/08-security-hardening-and-ops/specs/security-hardening-ops/spec.md
- Capability: security-hardening-ops
- Change: 08-security-hardening-and-ops
- Section: ADDED Requirements
- Fingerprint: sha256:f925257fe2ff8fb910daa62671387e2102ffde9a6d4480ab925bce4277edc476

Production operations SHALL document backup/restore and incident response for identity, site registry, token, OIDC client, audit, and key material data.

#### Scenario: Backup restore is rehearsed
- **WHEN** an operator follows the backup/restore notes
- **THEN** the restored environment SHALL preserve users, sites, modes, token/client metadata, redirect URIs, audit events, and active key metadata without exposing raw secrets in logs.

#### Scenario: Credential leak is reported
- **WHEN** a raw token, client secret, authorization code, or signing key leak is reported
- **THEN** the security runbook SHALL provide revoke/rotate steps and verification evidence to confirm the compromised credential no longer works.

### Requirement: CI Security Gate

Source:
- Kind: change
- Path: openspec/changes/08-security-hardening-and-ops/specs/security-hardening-ops/spec.md
- Capability: security-hardening-ops
- Change: 08-security-hardening-and-ops
- Section: ADDED Requirements
- Fingerprint: sha256:f925257fe2ff8fb910daa62671387e2102ffde9a6d4480ab925bce4277edc476

Continuous integration SHALL run tests, frontend build, and security smoke checks before production-oriented changes are accepted.

#### Scenario: CI executes for a hardening change
- **WHEN** a pull request changes auth, issuer, gateway, portal credential, or ops configuration code
- **THEN** CI SHALL run the configured test suite, build assets, and execute smoke/security checks that fail the pipeline on regression.

### Requirement: Gateway Stale Key Policy

Source:
- Kind: change
- Path: openspec/changes/08-security-hardening-and-ops/specs/security-hardening-ops/spec.md
- Capability: security-hardening-ops
- Change: 08-security-hardening-and-ops
- Section: ADDED Requirements
- Fingerprint: sha256:f925257fe2ff8fb910daa62671387e2102ffde9a6d4480ab925bce4277edc476

Gateway JWT validation SHALL define fail-closed behavior for stale, unavailable, or mismatched JWKS/signing key material.

#### Scenario: Gateway cannot refresh JWKS
- **WHEN** the gateway cannot refresh JWKS within the allowed stale window
- **THEN** it SHALL reject protected upstream requests instead of trusting expired or unknown key material.

#### Scenario: Token references an unknown key id
- **WHEN** a token header contains an unknown `kid`
- **THEN** the gateway SHALL attempt the documented refresh path and SHALL reject the token if the key remains unavailable.

### Requirement: Issuer Key Rotation Operations

Source:
- Kind: change
- Path: openspec/changes/08-security-hardening-and-ops/specs/security-hardening-ops/spec.md
- Capability: security-hardening-ops
- Change: 08-security-hardening-and-ops
- Section: ADDED Requirements
- Fingerprint: sha256:f925257fe2ff8fb910daa62671387e2102ffde9a6d4480ab925bce4277edc476

Issuer signing keys SHALL have documented and executable rotation operations covering generation, activation, JWKS publication, retirement, and rollback.

#### Scenario: Operator rotates signing keys
- **WHEN** an operator runs the documented rotation flow
- **THEN** the platform SHALL publish the new public key through JWKS, use the active private key for new signatures, and keep previous keys available until their issued tokens expire.

#### Scenario: Compromised key is handled
- **WHEN** a signing key is suspected leaked
- **THEN** the runbook SHALL describe forced retirement, token TTL impact, JWKS propagation, gateway cache invalidation, and user/client communication steps.

### Requirement: Production Rate Limits

Source:
- Kind: change
- Path: openspec/changes/08-security-hardening-and-ops/specs/security-hardening-ops/spec.md
- Capability: security-hardening-ops
- Change: 08-security-hardening-and-ops
- Section: ADDED Requirements
- Fingerprint: sha256:f925257fe2ff8fb910daa62671387e2102ffde9a6d4480ab925bce4277edc476

Security-sensitive identity, token, revoke, portal credential, and verification endpoints SHALL use explicit rate limits that fail closed and can be tuned per environment.

#### Scenario: Authentication endpoint exceeds its limit
- **WHEN** repeated authentication or OAuth/OIDC requests exceed the configured limit
- **THEN** the platform SHALL return a deterministic throttling response and SHALL NOT process credential, token, code, or session side effects for the rejected request.

#### Scenario: Token lifecycle endpoint exceeds its limit
- **WHEN** API token, OIDC client secret, revoke, or redirect URI management requests exceed the configured limit
- **THEN** the platform SHALL reject the request before mutating token/client state and SHALL log only non-secret metadata.

### Requirement: Secret-Safe Structured Logs

Source:
- Kind: change
- Path: openspec/changes/08-security-hardening-and-ops/specs/security-hardening-ops/spec.md
- Capability: security-hardening-ops
- Change: 08-security-hardening-and-ops
- Section: ADDED Requirements
- Fingerprint: sha256:f925257fe2ff8fb910daa62671387e2102ffde9a6d4480ab925bce4277edc476

Application and gateway-facing logs SHALL include a request correlation id and non-secret identifiers while excluding raw secrets.

#### Scenario: Request is handled by a protected flow
- **WHEN** auth, issuer, portal, token, revoke, or gateway contract code logs a lifecycle event
- **THEN** the log context SHALL include `request_id` and relevant non-secret ids such as user id, site id, client id, token id, `jti`, or `kid`.

#### Scenario: Secret-bearing input is present
- **WHEN** a request contains raw token, client secret, private key material, authorization code, PKCE verifier, Socialite token, or refresh token data
- **THEN** logs SHALL omit or redact the raw value.

