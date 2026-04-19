# 04-api-resource-gateway-for-apishka

## Цель
Собрать OpenResty gateway example, который проверяет токены idshka.ru и прокидывает доверенный context в apishka-api.

## Area
gateway, api_resource

## Что должно появиться
- nginx.conf
- lua/jwt_validate.lua
- lua/jwks_cache.lua
- lua/context_sign.lua
- header sanitation
- example upstream reads headers
- curl smoke tests

## Зависимости
`03-token-issuer-and-jwks`

## Acceptance criteria
- валидный токен проходит
- поддельный/expired/audience mismatch блокируется
- входящие X-Idshka-* не проходят
- signed context верифицируется в example upstream

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
