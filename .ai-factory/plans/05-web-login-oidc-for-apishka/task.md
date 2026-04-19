# Task: 05-web-login-oidc-for-apishka

## Implement
- oidc_clients schema
- redirect uri registry
- GET /oauth/authorize
- POST /oauth/token
- id_token issue/validation
- example apishka-web callback
- state/nonce/PKCE checks

## Keep in mind
- Preserve naming: `idshka.ru`, `apishka.ru`, `X-Idshka-*`.
- Do not reintroduce the old misspelling spelling.
- Update contracts before implementation if public surface changes.
- Add or update smoke evidence.

## Deliverables
- Code/config changes.
- Contract/docs updates if needed.
- Tests or smoke commands.
- Short implementation note.
