# idshka-api-token-inspector

Minimal HTTPS page for local API token testing.

Paste a Bearer token or raw JWT. The service verifies the signature against idshka JWKS, checks `iss`, `nbf`, and `exp` when present, then renders header, payload, expiration, scopes, permissions, audience, subject, site id, and JTI.

The submitted raw token is not rendered back into the page.

## Run

From the repository root:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose --profile examples up -d --build idshka-api-token-inspector
```

Open:

```text
https://localhost:8444
```

The inspector reuses the local `localhost` certificate volume used by `idshka-web-client`.

## Environment

| Variable | Default | Purpose |
| --- | --- | --- |
| `PORT` | `8444` | HTTPS listener port inside the container. |
| `IDSHKA_ISSUER` | `http://localhost:8080` | Expected `iss` claim. |
| `IDSHKA_JWKS_URL` | `http://nginx/oauth/jwks.json` | Container-facing JWKS endpoint. |
| `IDSHKA_JWKS_TIMEOUT_MS` | `15000` | JWKS fetch timeout before verification fails. |
| `IDSHKA_JWKS_RETRY_COUNT` | `1` | Retries for a JWKS timeout. |
| `IDSHKA_CLOCK_TOLERANCE_SECONDS` | `5` | Clock skew for JWT verification. |

When running under this repository's Compose network, keep:

```dotenv
IDSHKA_ISSUER=http://localhost:8080
IDSHKA_JWKS_URL=http://nginx/oauth/jwks.json
```
