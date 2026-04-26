# Lua Modules

Этот каталог содержит runtime-модули OpenResty gateway reference для `api.apishka.ru`.

## Modules

- `jwks_cache.lua` — читает public JWKS через internal Nginx subrequest и кеширует keys по `kid` в `lua_shared_dict`.
- `jwt_validate.lua` — парсит Bearer JWT, проверяет `alg`, `kid`, подпись RS256 через explicit `openssl` runtime dependency, `iss`, `aud`, `exp`, `nbf`, `token_type`, `scope`, `permissions`, `jti`.
- `context_headers.lua` — удаляет входящие `X-Idshka-*` и `Authorization`, затем выставляет trusted `X-Idshka-*` headers для upstream.

## Deferred

- `context_sign.lua` не включен в текущий slice. Signed context остается config-gated hardening для слабой network boundary.
- Edge revoke cache / online introspection остаются будущей security-hardening фазой.
