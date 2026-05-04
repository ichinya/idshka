# Delta for Security Hardening and Operations

## ADDED Requirements

### Requirement: Production Rate Limits

Security-sensitive identity, token, revoke, portal credential, verification, and OAuth/OIDC endpoints SHALL use explicit configurable rate limits that fail closed before state mutation.

#### Scenario: Authentication endpoint exceeds its limit

- **GIVEN** a client repeatedly calls registration, login, Socialite redirect, Socialite callback, link, or unlink endpoints
- **WHEN** the configured limit for that endpoint is exceeded
- **THEN** the platform SHALL return a deterministic throttling response with `request_id`
- **AND** the platform SHALL NOT create users, authenticate sessions, link providers, unlink providers, or process callback side effects for the rejected request.

#### Scenario: Token lifecycle endpoint exceeds its limit

- **GIVEN** a caller repeatedly submits API token, OAuth token, OIDC client secret, redirect URI, verification, or revoke requests
- **WHEN** the configured endpoint limit is exceeded
- **THEN** the platform SHALL reject the request before mutating token, client, redirect URI, verification, or revoke state
- **AND** logs SHALL include only non-secret metadata.

#### Scenario: Rate-limit configuration is tuned

- **GIVEN** production operators set environment-specific rate-limit values
- **WHEN** the application boots
- **THEN** the named Laravel limiters SHALL use those values or safe defaults
- **AND** invalid limiter configuration SHALL fail closed or fall back to a stricter safe value without exposing request payloads.

#### Scenario: Browser portal request exceeds its limit

- **GIVEN** an authenticated owner repeatedly submits a portal site, credential, redirect URI, verification, or revoke form
- **WHEN** the configured portal limiter is exceeded
- **THEN** the platform SHALL return a deterministic browser-safe throttling result with a traceable `request_id`
- **AND** the platform SHALL NOT call the domain action for the rejected request.

### Requirement: Secret-Safe Structured Logs

Application and gateway-facing logs SHALL include a request correlation id and non-secret identifiers while excluding raw secrets.

#### Scenario: Request is handled by a protected flow

- **GIVEN** an auth, issuer, portal, token, revoke, verification, OAuth, or gateway flow handles a request
- **WHEN** the flow logs lifecycle, validation, denial, or failure events
- **THEN** the log context SHALL include `request_id` when a request context exists
- **AND** the log context SHOULD include relevant non-secret ids such as user id, site id, client id, token id, `jti`, `kid`, status code, or error class.

#### Scenario: Secret-bearing input is present

- **GIVEN** a request or operation contains raw token, client secret, private key material, authorization code, PKCE verifier, Socialite provider token, password, site verification challenge token, or refresh token data
- **WHEN** logs, exceptions, audit events, command output, gateway errors, or smoke-test failures are emitted
- **THEN** the raw value SHALL be omitted or redacted.

#### Scenario: Gateway JWKS logging occurs

- **GIVEN** the gateway fetches, caches, misses, or rejects JWKS/signing-key material
- **WHEN** the gateway writes a log entry
- **THEN** the entry SHALL include `request_id` when available and MAY include `kid` and cache outcome
- **AND** the entry SHALL NOT include raw JWTs or private key material.

### Requirement: Issuer Key Rotation Operations

Issuer signing keys SHALL have documented and executable rotation operations covering generation, activation, JWKS publication, retirement, compromise handling, rollback, and status inspection.

#### Scenario: Operator prepares a next signing key

- **GIVEN** an operator runs the documented prepare command
- **WHEN** no reusable next key exists
- **THEN** the platform SHALL create an encrypted private key and public JWK with status `next`
- **AND** JWKS SHALL publish the next public key without using it for new signatures.

#### Scenario: Operator activates the next signing key

- **GIVEN** a prepared next key exists
- **WHEN** an operator runs the documented activation command
- **THEN** the platform SHALL use the activated key for new token signatures
- **AND** previous active public keys SHALL remain available through JWKS until issued tokens signed with them have expired or the operator force-retires them.

#### Scenario: Operator retires expired signing keys

- **GIVEN** old signing keys are past their safe publication window
- **WHEN** an operator runs the documented retire-expired command
- **THEN** those keys SHALL be removed from active JWKS publication without deleting audit-relevant key metadata.

#### Scenario: Non-expiring token blocks automatic retirement

