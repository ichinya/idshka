# Legacy Rules

## Legacy source

- .ai-factory/plans/06-web-login-through-idshka/rules.md

## Preserved content

# Rules

Apply these areas:
- web_client
- issuer
- laravel
- socialite
- security

Also apply global `.ai-factory/RULES.md`.

Plan-specific rules:
- Implement Authorization Code + PKCE only; do not add refresh tokens in this slice.
- Redirect URI matching must be exact. No wildcard, suffix or host-only matching.
- `state`, `nonce`, `code_challenge` and `code_challenge_method=S256` are mandatory.
- Authorization codes are short-lived, one-time, stored only as hashes and consumed under transaction/lock.
- Client secrets are shown only at creation time by future management flows and stored only as hash/encrypted data; token exchange verifies without exposing raw values.
- `id_token` and web access token must be signed by the existing issuer key infrastructure and include `kid`.
- Raw authorization codes, client secrets, id tokens, access tokens, private keys and Socialite provider tokens must never be logged.
- OAuth error responses must be deterministic and must include `request_id` where the endpoint returns JSON.
- Public OAuth/token/userinfo endpoints need explicit guards/middleware/rate limits at route declaration time.
- Feature tests are mandatory for all public OAuth endpoints and must include fail-closed security cases.
