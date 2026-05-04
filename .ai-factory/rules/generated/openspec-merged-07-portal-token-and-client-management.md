# Generated OpenSpec Rules

View: Merged OpenSpec Rules
Source of truth: OpenSpec canonical specs
Generated files are derived guidance and are safe to delete, overwrite, and regenerate.
Change: 07-portal-token-and-client-management

## Source Fingerprints
- sha256:c92c48f00fed67e593bde5bfe4fc0ab9cdb9f6a2452c777db73b91e4c7daf92a openspec/specs/api-resource-gateway/spec.md
- sha256:aab10a2a3acc09558575cb2368d9c4c2dee57b0570431e0338b17e749248a594 openspec/specs/identity-auth/spec.md
- sha256:943632a8e061708c8cac51be3859ce5dd319befc2444887657c58fdb6076c3d1 openspec/specs/oauth-web-login/spec.md
- sha256:a7cb5f1f02044943eece50b184822e439b7c6777b2017651a27ac1851d6fdcb2 openspec/specs/platform-foundation/spec.md
- sha256:f2329389f86fc18416d6d20ee65d85e5144cde995ace746245847b18956e252e openspec/specs/site-registry/spec.md
- sha256:6484b95371fc8c0281c2b13d6d774e23e04dfe6f64089ee05543eb40b1572452 openspec/specs/token-issuer/spec.md
- sha256:1a12e7c3dae5adb17ed5b62ff100f77cdef4c0afd69073af85f1059375f588ff openspec/changes/07-portal-token-and-client-management/specs/portal-token-client-management/spec.md

## Requirements

### Requirement: Deferred Gateway Hardening

Source:
- Kind: base
- Path: openspec/specs/api-resource-gateway/spec.md
- Capability: api-resource-gateway
- Change: none
- Section: Requirements
- Fingerprint: sha256:c92c48f00fed67e593bde5bfe4fc0ab9cdb9f6a2452c777db73b91e4c7daf92a

Deferred gateway hardening SHALL be documented when not implemented in the current reference.

#### Scenario: Signed context is not enabled
- **WHEN** signed context is not implemented in the reference gateway
- **THEN** documentation SHALL state that signed context is deferred to future hardening.

#### Scenario: Edge revoke cache is not enabled
- **WHEN** edge revoke cache or online introspection is not implemented in the reference gateway
- **THEN** documentation SHALL state that it is deferred to a future security-hardening phase.

### Requirement: Gateway Runtime and Routing

Source:
- Kind: base
- Path: openspec/specs/api-resource-gateway/spec.md
- Capability: api-resource-gateway
- Change: none
- Section: Requirements
- Fingerprint: sha256:c92c48f00fed67e593bde5bfe4fc0ab9cdb9f6a2452c777db73b91e4c7daf92a

The API resource gateway SHALL provide an OpenResty reference runtime for `api.example.test` with public health and protected proxy routing.

#### Scenario: Gateway health is requested
- **WHEN** a client requests `/healthz`
- **THEN** the gateway SHALL return `200` JSON without requiring authentication.

#### Scenario: Protected upstream is requested
- **WHEN** a client requests a protected route
- **THEN** the gateway SHALL validate the Bearer JWT before proxying to the upstream API.

#### Scenario: Gateway runtime is built
- **WHEN** the gateway image is built
- **THEN** required runtime dependencies for JWT/JWKS verification SHALL be explicit rather than accidental base-image contents.

### Requirement: Gateway Smoke Coverage

Source:
- Kind: base
- Path: openspec/specs/api-resource-gateway/spec.md
- Capability: api-resource-gateway
- Change: none
- Section: Requirements
- Fingerprint: sha256:c92c48f00fed67e593bde5bfe4fc0ab9cdb9f6a2452c777db73b91e4c7daf92a

The gateway SHALL have executable smoke coverage for its public contract.

#### Scenario: Smoke suite runs
- **WHEN** the gateway smoke script runs against the local Compose stack
- **THEN** it SHALL check valid token proxying, missing token, invalid signature, wrong audience, expired token, not-before token, header sanitization, and raw token leak prevention.

#### Scenario: CI runs gateway checks
- **WHEN** CI validates the repository
- **THEN** it SHALL include the API resource gateway smoke path or document an explicit blocker.

