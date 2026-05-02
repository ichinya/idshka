# idshka-web-client

Minimal HTTPS web client for logging in through idshka with Authorization Code + PKCE.

## Portal Settings

Create a verified `web_client` site in the idshka portal, create an OIDC client, and register this exact redirect URI:

```text
https://localhost:8443/auth/idshka/callback
```

Copy the issued `client_id` and one-time `client_secret`.

For local Docker testing, the issuer allows `localhost` site registration only when:

```dotenv
SITE_REGISTRY_ALLOW_LOOPBACK_DOMAINS=true
```

That flag is for local development only.

## Run

The idshka issuer should be running from the repository root:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
```

Then start the HTTPS client:

```bash
IDSHKA_CLIENT_ID=client_... \
IDSHKA_CLIENT_SECRET=secret_... \
IDSHKA_REDIRECT_URI=https://localhost:8443/auth/idshka/callback \
docker compose --profile examples up -d --build --no-deps idshka-web-client
```

Open:

```text
https://localhost:8443
```

The container generates a self-signed localhost certificate on first start. Your browser will show a local certificate warning.

## Environment

| Variable | Default | Purpose |
| --- | --- | --- |
| `IDSHKA_PUBLIC_URL` | `http://localhost:8080` | Browser-facing issuer URL for `/oauth/authorize`. |
| `IDSHKA_INTERNAL_URL` | same as `IDSHKA_PUBLIC_URL` | Container-facing issuer URL for `/oauth/token` and `/oauth/userinfo`. |
| `IDSHKA_CLIENT_ID` | empty | OIDC client id from the portal. |
| `IDSHKA_CLIENT_SECRET` | empty | OIDC client secret from the portal. |
| `IDSHKA_REDIRECT_URI` | `https://localhost:8443/auth/idshka/callback` | Exact registered callback URI. |
| `IDSHKA_SESSION_SECRET` | local demo fallback | Express session signing secret. |
| `IDSHKA_SCOPES` | `openid profile email` | Requested OAuth scopes. |

When running under this repository's Compose network, keep:

```dotenv
IDSHKA_PUBLIC_URL=http://localhost:8080
IDSHKA_INTERNAL_URL=http://nginx
```

The page stores only local session data, raw `/oauth/userinfo`, and non-secret token metadata. It does not render `access_token`, `id_token`, authorization `code`, client secret, or PKCE verifier.
