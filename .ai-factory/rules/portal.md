# Area: portal

## Scope
Личный кабинет `idshka.ru`.

## Rules
- Raw API token показывать один раз.
- Client secret показывать один раз или только rotate.
- Dangerous actions требуют подтверждения.
- UX должен явно показывать mode сайта: API-only, Web login или оба.
- В UI показывать последний usage/revoke/audit для tokens and clients.
- Snippets должны соответствовать текущим contracts.
- Не показывать приватные ключи.

## UX copy
- Пользователь должен понимать разницу: token для API-запросов vs web login для входа на сайт.