### Requirement: JWKS Cache

Source:
- Kind: base
- Path: openspec/specs/api-resource-gateway/spec.md
- Capability: api-resource-gateway
- Change: none
- Section: Requirements
- Fingerprint: sha256:c92c48f00fed67e593bde5bfe4fc0ab9cdb9f6a2452c777db73b91e4c7daf92a

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

Source:
- Kind: base
- Path: openspec/specs/api-resource-gateway/spec.md
- Capability: api-resource-gateway
- Change: none
- Section: Requirements
- Fingerprint: sha256:c92c48f00fed67e593bde5bfe4fc0ab9cdb9f6a2452c777db73b91e4c7daf92a

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

Source:
- Kind: base
- Path: openspec/specs/api-resource-gateway/spec.md
- Capability: api-resource-gateway
- Change: none
- Section: Requirements
- Fingerprint: sha256:c92c48f00fed67e593bde5bfe4fc0ab9cdb9f6a2452c777db73b91e4c7daf92a

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

### Requirement: Identity Secret Handling

Source:
- Kind: base
- Path: openspec/specs/identity-auth/spec.md
- Capability: identity-auth
- Change: none
- Section: Requirements
- Fingerprint: sha256:aab10a2a3acc09558575cb2368d9c4c2dee57b0570431e0338b17e749248a594

The identity layer SHALL protect provider secrets and avoid logging sensitive values.

#### Scenario: Provider tokens are stored
- **WHEN** a provider returns access or refresh tokens
- **THEN** the service SHALL store those tokens encrypted and SHALL NOT persist raw tokens in plain text columns.

#### Scenario: Identity events are logged
- **WHEN** registration, login, social login, link, or unlink events occur
- **THEN** logs SHALL include non-secret identifiers and SHALL NOT include provider access tokens, refresh tokens, passwords, or raw provider payload secrets.

### Requirement: Session Authentication

Source:
- Kind: base
- Path: openspec/specs/identity-auth/spec.md
- Capability: identity-auth
- Change: none
- Section: Requirements
- Fingerprint: sha256:aab10a2a3acc09558575cb2368d9c4c2dee57b0570431e0338b17e749248a594

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

### Requirement: Social Account Linking

Source:
- Kind: base
- Path: openspec/specs/identity-auth/spec.md
- Capability: identity-auth
- Change: none
- Section: Requirements
- Fingerprint: sha256:aab10a2a3acc09558575cb2368d9c4c2dee57b0570431e0338b17e749248a594

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

### Requirement: Socialite Login

Source:
- Kind: base
- Path: openspec/specs/identity-auth/spec.md
- Capability: identity-auth
- Change: none
- Section: Requirements
- Fingerprint: sha256:aab10a2a3acc09558575cb2368d9c4c2dee57b0570431e0338b17e749248a594

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

### Requirement: Authorization Code Lifecycle

Source:
- Kind: base
- Path: openspec/specs/oauth-web-login/spec.md
- Capability: oauth-web-login
- Change: none
- Section: Requirements
- Fingerprint: sha256:943632a8e061708c8cac51be3859ce5dd319befc2444887657c58fdb6076c3d1

The platform SHALL issue short-lived authorization codes that are returned raw once and stored only as hashes.

#### Scenario: Authorization code is issued
- **WHEN** a valid authorize request completes
- **THEN** the platform SHALL persist a hashed authorization code bound to the client, user, site, redirect URI, scopes, nonce, PKCE challenge, and expiry.

#### Scenario: Authorization code is consumed
- **WHEN** a valid token request consumes an active authorization code
- **THEN** the platform SHALL mark the code consumed transactionally and SHALL prevent the same code from being reused.

#### Scenario: Authorization code is inactive
- **WHEN** an authorization code is missing, expired, already consumed, bound to another client, or bound to another redirect URI
- **THEN** the platform SHALL reject the token request with `invalid_grant`.

### Requirement: OIDC Web Client Persistence

Source:
- Kind: base
- Path: openspec/specs/oauth-web-login/spec.md
- Capability: oauth-web-login
- Change: none
- Section: Requirements
- Fingerprint: sha256:943632a8e061708c8cac51be3859ce5dd319befc2444887657c58fdb6076c3d1