- **GIVEN** a signing key has issued one or more non-expiring API tokens that are not revoked
- **WHEN** an operator runs the normal retire-expired command for that key
- **THEN** the platform SHALL block automatic retirement or require an explicit force/compromise mode
- **AND** the command output SHALL report only non-secret blocker counts and key ids.

#### Scenario: Compromised key is handled

- **GIVEN** a signing key is suspected leaked
- **WHEN** an operator follows the forced retirement runbook
- **THEN** the platform SHALL stop publishing that key according to the compromise procedure
- **AND** the runbook SHALL describe token TTL impact, gateway cache invalidation, client/user communication, and verification evidence.

### Requirement: Backup Restore and Incident Runbooks

Production operations SHALL document backup/restore and incident response for identity, site registry, token, OIDC client, audit, revoke, and key material data.

#### Scenario: Backup restore is rehearsed

- **GIVEN** an operator follows the backup/restore notes
- **WHEN** a restore drill completes
- **THEN** the restored environment SHALL preserve users, sites, modes, token/client metadata, redirect URIs, audit events, revoke state, and active key metadata
- **AND** restore logs and evidence SHALL NOT expose raw secrets.

#### Scenario: Credential leak is reported

- **GIVEN** a raw token, client secret, authorization code, Socialite token, signing key, or `APP_KEY` leak is reported
- **WHEN** the security runbook is followed
- **THEN** it SHALL provide revoke, rotate, invalidate, communicate, and verify steps appropriate to the credential type
- **AND** verification evidence SHALL prove the compromised credential no longer works or has a documented residual risk window.

#### Scenario: Client secret leak is handled

- **GIVEN** an OIDC client secret is suspected leaked
- **WHEN** the security runbook is followed
- **THEN** it SHALL clearly state whether the supported operator action is revoke/recreate or true secret rotation
- **AND** it SHALL describe how to verify that the leaked secret no longer authenticates.

#### Scenario: Gateway header trust failure is reported

- **GIVEN** a connected resource reports spoofed or missing `X-Idshka-*` trust headers
- **WHEN** the security runbook is followed
- **THEN** it SHALL direct operators to validate gateway sanitization, private network exposure, request ids, upstream reachability, and signed-context requirements.

### Requirement: CI Security Gate

Continuous integration SHALL run tests, frontend build, dependency/security checks, and smoke validation before production-oriented changes are accepted.

#### Scenario: CI executes for a hardening change

- **GIVEN** a pull request changes auth, issuer, gateway, portal credential, or ops configuration code
- **WHEN** CI runs
- **THEN** CI SHALL run dependency metadata validation, dependency audits with explicit severity thresholds, Laravel tests, frontend build, Docker Compose validation, ingress smoke, and gateway smoke
- **AND** regressions SHALL fail the pipeline.

#### Scenario: Security smoke fails

- **GIVEN** a security smoke check fails in CI
- **WHEN** failure diagnostics are printed
- **THEN** output SHALL include command/status context and request ids where available
- **AND** output SHALL NOT print raw tokens, private keys, client secrets, authorization codes, or PKCE verifiers.

#### Scenario: CI workflow token is scoped

- **GIVEN** GitHub Actions runs the repository workflow
- **WHEN** the CI job starts
- **THEN** workflow permissions SHALL be set to the least privilege needed for checkout and checks.

### Requirement: Gateway Stale Key Policy

Gateway JWT validation SHALL define fail-closed behavior for stale, unavailable, or mismatched JWKS/signing key material.

#### Scenario: Gateway cannot refresh JWKS

- **GIVEN** the gateway needs JWKS material and the configured cache entry is missing or expired
- **WHEN** the gateway cannot refresh JWKS within the allowed window
- **THEN** it SHALL reject protected upstream requests instead of trusting expired, unavailable, or unknown key material.

#### Scenario: Token references an unknown key id

- **GIVEN** a token header contains an unknown `kid`
- **WHEN** the gateway validates the token
- **THEN** the gateway SHALL attempt the documented JWKS refresh path
- **AND** it SHALL reject the token if the key remains unavailable.

#### Scenario: Cached key is within its allowed TTL

- **GIVEN** a cached public key exists for a token `kid`
- **WHEN** the cache entry is still within the configured TTL
- **THEN** the gateway MAY use the cached public key for validation
- **AND** it SHALL NOT use that cached key after the TTL expires unless a fresh JWKS response republishes it.
