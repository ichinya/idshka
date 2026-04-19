# 05-api-resource-gateway-for-apishka

## Цель
Собрать OpenResty gateway example, который проверяет токены idshka.ru и прокидывает доверенный context в apishka-api.

## Area
gateway, api_resource, security

## Что должно появиться
- infra/openresty/apishka/nginx.conf
- lua/jwks_cache.lua
- lua/jwt_validate.lua
- lua/context_headers.lua
- optional context_sign.lua
- curl smoke сценарии

## Зависимости
04-token-issuer-and-jwks

## Acceptance criteria
- валидный токен проходит до upstream
- неверная подпись возвращает 401
- неверный aud возвращает 401
- входящие X-Idshka-* затираются
- upstream получает X-Idshka-User-Id/Scopes/Permissions

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