The platform SHALL persist OIDC web clients and their redirect URIs for verified connected sites without storing raw client secrets.

#### Scenario: Web client is stored
- **WHEN** a web client is created for a connected site
- **THEN** the platform SHALL persist the site id, owner user id, generated client id, hashed client secret, display name, and revoked state.

#### Scenario: Redirect URI is stored
- **WHEN** a redirect URI is registered for an OIDC web client
- **THEN** the platform SHALL store the exact redirect URI and a hash for lookup, and SHALL NOT support wildcard matching in the MVP.

#### Scenario: Client is revoked
- **WHEN** a revoked client is used in an OAuth web login flow
- **THEN** the platform SHALL fail closed with a deterministic OAuth error.

### Requirement: Token Endpoint Exchange

Source:
- Kind: base
- Path: openspec/specs/oauth-web-login/spec.md
- Capability: oauth-web-login
- Change: none
- Section: Requirements
- Fingerprint: sha256:943632a8e061708c8cac51be3859ce5dd319befc2444887657c58fdb6076c3d1

The platform SHALL exchange authorization codes for web login tokens only through `POST /oauth/token` with the `authorization_code` grant.

#### Scenario: Valid token request is submitted
- **WHEN** a web client submits `grant_type=authorization_code`, valid client credentials, the original redirect URI, an active authorization code, and a matching PKCE verifier
- **THEN** the platform SHALL return a Bearer access token, an ID token, expiry metadata, and the granted scope.

#### Scenario: Client credentials are invalid
- **WHEN** the token request has a missing, unknown, revoked, or invalid client secret
- **THEN** the platform SHALL reject the request with `invalid_client` before consuming the authorization code.

#### Scenario: PKCE verification fails
- **WHEN** the code verifier does not match the stored S256 challenge
- **THEN** the platform SHALL reject the request with `invalid_grant` and SHALL NOT expose the raw verifier or authorization code in logs.

#### Scenario: Unsupported grant is requested
- **WHEN** the token request uses a grant type other than `authorization_code`
- **THEN** the platform SHALL reject the request and SHALL NOT issue tokens.

#### Scenario: Token endpoint is called repeatedly
- **WHEN** callers use the token endpoint
- **THEN** the platform SHALL apply the `oauth-token` rate limit.

### Requirement: Web Authorization Endpoint

Source:
- Kind: base
- Path: openspec/specs/oauth-web-login/spec.md
- Capability: oauth-web-login
- Change: none
- Section: Requirements
- Fingerprint: sha256:943632a8e061708c8cac51be3859ce5dd319befc2444887657c58fdb6076c3d1

The platform SHALL expose a first-party authenticated authorization endpoint for web clients using Authorization Code with S256 PKCE.

#### Scenario: Valid authorize request is submitted
- **WHEN** an authenticated user requests `GET /oauth/authorize` with `response_type=code`, valid `client_id`, exact `redirect_uri`, allowed scope containing `openid`, `state`, `nonce`, `code_challenge`, and `code_challenge_method=S256`
- **THEN** the platform SHALL redirect to the registered redirect URI with an authorization code and the original state.

#### Scenario: User is not authenticated
- **WHEN** a guest requests `GET /oauth/authorize`
- **THEN** the platform SHALL require first-party web authentication before issuing an authorization code.

#### Scenario: Authorize request is invalid
- **WHEN** the authorize request has an unknown client, revoked client, unverified site, missing `web_client` mode, invalid scope, mismatched redirect URI, missing nonce, missing state, or invalid PKCE challenge
- **THEN** the platform SHALL fail closed and SHALL NOT issue an authorization code.

#### Scenario: Authorize endpoint is called repeatedly
- **WHEN** guest or authenticated callers use the authorize endpoint repeatedly
- **THEN** the platform SHALL apply the `oauth-authorize` rate limit before authentication short-circuits the request.

### Requirement: Web Login Operational Safety

Source:
- Kind: base
- Path: openspec/specs/oauth-web-login/spec.md
- Capability: oauth-web-login
- Change: none
- Section: Requirements
- Fingerprint: sha256:943632a8e061708c8cac51be3859ce5dd319befc2444887657c58fdb6076c3d1

