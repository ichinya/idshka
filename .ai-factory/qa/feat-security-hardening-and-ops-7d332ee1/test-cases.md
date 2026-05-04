## Test Cases: Security Hardening and Operations

---

### TC-001: API token issue rate limit blocks mutation

**Priority:** High
**Type:** Negative / Security

**Precondition:**

An owner is authenticated, owns a verified `api_resource` site for `example.test`, and token issue limit is set to one request per minute for the test environment.

**Steps:**

1. Record the current API token count for the owner and site.
2. Submit one valid API token issue request for `example.test`.
3. Immediately submit a second valid API token issue request with the same owner and site.
4. Inspect the second response and safe operational evidence.
5. Record the API token count again.

**Expected result:**

The first request succeeds. The second request returns `429` JSON with `error=rate_limited` and a non-empty `request_id`. The API token count increases by exactly one, not two. Logs include safe identifiers such as `request_id`, `user_id`, `site_id`, and limiter context, but do not include a raw JWT or private key material.

**Test data:**

```text
site domain: example.test
mode: api_resource
scopes: orders.read
permissions: orders.read
rate limit: SECURITY_RATE_LIMIT_TOKEN_ISSUE_PER_MINUTE=1
```

---

### TC-002: Portal client write rate limit redirects safely

**Priority:** High
**Type:** Negative / Security

**Precondition:**

An owner is authenticated in the browser portal, owns a verified `web_client` site, and portal client write limit is set to one request per minute.

**Steps:**

1. Record the current OIDC client count and redirect URI count for the site.
2. Submit a valid portal client creation form.
3. Immediately submit a second portal client creation form.
4. Observe the browser response and session feedback.
5. Re-check client and redirect URI counts.

**Expected result:**

The first form creates one client. The second form redirects back with a rate-limit error and a traceable `request_id`. No second client and no second redirect URI are created. The response and logs do not expose the submitted client secret or raw form payload.

**Test data:**

```text
site domain: example.test
redirect URI: https://example.test/auth/idshka/callback
rate limit: SECURITY_RATE_LIMIT_PORTAL_CLIENT_WRITE_PER_MINUTE=1
```

---

### TC-003: Portal revoke rate limit blocks second revoke before state change

**Priority:** High
**Type:** Negative / Security

**Precondition:**

An owner has two active API tokens for the same site, and portal credential revoke limit is set to one request per minute.

**Steps:**

1. Record `token_id` and `jti` for two active tokens without storing raw JWTs.
2. Revoke the first token through the portal.
3. Immediately attempt to revoke the second token through the portal.
4. Inspect portal feedback and token metadata.

**Expected result:**

The first token is revoked. The second revoke request is rate-limited and redirects with a `request_id`. The second token remains active. Evidence stores only `token_id`, `jti`, `site_id`, `request_id`, and timestamps.

**Test data:**

```text
token A: active, finite expiry
token B: active, finite expiry
rate limit: SECURITY_RATE_LIMIT_PORTAL_CREDENTIAL_REVOKE_PER_MINUTE=1
```

---

### TC-004: Invalid rate-limit env value falls back safely

**Priority:** Medium
**Type:** Edge / Negative

**Precondition:**

Application environment can be restarted with a deliberately invalid rate-limit value.

**Steps:**

1. Set one limiter env value to a non-integer string.
2. Boot the application and warm config.
3. Exercise the affected endpoint more times than the safe default allows.
4. Inspect logs for the invalid configuration diagnostic.

**Expected result:**

The application boots. The limiter uses the safe default rather than disabling protection. The diagnostic log includes limiter name, config key, configured type, and default limit. It does not include request payloads, passwords, tokens, client secrets, authorization codes, or private key material.

**Test data:**

```text
SECURITY_RATE_LIMIT_AUTH_LOGIN_PER_MINUTE=not-an-int
endpoint: POST /login
```

---

### TC-005: OAuth token exchange logs safe context only

**Priority:** High
**Type:** Security / Regression

**Precondition:**

A valid OAuth authorization code flow can be completed for a verified `web_client` site.

**Steps:**

1. Start an OAuth authorize flow and obtain a one-time authorization code.
2. Exchange the code at the token endpoint using the correct client credentials and PKCE verifier.
3. Inspect application log evidence for the exchange request.

**Expected result:**

