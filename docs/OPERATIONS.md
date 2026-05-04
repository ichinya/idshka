[← Previous Page](SOCIALITE.md) · [Back to README](../README.md) · [Next Page →](SECURITY_RUNBOOK.md)

# Operations Runbook

This runbook covers backup, restore, and restore drill evidence for the current `idshka.ru` Laravel monolith. It intentionally uses non-secret identifiers and evidence summaries without dumping raw secrets.

## Backup Scope

Back up these state sources together so they represent the same point in time:

| Area | Contents | Notes |
|------|----------|-------|
| PostgreSQL | users, social accounts, sites, modes, verification records, token metadata, revoked token state, OIDC clients, redirect URIs, authorization codes, audit events, signing keys | Database rows are the source of truth for identity, issuer, portal and audit state. |
| Redis revoke denylist | best-effort revoke denylist entries keyed by `jti` | Cache accelerates revoke checks; PostgreSQL remains authoritative. |
| storage | Laravel storage used by the app runtime | Do not include transient logs that contain request bodies. |
| runtime config | `.env`, deploy variables, Docker/CI runtime selections | Record variable names and checksums separately from secret values. |
| APP_KEY | Laravel encryption key | Required to decrypt encrypted Socialite provider tokens and private signing key material. |
| signing keys | encrypted private keys, public JWK metadata, status, `kid`, `activated_at`, `retired_at` | Stored in PostgreSQL; never export decrypted PEM in drill evidence. |

## Backup Procedure

1. Put the deployment into a controlled backup window or record the live timestamp and database snapshot isolation level.
2. Export PostgreSQL with a tool appropriate for the environment, for example `pg_dump` or a managed snapshot.
3. Capture runtime config names, config version, image tags and secret manager references. Do not print secret values into terminal logs.
4. Snapshot persistent `storage` volumes if the deployment stores runtime files there.
5. Record Redis revoke denylist expectations: Redis can be restored when available, but revoke safety must still be recoverable from PostgreSQL token metadata and `revoked_at`.
6. Store backup artifact ids, checksums, timestamps and operator `request_id` or ticket id in the operations log.

## Restore Procedure

1. Restore runtime config first, including the same `APP_KEY` value used by the backup.
2. Restore PostgreSQL and run migrations only after verifying the schema target.
3. Restore storage volumes if they are part of the deployment.
4. Start Laravel with queue/scheduler disabled until database integrity checks pass.
5. Rebuild cache state that is derived from PostgreSQL, including route/config cache and JWKS cache.
6. Restore Redis revoke denylist if an artifact exists; otherwise warm it from revoked token metadata where the implementation supports that path and rely on PostgreSQL as source of truth.
7. Re-enable traffic after smoke checks pass.

## Data Integrity Checks

Verify these tables or domain states after restore:

- users and linked Socialite accounts can load without decrypt errors.
- sites retain owner ids, normalized domains, verification status, and enabled modes.
- token metadata keeps `token_id`, `jti`, `kid`, expiry and revoke state, with no raw JWT stored.
- OIDC clients keep `client_id`, hashed client secret metadata and active/revoked state.
- redirect URIs are exact values with no wildcard expansion.
- authorization codes are hashed, short-lived and can be expired safely.
- audit events remain queryable by category, action, user, site and request id.

## JWKS cache rebuild

The public JWKS endpoint is derived from non-retired active and next signing keys in PostgreSQL.

1. Clear `issuer:jwks:public` from the configured Laravel cache backend.
2. Run `php artisan idshka:keys:status` and confirm the signing key, old active keys and prepared next key show expected `kid` values.
3. Request `GET /oauth/jwks.json` and confirm it returns public fields only: `kty`, `kid`, `alg`, `use`, `n`, `e`.
4. Never include decrypted private key material in restore drill evidence.

## Restore Drill Evidence

Each restore drill should capture:

- backup artifact ids, checksums and restore target.
- command names and pass/fail status, not command output containing secrets.
- sample safe identifiers: `request_id`, `user_id`, `site_id`, `client_id`, `token_id`, `jti`, `kid`.
- HTTP status and error codes from probes or smoke tests.
- confirmation that evidence was collected without dumping raw secrets.

## Redis Revoke Denylist Expectations

Redis revoke denylist entries are cache state. A missing Redis restore must not make a revoked token valid if PostgreSQL still has `api_tokens.revoked_at`.

Operationally:

- Prefer restoring Redis when the backup includes it.
- Treat Redis misses after restore as cache warmup events.
- Use PostgreSQL token metadata for incident decisions.
- Keep revoke evidence to `jti`, `token_id`, `kid`, `site_id`, `request_id` and timestamps.

## See Also

- [Security Runbook](SECURITY_RUNBOOK.md) — incident procedures for token, secret, key and gateway trust failures.
- [API Flows](API_FLOWS.md) — issuer and portal behavior that backups must preserve.
- [Gateway Contract](GATEWAY_CONTRACT.md) — JWKS and gateway validation contract.