The OAuth web login flow SHALL avoid logging raw secrets and SHALL be documented for external Laravel web clients.

#### Scenario: OAuth flow emits logs
- **WHEN** authorize, token, code, token issuance, or userinfo lifecycle events are logged
- **THEN** logs SHALL include only non-secret metadata such as request id, user id, site id, client id, jti, expiry, and hash prefixes, and SHALL NOT include raw authorization codes, client secrets, PKCE verifiers, JWTs, or private key material.

#### Scenario: External Laravel client follows the example
- **WHEN** a connected Laravel web client implements login through `idshka.ru`
- **THEN** the documentation SHALL describe redirect creation, callback state validation, token exchange, ID token validation via JWKS, userinfo lookup, and local session creation using only public HTTP endpoints.

#### Scenario: Refresh token is requested
- **WHEN** a web login client expects refresh tokens during the MVP
- **THEN** the platform SHALL not issue refresh tokens and SHALL require the web client to use its own local session after callback.

### Requirement: Web Login Tokens and Userinfo

Source:
- Kind: base
- Path: openspec/specs/oauth-web-login/spec.md
- Capability: oauth-web-login
- Change: none
- Section: Requirements
- Fingerprint: sha256:943632a8e061708c8cac51be3859ce5dd319befc2444887657c58fdb6076c3d1

The platform SHALL issue signed web login tokens and expose userinfo claims according to granted OIDC scopes.

#### Scenario: ID token is issued
- **WHEN** the authorization code exchange succeeds
- **THEN** the ID token SHALL be signed with an allowed algorithm and SHALL include issuer, audience client id, subject, site id, client id, nonce, `token_type=id_token`, scope, jti, issued-at, not-before, expiry, `kid`, and `typ=JWT`.

#### Scenario: Web access token is issued
- **WHEN** the authorization code exchange succeeds
- **THEN** the web access token SHALL be short-lived, signed, scoped to the client, and marked with `token_type=web_access`.

#### Scenario: Userinfo is requested
- **WHEN** `GET /oauth/userinfo` receives a valid Bearer web access token
- **THEN** the platform SHALL return `sub` and SHALL include profile or email claims only when the corresponding scopes were granted.

#### Scenario: Userinfo token is invalid
- **WHEN** userinfo receives a missing, expired, malformed, unknown-key, or wrong-token-type Bearer token
- **THEN** the platform SHALL fail closed with `invalid_token`.

#### Scenario: Userinfo endpoint is called repeatedly
- **WHEN** callers use the userinfo endpoint
- **THEN** the platform SHALL apply the `oauth-userinfo` rate limit.

### Requirement: Laravel Platform Runtime

Source:
- Kind: base
- Path: openspec/specs/platform-foundation/spec.md
- Capability: platform-foundation
- Change: none
- Section: Requirements
- Fingerprint: sha256:a7cb5f1f02044943eece50b184822e439b7c6777b2017651a27ac1851d6fdcb2

The platform SHALL run as a Laravel-first application with a defined web, API, OAuth, and operational route surface.

#### Scenario: Application exposes configured route groups
- **WHEN** the application boots
- **THEN** it SHALL load web routes for browser/session flows, API routes for versioned JSON endpoints, OAuth routes for issuer endpoints, and operational probe routes for service health.

#### Scenario: Domain boundaries are present
- **WHEN** new product behavior is added
- **THEN** domain logic SHALL live under `app/Domain/*` or explicit contract namespaces rather than being embedded directly in framework boot files.

### Requirement: Local Container Runtime

Source:
- Kind: base
- Path: openspec/specs/platform-foundation/spec.md
- Capability: platform-foundation
- Change: none
- Section: Requirements
- Fingerprint: sha256:a7cb5f1f02044943eece50b184822e439b7c6777b2017651a27ac1851d6fdcb2

The repository SHALL include a local container runtime for the Laravel app and its required infrastructure.

#### Scenario: Local runtime is configured
- **WHEN** Docker Compose configuration is evaluated
- **THEN** it SHALL define app, web server, database, cache, and gateway-related services with explicit networks and volumes.

#### Scenario: CI validates runtime configuration
- **WHEN** CI runs
- **THEN** it SHALL validate dependency metadata, install dependencies, build frontend assets, run tests, and validate Compose configuration.