The exchange succeeds and returns tokens to the client. Logs contain `request_id`, `client_id`, `site_id`, `user_id`, and safe token identifiers such as `jti` where applicable. Logs do not contain the raw authorization code, PKCE verifier, client secret, access token, ID token, or private key material.

**Test data:**

```text
client_id: existing client for example.test
redirect URI: https://example.test/auth/idshka/callback
scope: openid profile email
```

---

### TC-006: Socialite callback and linking evidence stays secret-safe

**Priority:** Medium
**Type:** Security / Regression

**Precondition:**

Socialite login and account linking are configured in a local or stubbed environment.

**Steps:**

1. Complete a Socialite login callback for a new or existing user.
2. Complete a Socialite link flow for an authenticated user.
3. Complete an unlink flow for the same provider.
4. Inspect safe log and audit evidence for all three operations.

**Expected result:**

Login/link/unlink behavior remains correct. Evidence includes `request_id`, provider name, local `user_id`, and provider user id where safe. Evidence does not include provider access token, refresh token, email password, raw callback payload, or session secret.

**Test data:**

```text
provider: google
intent: login and link
local user: owner@example.test
```

---

### TC-007: Prepare and activate next signing key

**Priority:** High
**Type:** Positive / Security

**Precondition:**

At least one active signing key exists.

**Steps:**

1. Run the key status command and record current active `kid` values.
2. Prepare a next signing key.
3. Confirm JWKS publishes the prepared public key but new signatures still use the previous active key.
4. Activate the prepared key.
5. Issue a new token and inspect its `kid`.
6. Request JWKS again.

**Expected result:**

The prepared key has status `next` before activation. After activation, new tokens use the newly activated `kid`. JWKS publishes the newly active public key and still publishes previous active public keys while they are within their safe publication window. No command output or evidence contains decrypted private key material.

**Test data:**

```text
commands: idshka:keys:status, idshka:keys:prepare, idshka:keys:activate-next
safe evidence: key_id, kid, status, activated_at
```

---

### TC-008: Retire expired signing key is blocked by non-expiring token

**Priority:** High
**Type:** Edge / Security

**Precondition:**

An older active key has issued at least one non-expiring API token that is not revoked. A newer active key exists.

**Steps:**

1. Record the older key `kid` and blocker token `jti`.
2. Run retire-expired in normal mode.
3. Inspect command output, key status, and JWKS publication.
4. Revoke or otherwise clear the blocker according to policy.
5. Run retire-expired again.

**Expected result:**

The first retirement attempt reports a blocker count and does not retire the older key. Output and logs include only safe key ids, `kid`, and blocker counts. After the blocker is cleared, the key can be retired and removed from active JWKS publication without deleting audit-relevant metadata.

**Test data:**

```text
api token: expires_at=null, revoked_at=null
safe evidence: key_id, kid, blocking_api_tokens_count
```

---

### TC-009: Force-retire refuses destructive action without confirmation

**Priority:** High
**Type:** Negative / Security

**Precondition:**

A signing key exists and its `kid` is known.

**Steps:**

1. Attempt to force-retire the key without `--force` and without `--dry-run`.
2. Inspect command exit status and key state.
3. Run force-retire with `--dry-run`.
4. Run force-retire with explicit confirmation only after confirming the test key can be retired.

**Expected result:**

Without confirmation, the command refuses the destructive operation and key state does not change. Dry-run reports the target without mutation. Confirmed force-retire changes the key to retired, clears JWKS cache, and logs only `kid`, key id, status, and timestamps.

**Test data:**

```text
command variants:
idshka:keys:force-retire <kid>
idshka:keys:force-retire <kid> --dry-run
idshka:keys:force-retire <kid> --force
```

---

### TC-010: Rollback returns signing to previous active key

**Priority:** High
**Type:** Positive / Security

**Precondition:**

Two active signing keys exist: one newest active key and one previous active key still safe for publication.

**Steps:**

1. Record newest active `kid` and previous active `kid`.
2. Run rollback to previous active.
3. Issue a new token.
4. Request JWKS.

**Expected result:**

The newest key is marked retired and no longer selected for new signatures. New tokens use the previous active `kid`. JWKS cache is invalidated and publication reflects the updated lifecycle state. Evidence contains no private key material or raw JWT.

**Test data:**

```text
safe evidence: retired_kid, active_kid, retired_at, token jti
```

---

