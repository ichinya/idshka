# Task: 03-token-issuer-and-jwks

## Implement
- signing_keys schema
- api_tokens schema
- revoked_jti schema
- POST /v1/user/api-tokens
- GET /oauth/jwks.json
- POST /v1/user/api-tokens/{id}/revoke
- audit events

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