### Requirement: Operational Probes

Source:
- Kind: base
- Path: openspec/specs/platform-foundation/spec.md
- Capability: platform-foundation
- Change: none
- Section: Requirements
- Fingerprint: sha256:a7cb5f1f02044943eece50b184822e439b7c6777b2017651a27ac1851d6fdcb2

The platform SHALL provide JSON health and readiness probes suitable for local runtime and CI smoke checks.

#### Scenario: Health probe is requested
- **WHEN** a client requests the public health endpoint
- **THEN** the service SHALL return deterministic JSON identifying the service status.

#### Scenario: Readiness probe is requested
- **WHEN** an allowed internal readiness probe is requested
- **THEN** the service SHALL check required runtime dependencies and return JSON without exposing sensitive connection details.

#### Scenario: Public client requests internal readiness
- **WHEN** a non-internal client requests readiness
- **THEN** the service SHALL fail closed with a forbidden response.

### Requirement: Request Correlation

Source:
- Kind: base
- Path: openspec/specs/platform-foundation/spec.md
- Capability: platform-foundation
- Change: none
- Section: Requirements
- Fingerprint: sha256:a7cb5f1f02044943eece50b184822e439b7c6777b2017651a27ac1851d6fdcb2

Every HTTP response SHALL carry a bounded request id for tracing and logs.

#### Scenario: Request has no trusted id
- **WHEN** a request enters the application without a valid request id
- **THEN** the application SHALL generate one and attach it to response headers and log context.

#### Scenario: Request supplies an invalid id
- **WHEN** a client supplies a malformed or oversized request id
- **THEN** the application SHALL sanitize or replace it before using it.

### Requirement: Domain Verification

Source:
- Kind: base
- Path: openspec/specs/site-registry/spec.md
- Capability: site-registry
- Change: none
- Section: Requirements
- Fingerprint: sha256:f2329389f86fc18416d6d20ee65d85e5144cde995ace746245847b18956e252e

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

Source:
- Kind: base
- Path: openspec/specs/site-registry/spec.md
- Capability: site-registry
- Change: none
- Section: Requirements
- Fingerprint: sha256:f2329389f86fc18416d6d20ee65d85e5144cde995ace746245847b18956e252e

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

Source:
- Kind: base
- Path: openspec/specs/site-registry/spec.md
- Capability: site-registry
- Change: none
- Section: Requirements
- Fingerprint: sha256:f2329389f86fc18416d6d20ee65d85e5144cde995ace746245847b18956e252e

Site registry actions SHALL emit audit events for security-relevant lifecycle changes.

#### Scenario: Site lifecycle event occurs
- **WHEN** a site is connected, verified, or has a mode enabled
- **THEN** the service SHALL log an audit event with site id, owner id, and non-secret context.

### Requirement: Site Registry Persistence

Source:
- Kind: base
- Path: openspec/specs/site-registry/spec.md
- Capability: site-registry
- Change: none
- Section: Requirements
- Fingerprint: sha256:f2329389f86fc18416d6d20ee65d85e5144cde995ace746245847b18956e252e

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

### Requirement: API Resource Eligibility

Source:
- Kind: base
- Path: openspec/specs/token-issuer/spec.md
- Capability: token-issuer
- Change: none
- Section: Requirements
- Fingerprint: sha256:6484b95371fc8c0281c2b13d6d774e23e04dfe6f64089ee05543eb40b1572452

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

### Requirement: Issuer Audit and Throttling

Source:
- Kind: base
- Path: openspec/specs/token-issuer/spec.md
- Capability: token-issuer
- Change: none
- Section: Requirements
- Fingerprint: sha256:6484b95371fc8c0281c2b13d6d774e23e04dfe6f64089ee05543eb40b1572452

Issuer endpoints SHALL emit non-secret audit events and apply explicit rate limits.

#### Scenario: Token lifecycle event occurs
- **WHEN** a token is issued or revoked
- **THEN** the issuer SHALL record audit metadata including user id, site id, audience, jti, kid where applicable, and timestamps without raw token material.

#### Scenario: Issuer endpoint is called repeatedly
- **WHEN** clients call token issue, revoke, or JWKS endpoints
- **THEN** the platform SHALL apply endpoint-specific rate limits.

