# Area: api_resource

## Scope
Подключённый API-only сайт, например `apishka.ru`.

## Rules
- API resource не доверяет клиентским auth headers.
- Primary auth source для upstream — gateway-provided context.
- Permission checks выполняются по scopes/permissions из context.
- Endpoint должен явно указывать required permissions.
- При отсутствии `X-Idshka-Authenticated: 1` upstream возвращает `401` или блокируется сетью до попадания в app.
- Для signed context проверять HMAC signature, timestamp и replay window.

## Forbidden
- Нельзя парсить public JWT в business handler как единственный auth механизм.
- Нельзя принимать `user_id` из query/body как auth identity.
