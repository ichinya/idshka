# oauth-web-login Specification

## Purpose
TBD - created by archiving change 06-web-login-through-idshka. Update Purpose after archive.
## Requirements
### Requirement: OIDC Web Client Persistence
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

### Requirement: Web Authorization Endpoint
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
- **WHEN** callers use the authorize endpoint
- **THEN** the platform SHALL apply the `oauth-authorize` rate limit.

### Requirement: Authorization Code Lifecycle
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

### Requirement: Token Endpoint Exchange
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

### Requirement: Web Login Tokens and Userinfo
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

### Requirement: Web Login Operational Safety
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

