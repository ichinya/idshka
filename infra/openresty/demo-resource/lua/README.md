# Lua Modules

Этот каталог содержит runtime-модули OpenResty gateway reference для demo API resource.

## Modules

- `jwks_cache.lua` — читает public JWKS через internal Nginx subrequest и кеширует keys по `kid` в `lua_shared_dict` только до explicit `expires_at`; unknown `kid` triggers refresh, stale keys are deleted before validation, and JWKS fetch/decode failures fail closed.
- `jwt_validate.lua` — парсит Bearer JWT, проверяет `alg`, `kid`, подпись RS256 через explicit `openssl` runtime dependency, `iss`, `aud`, optional `exp`, `nbf`, `token_type`, `scope`, `permissions`, `jti`.
- `context_headers.lua` — удаляет входящие `X-Idshka-*` и `Authorization`, затем выставляет trusted `X-Idshka-*` headers для upstream.

## Deferred

- `context_sign.lua` не включен в текущий slice. Signed context остается config-gated hardening для слабой network boundary.
- Edge revoke cache / online introspection остаются будущей security-hardening фазой.
