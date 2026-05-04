[← Previous Page](OPERATIONS.md) · [Back to README](../README.md)

# Security Runbook

Use these procedures when a credential, token, signing key, Laravel secret or gateway trust boundary may be compromised. Evidence should use `request_id`, `user_id`, `site_id`, `client_id`, `token_id`, `jti`, `kid`, timestamps and status codes. Do not paste raw tokens, client secrets, authorization codes, PKCE verifiers, Socialite provider tokens, decrypted PEM or `APP_KEY` values into tickets, logs or chat.

## Shared Triage

1. Open an incident ticket and assign an incident commander.
2. Capture the earliest known exposure time, affected user/site/client ids and relevant `request_id` values.
3. Preserve application logs and audit events with safe identifiers only.
4. Decide whether the affected credential can be revoked directly or whether replacement and downstream coordination are required.
5. After containment, add regression tests or operational checks for the path that failed.

## Leaked API token

Signals:

- Raw Bearer token was pasted into logs, tickets or a browser console.
- Gateway accepted traffic with a suspicious `jti`, `site_id`, `kid` or audience.

Containment:

1. Identify token metadata by hash when available, or by safe claims such as `jti`, `kid`, `site_id`, `sub` and `aud`.
2. Revoke the token through the portal or API owner flow.
3. Confirm `api_tokens.revoked_at` is set and Redis revoke denylist was updated best-effort.
4. Ask the owner to issue a replacement token and rotate it in the connected API client.

Evidence:

- Record `token_id`, `jti`, `kid`, `site_id`, `request_id`, revoke timestamp and gateway status codes.
- Do not store the raw JWT.

## Leaked client secret

Current handling is revoke and recreate, not in-place secret rotation.

Containment:

1. Find the affected OIDC client by `client_id` and `site_id`.
2. Revoke the client in the portal or database-backed admin path.
3. Create a replacement client and exact redirect URIs.
4. Share the new secret through the approved secret manager only.
5. Invalidate pending authorization codes for the revoked client where practical.

Evidence:

- Record `client_id`, `site_id`, redirect URI ids, revoke timestamp, replacement client id and `request_id`.
- Do not store the leaked secret value.

## Leaked authorization code

Authorization codes are one-time, short-lived and stored as hashes.

Containment:

1. Identify the flow by `client_id`, `site_id`, redirect URI hash prefix, code hash prefix and `request_id`.
2. If the code has not been consumed, expire or delete the hashed authorization code row.
3. If the code was consumed, review issued `id_token` and web access token `jti` values from the same request and revoke downstream sessions where applicable.
4. Check for repeated invalid grant attempts from the same IP or client.

Evidence:

- Record code hash prefix, `client_id`, `site_id`, `user_id`, related token `jti` values and timestamps.
- Do not store the raw authorization code or PKCE verifier.

## Leaked Socialite provider token

Socialite provider tokens may be encrypted with Laravel encryption using `APP_KEY`.

Containment:

1. Identify the affected `social_accounts` row by provider, provider user id and local `user_id`.
2. Unlink or refresh the provider account according to provider capabilities.
3. Revoke the provider token at the upstream provider console when available.
4. Force a fresh Socialite login for the affected user.

Evidence:

- Record provider name, provider user id, local `user_id`, `request_id` and upstream revoke result.
- Do not store provider access or refresh token values.

## Leaked signing key

A leaked signing key means the private key material for a `kid` may be usable to mint tokens.

Containment:

1. Identify the affected `kid` and active token population.
2. Run `php artisan idshka:keys:prepare` and `php artisan idshka:keys:activate-next` if a clean next key is not already active.
3. Run `php artisan idshka:keys:force-retire <kid> --force` for the compromised key.
4. Clear and rebuild JWKS cache, then confirm `/oauth/jwks.json` no longer publishes the retired `kid`.
5. Revoke or expire token metadata signed by the compromised `kid` based on blast radius.
6. Coordinate gateway cache refresh and fail closed for unknown or retired `kid` values.

Evidence:

- Record `kid`, key ids, activation/retirement timestamps, JWKS status, affected `jti` counts and `request_id`.
- Do not store decrypted private key material.

## Compromised APP_KEY

`APP_KEY` protects encrypted application data, including encrypted Socialite tokens and signing private keys stored through Laravel encryption.

Containment:

1. Stop writes or put the system in maintenance mode.
2. Assume encrypted secrets protected by the old `APP_KEY` may be exposed.
3. Rotate signing keys and force retire keys encrypted under the compromised boundary after a clean key is active.
4. Revoke Socialite provider tokens and force fresh provider linking where required.
5. Revoke and recreate OIDC clients whose encrypted or derived secret handling may be affected.
6. Generate a new `APP_KEY` and re-encrypt retained data through an audited migration path.
7. Run restore and login drills before reopening traffic.

Evidence:

- Record config version, migration id, key rotation `kid` values, affected account counts and smoke-test status.
- Never record old or new `APP_KEY` values.

## Gateway header trust failure

A gateway header trust failure means upstream may have trusted client-supplied `X-Idshka-*` headers or accepted JWT context without required validation.

Containment:

1. Disable or isolate the affected gateway route.
2. Confirm the gateway strips inbound `X-Idshka-*` and `Authorization` before proxying.
3. Re-run gateway smoke for spoofed headers, invalid signature, wrong audience, expired token and unknown `kid`.
4. Review upstream logs for suspicious `site_id`, `user_id`, `jti`, `kid` and `request_id` combinations.
5. Revoke affected API tokens and force clients to reissue credentials when spoofed access cannot be bounded.

Evidence:

- Record gateway config version, failed smoke case, `request_id`, upstream status, `site_id`, `jti` and `kid`.
- Do not store raw JWTs from failed requests.

## Post-Incident Closure

- Add or update tests that would have failed before the incident.
- Update [Operations Runbook](OPERATIONS.md) if restore, key rotation or evidence handling changed.
- Update [Gateway Contract](GATEWAY_CONTRACT.md) when gateway validation behavior changes.
- Update [API Flows](API_FLOWS.md) when portal or OAuth behavior changes.
