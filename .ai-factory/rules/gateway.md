# Gateway Area Rules

## Scope
OpenResty/Nginx, Lua handlers, request sanitation, header injection.

## Правила
- Gateway — единственный источник доверенных `X-Idska-*` заголовков.
- Перед валидацией нужно удалить все входящие `X-Idska-*` от клиента.
- JWT проверяется локально по JWKS-кэшу; сетевой fallback допустим только как feature flag.
- При ошибке чтения ключа или claim mismatch поведение только fail closed.
- `iss`, `aud`, `exp`, `nbf`, `kid`, подпись — обязательная проверка.
- Denylist-проверка по `jti` должна быть дешёвой и не ломать happy path latency.
- Request ID создаётся на gateway, если не пришёл от доверенного ingress выше.

## Контракт с upstream
- Устанавливать только согласованный набор `X-Idska-*` заголовков.
- Не передавать в upstream внутренние детали JWKS-кэша.
- Ошибки auth возвращать в стабильном JSON-формате с `code` и `request_id`.
- `401` использовать для invalid/missing token, `403` — для policy-level deny при валидном токене.

## Запрещено
- Проксировать публичный запрос в upstream до завершения auth-проверки.
- Полагаться на regex-only JWT parsing без криптографической проверки.
- Использовать пользовательский `Host`/`X-Forwarded-*` как источник доверия без whitelist.
