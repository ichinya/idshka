# Task: 04-api-resource-gateway-for-apishka

## Implement
- nginx.conf
- lua/jwt_validate.lua
- lua/jwks_cache.lua
- lua/context_sign.lua
- header sanitation
- example upstream reads headers
- curl smoke tests

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
