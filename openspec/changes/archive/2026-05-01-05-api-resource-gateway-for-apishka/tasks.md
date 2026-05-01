# Tasks

## Migrated legacy tasks

# Task

1. T1: Gateway runtime dependencies и config surface
   - Зафиксировать Lua dependencies для JWT/JWKS verification.
   - Добавить config для `issuer`, `audience=apishka.ru`, internal `jwks_url`, allowed `alg`, JWKS TTL/stale policy.

2. T2: OpenResty routing skeleton и upstream boundary
   - Заменить `501 gateway_not_implemented` на proxy flow через Lua validation.
   - Сохранить public `/healthz`.
   - Все errors возвращать как deterministic JSON с `request_id`.

3. T3: `infra/openresty/apishka/lua/jwks_cache.lua`
   - Загружать JWKS через internal Docker URL.
   - Кешировать public keys в `lua_shared_dict`.
   - Выбирать key по `kid`; unknown `kid` fail closed.

4. T4: `infra/openresty/apishka/lua/jwt_validate.lua`
   - Проверять Bearer token, `alg`, `kid`, подпись, `iss`, `aud`, `exp`, `nbf`, `sub`, `site_id`, `token_type=user_api`, `scope`, `permissions`, `jti`.
   - Не логировать и не прокидывать raw JWT.

5. T5: `infra/openresty/apishka/lua/context_headers.lua`
   - Удалять входящие `X-Idshka-*`.
   - Выставлять trusted `X-Idshka-Authenticated/User-Id/Site-Id/Audience/Scopes/Permissions/JTI/Token-Exp` и `X-Request-Id`.

6. T6: Optional `context_sign.lua`
   - Реализовать только как config-gated feature.
   - Если не включается в этом slice, явно оставить signed context deferred.

7. T7: Minimal `apishka-api` smoke upstream
   - Добавить echo/consumer endpoint для проверки trusted headers.
   - Не возвращать raw token.

8. T8: Gateway smoke scripts и CI integration
   - Подготовить valid token fixture.
   - Проверить valid token, missing token, invalid signature, wrong aud, expired/nbf, header sanitization и upstream headers.
   - Добавить CI smoke или documented blocked reason.

9. T9: Docs sync и implementation notes
   - Обновить `docs/API_FLOWS.md`, `docs/GATEWAY_CONTRACT.md`, `docs/README.md`.
   - Обновить `infra/openresty/apishka/lua/README.md`.
   - Зафиксировать deferred items для plan `08`, если revoke cache/stale policy не реализуются полностью.
