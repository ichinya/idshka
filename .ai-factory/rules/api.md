# API Area Rules

## Scope
`apishka-api`, auth-context parsing, permission middleware, error semantics.

## Правила
- `apishka-api` работает только за доверенным gateway.
- Входящий auth-context сначала преобразуется в typed object `AuthContext`.
- Permission checks используют `permissions`/`scope` как источник истины; `roles` — вторичный слой.
- Business handlers не должны знать о формате JWT и `kid`.
- Каждая protected route явно декларирует нужные permissions.
- Ошибки доступа не должны раскрывать лишнюю security-sensitive информацию.

## Контракт маршрутов
- Не использовать raw header strings по всему коду — только shared helper/contract package.
- Все `401/403/429` должны иметь стабильный JSON-ответ.
- Все write-маршруты логируют `request_id`, `user_id`, `site_id`, `action`.

## Запрещено
- Парсить JWT в `apishka-api` как альтернативный путь.
- Делать fallback "если нет permissions, но есть role admin — пустить" без явного правила.
- Доверять `X-Idska-*`, если запрос пришёл не с внутреннего ingress.