### TC-011: Gateway accepts valid token and strips spoofed headers

**Priority:** High
**Type:** Positive / Security

**Precondition:**

The app, nginx, Redis, PostgreSQL, demo API, and demo OpenResty gateway are running. A valid user API token exists for audience `example.test`.

**Steps:**

1. Send a protected request through the gateway with a valid Bearer token.
2. Include spoofed inbound headers such as `X-Idshka-User-Id`, `X-Idshka-Scopes`, and `X-Idshka-Canary`.
3. Inspect the upstream response.

**Expected result:**

The request reaches upstream with authenticated context from the verified token. Spoofed inbound `X-Idshka-*` values do not reach upstream. The upstream response does not include the raw JWT. Response/evidence includes a traceable `request_id`.

**Test data:**

```text
URL: http://127.0.0.1:8081/protected
audience: example.test
scope: orders.read
spoofed headers: X-Idshka-User-Id=spoofed-user, X-Idshka-Canary=spoofed-canary
```

---

### TC-012: Gateway rejects retired cached key after TTL expiry

**Priority:** High
**Type:** Edge / Security

**Precondition:**

Gateway JWKS cache TTL is configured to a known short value. A valid token is issued with the current signing key.

**Steps:**

1. Send the valid token once to warm the gateway JWKS cache.
2. Rotate to a new signing key and force-retire the token's original `kid`.
3. Send the old token again before TTL expiry.
4. Wait until just after TTL expiry.
5. Send the old token again.

**Expected result:**

Before TTL expiry, the cached key may still validate the token. After TTL expiry, the gateway refreshes JWKS, does not trust the stale retired key, and returns `401` JSON with `error=invalid_token`, a message that the signing key is unknown, and `request_id`.

**Test data:**

```text
GATEWAY_JWKS_CACHE_SECONDS=20
wait time after retirement: 21 seconds
safe evidence: original kid, new kid, request_id, HTTP status
```

---

### TC-013: Gateway fails closed for unknown kid and JWKS unavailable

**Priority:** High
**Type:** Negative / Security

**Precondition:**

Gateway is running and can initially reach JWKS.

**Steps:**

1. Send a token whose header references an unknown `kid`.
2. Confirm the gateway attempts refresh and rejects the token.
3. Make JWKS temporarily unavailable to the gateway.
4. Send a token whose `kid` requires refresh.

**Expected result:**

Unknown `kid` returns `401` JSON with `error=invalid_token` and `request_id`. When JWKS is unavailable, the gateway returns `502` JSON with `error=jwks_unavailable` and `request_id`. In both cases, the request is not proxied upstream and no raw JWT is printed in failure diagnostics.

**Test data:**

```text
unknown kid: unknown-kid
JWKS unavailable case: stop Laravel nginx or block internal JWKS route
```

---

### TC-014: Gateway rejects malformed token conditions

**Priority:** High
**Type:** Negative / Regression

**Precondition:**

Gateway is running and has access to JWKS.

**Steps:**

1. Send a request with no Bearer token.
2. Send a request with a token with invalid signature.
3. Send a request with wrong audience.
4. Send a request with an expired token.
5. Send a request with a not-before time in the future.

**Expected result:**

Missing token returns `401` with `error=missing_token`. Invalid signature, future not-before, and malformed header cases return `401` with `error=invalid_token`. Wrong audience returns `401` with `error=audience_mismatch`. Expired token returns `401` with `error=expired_token`. Every response includes `request_id` and none are proxied upstream.

**Test data:**

```text
wrong audience: wrong.example.test
expired offset: -60 seconds
not-before offset: +600 seconds
```

---

### TC-015: Backup and restore runbook preserves safe evidence rules

**Priority:** Medium
**Type:** Functional / Security

**Precondition:**

A tester has access to the operations runbook and a disposable local or staging-like environment.

**Steps:**

1. Follow the backup scope list and identify each state source that must be captured together.
2. Perform a restore drill using a disposable target or dry operational checklist.
3. Verify data integrity checks for users, sites, modes, token metadata, OIDC clients, redirect URIs, authorization codes, audit events, revoke state, and signing key metadata.
4. Rebuild JWKS cache and request public JWKS.
5. Review captured evidence.

**Expected result:**