### Requirement: Signing Keys and JWKS

Source:
- Kind: base
- Path: openspec/specs/token-issuer/spec.md
- Capability: token-issuer
- Change: none
- Section: Requirements
- Fingerprint: sha256:6484b95371fc8c0281c2b13d6d774e23e04dfe6f64089ee05543eb40b1572452

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

### Requirement: Token Revocation

Source:
- Kind: base
- Path: openspec/specs/token-issuer/spec.md
- Capability: token-issuer
- Change: none
- Section: Requirements
- Fingerprint: sha256:6484b95371fc8c0281c2b13d6d774e23e04dfe6f64089ee05543eb40b1572452

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

### Requirement: User API Token Issuance

Source:
- Kind: base
- Path: openspec/specs/token-issuer/spec.md
- Capability: token-issuer
- Change: none
- Section: Requirements
- Fingerprint: sha256:6484b95371fc8c0281c2b13d6d774e23e04dfe6f64089ee05543eb40b1572452

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

## ADDED Requirements

### Requirement: Durable Portal Audit

Source:
- Kind: change
- Path: openspec/changes/07-portal-token-and-client-management/specs/portal-token-client-management/spec.md
- Capability: portal-token-client-management
- Change: 07-portal-token-and-client-management
- Section: ADDED Requirements
- Fingerprint: sha256:1a12e7c3dae5adb17ed5b62ff100f77cdef4c0afd69073af85f1059375f588ff

Security-relevant portal actions SHALL be visible to the owner through durable audit events.

#### Scenario: Portal lifecycle events occur
- **WHEN** site, token, OIDC client, redirect URI, or revoke lifecycle actions complete
- **THEN** the platform SHALL persist non-secret audit metadata and SHALL show those events in the portal audit table.

### Requirement: Portal Danger Actions

Source:
- Kind: change
- Path: openspec/changes/07-portal-token-and-client-management/specs/portal-token-client-management/spec.md
- Capability: portal-token-client-management
- Change: 07-portal-token-and-client-management
- Section: ADDED Requirements
- Fingerprint: sha256:1a12e7c3dae5adb17ed5b62ff100f77cdef4c0afd69073af85f1059375f588ff

The portal SHALL require explicit confirmation for dangerous credential lifecycle actions.

#### Scenario: API token revoke lacks confirmation
- **WHEN** an owner submits a token revoke request without `confirm=revoke`
- **THEN** the portal SHALL reject the request and SHALL NOT revoke the token.

#### Scenario: Web client revoke lacks confirmation
- **WHEN** an owner submits a web client revoke request without `confirm=revoke`
- **THEN** the portal SHALL reject the request and SHALL NOT revoke the client.

### Requirement: Portal Secret Handling

Source:
- Kind: change
- Path: openspec/changes/07-portal-token-and-client-management/specs/portal-token-client-management/spec.md
- Capability: portal-token-client-management
- Change: 07-portal-token-and-client-management
- Section: ADDED Requirements
- Fingerprint: sha256:1a12e7c3dae5adb17ed5b62ff100f77cdef4c0afd69073af85f1059375f588ff

The portal SHALL display newly issued secrets only once and SHALL persist only non-secret metadata.

#### Scenario: API token is issued from the portal
- **WHEN** an owner creates an API token for a verified owned `api_resource` site
- **THEN** the portal SHALL flash the raw Bearer token once and later lists SHALL show only metadata such as token id, audience, `jti`, expiry, and revoke state.

#### Scenario: Web client is created from the portal
- **WHEN** an owner creates an OIDC web client for a verified owned `web_client` site
- **THEN** the portal SHALL flash the generated client secret once, persist a hashed secret, and later lists SHALL show only client id, site, redirect URIs, and revoke state.

### Requirement: Self-Service Portal Dashboard

Source:
- Kind: change
- Path: openspec/changes/07-portal-token-and-client-management/specs/portal-token-client-management/spec.md
- Capability: portal-token-client-management
- Change: 07-portal-token-and-client-management
- Section: ADDED Requirements
- Fingerprint: sha256:1a12e7c3dae5adb17ed5b62ff100f77cdef4c0afd69073af85f1059375f588ff

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

