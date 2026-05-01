## ADDED Requirements

### Requirement: Gateway Runtime and Routing
The API resource gateway SHALL provide an OpenResty reference runtime for `api.apishka.ru` with public health and protected proxy routing.

#### Scenario: Gateway health is requested
- **WHEN** a client requests `/healthz`
- **THEN** the gateway SHALL return `200` JSON without requiring authentication.

#### Scenario: Protected upstream is requested
- **WHEN** a client requests a protected route
- **THEN** the gateway SHALL validate the Bearer JWT before proxying to the upstream API.

#### Scenario: Gateway runtime is built
- **WHEN** the gateway image is built
- **THEN** required runtime dependencies for JWT/JWKS verification SHALL be explicit rather than accidental base-image contents.

### Requirement: JWKS Cache
The gateway SHALL fetch public JWKS through an internal URL and cache keys by `kid`.

#### Scenario: Known key id is requested
- **WHEN** a token header references a `kid` present in JWKS
- **THEN** the gateway SHALL select that public key and cache it for the configured TTL.

#### Scenario: Unknown key id is requested
- **WHEN** a token header references a `kid` not present in JWKS
- **THEN** the gateway SHALL fail closed with deterministic JSON.

#### Scenario: JWKS fetch fails
- **WHEN** JWKS cannot be fetched or decoded
- **THEN** the gateway SHALL fail closed with a deterministic gateway error response.

### Requirement: JWT Validation
The gateway SHALL validate user API JWTs before forwarding requests upstream.

#### Scenario: Missing token
- **WHEN** a protected request has no Bearer token
- **THEN** the gateway SHALL return `401` JSON with `missing_token` and a request id.

#### Scenario: Malformed token
- **WHEN** a protected request has a malformed Authorization header or JWT
- **THEN** the gateway SHALL return `401` JSON with `invalid_token` and a request id.

#### Scenario: Signature or header is invalid
- **WHEN** token signature, algorithm, key id, or type header is invalid
- **THEN** the gateway SHALL return `401` JSON and SHALL NOT proxy the request.

#### Scenario: Issuer or audience is invalid
- **WHEN** token `iss` or `aud` does not match gateway configuration
- **THEN** the gateway SHALL fail closed, using `audience_mismatch` for audience failures.

#### Scenario: Required claims are invalid
- **WHEN** token `sub`, `site_id`, `token_type=user_api`, `scope`, `permissions`, `jti`, `iat`, `nbf`, or `exp` claims are missing or invalid
- **THEN** the gateway SHALL return `401` JSON and SHALL NOT proxy the request.

#### Scenario: Token is expired or not yet valid
- **WHEN** token time-window claims fail validation
- **THEN** the gateway SHALL fail closed and SHALL NOT proxy the request.

### Requirement: Trusted Upstream Context
The gateway SHALL remove client-supplied identity context and inject trusted upstream headers.

#### Scenario: Client spoofs gateway headers
- **WHEN** a client sends any `X-Idshka-*` header
- **THEN** the gateway SHALL remove those headers before setting trusted context.

#### Scenario: Valid token reaches upstream
- **WHEN** JWT validation succeeds
- **THEN** the gateway SHALL set trusted `X-Idshka-Authenticated`, `X-Idshka-User-Id`, `X-Idshka-Site-Id`, `X-Idshka-Audience`, `X-Idshka-Scopes`, `X-Idshka-Permissions`, `X-Idshka-JTI`, `X-Idshka-Token-Exp`, and `X-Request-Id` headers.

#### Scenario: Raw token handling
- **WHEN** the gateway proxies a valid request or returns an error
- **THEN** the raw JWT SHALL NOT appear in upstream responses, gateway error bodies, or smoke-flow logs.

### Requirement: Gateway Smoke Coverage
The gateway SHALL have executable smoke coverage for its public contract.

#### Scenario: Smoke suite runs
- **WHEN** the gateway smoke script runs against the local Compose stack
- **THEN** it SHALL check valid token proxying, missing token, invalid signature, wrong audience, expired token, not-before token, header sanitization, and raw token leak prevention.

#### Scenario: CI runs gateway checks
- **WHEN** CI validates the repository
- **THEN** it SHALL include the API resource gateway smoke path or document an explicit blocker.

### Requirement: Deferred Gateway Hardening
Deferred gateway hardening SHALL be documented when not implemented in the current reference.

#### Scenario: Signed context is not enabled
- **WHEN** signed context is not implemented in the reference gateway
- **THEN** documentation SHALL state that signed context is deferred to future hardening.

#### Scenario: Edge revoke cache is not enabled
- **WHEN** edge revoke cache or online introspection is not implemented in the reference gateway
- **THEN** documentation SHALL state that it is deferred to a future security-hardening phase.