The runbook gives enough steps to preserve and verify the listed state. Evidence uses artifact ids, checksums, safe identifiers, HTTP status, error codes, and command pass/fail status. Evidence does not include raw secrets, decrypted PEM, raw JWTs, client secrets, authorization codes, Socialite tokens, or `APP_KEY`.

**Test data:**

```text
safe identifiers: request_id, user_id, site_id, client_id, token_id, jti, kid
JWKS public fields: kty, kid, alg, use, n, e
```

---

### TC-016: Security runbook handles credential leaks with correct operator action

**Priority:** Medium
**Type:** Functional / Security

**Precondition:**

The security runbook is available to a tester acting as incident commander.

**Steps:**

1. Walk through leaked API token handling.
2. Walk through leaked client secret handling.
3. Walk through leaked authorization code handling.
4. Walk through leaked Socialite provider token handling.
5. Walk through leaked signing key handling.
6. Walk through compromised `APP_KEY` handling.
7. Walk through gateway header trust failure handling.

**Expected result:**

Each procedure has containment and evidence steps. Client secret handling clearly states revoke/recreate rather than true in-place rotation. Signing key handling includes prepare/activate/force-retire, JWKS cache rebuild, token impact, and gateway coordination. Gateway trust failure includes header sanitization checks and protected smoke cases. All evidence examples use safe identifiers only.

**Test data:**

```text
incident examples:
leaked API token with jti=<safe-jti>
leaked client secret for client_id=<safe-client-id>
leaked signing key with kid=<safe-kid>
gateway trust failure with request_id=<safe-request-id>
```

---

### TC-017: Existing auth and portal happy paths still work

**Priority:** Medium
**Type:** Regression

**Precondition:**

The application is running with default rate limits and a clean browser session.

**Steps:**

1. Register a new user.
2. Log out and log in again with email/password.
3. Create a site in the portal.
4. Verify the site by an available local verification method.
5. Enable `api_resource` and `web_client` modes.
6. Create an API token.
7. Create an OIDC client and redirect URI.
8. Revoke the API token and revoke the client.

**Expected result:**

All core portal and auth operations still complete under normal usage. Request lifecycle evidence includes `request_id`. Lists and confirmation views do not expose raw token or client secret values after the one-time creation moment.

**Test data:**

```text
user email: owner+qa@example.test
site domain: localhost or example.test, depending on local verification setup
redirect URI: https://example.test/auth/idshka/callback
```

---

### TC-018: CI workflow exposes required hardening gates

**Priority:** Medium
**Type:** Functional / Regression

**Precondition:**

The repository workflow file is available and a branch or pull request can trigger it.

**Steps:**

1. Inspect workflow permissions.
2. Confirm dependency metadata and dependency audit steps are present.
3. Confirm route/config smoke is present before broader runtime smoke.
4. Confirm frontend build is present.
5. Confirm Docker Compose config validation is present.
6. Confirm ingress smoke and API resource gateway smoke are present.
7. Confirm cleanup runs even if a smoke step fails.

**Expected result:**

Workflow permissions are least-privilege for checkout and checks. Required gates are present in deterministic order. Gateway and ingress smoke failures expose command/status/request context but should not print raw JWTs, private keys, client secrets, authorization codes, or PKCE verifiers.

**Test data:**

```text
workflow: .github/workflows/ci.yml
branches: main, feature/**
expected permission: contents: read
```

## Test Data (based on test design techniques)

### Positive

* Authenticated owner with verified `api_resource` site `example.test`.
* Authenticated owner with verified `web_client` site and exact redirect URI.
* Active signing key plus prepared next signing key.
* Valid user API JWT for audience `example.test` and scope `orders.read`.
* Gateway protected request through `http://127.0.0.1:8081/protected`.

### Negative

* Exceeded endpoint limit after setting a per-minute value to `1`.
* Invalid limiter env value `not-an-int`.
* Token with unknown `kid`.
* Token with wrong audience `wrong.example.test`.
* Token with invalid signature.
* Token with expired `exp`.
* Token with future `nbf`.
* JWKS unavailable while gateway needs a refresh.
* Destructive force-retire command without confirmation.

### Edge

* Non-expiring API token with `expires_at=null` blocking normal key retirement.
* Array audience containing both wrong audience and `example.test`.
* Gateway JWKS cache hit before TTL expiry and refresh after TTL expiry.
* Portal form throttling with browser redirect instead of JSON.
* Restore drill evidence that must prove behavior without storing raw secrets.
