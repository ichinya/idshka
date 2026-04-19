# Area: api_resource

- API-only сайт принимает пользователя только через gateway context.
- Upstream не должен сам доверять заголовкам от публичного клиента.
- Проверки прав строятся по `X-Idshka-Scopes` и `X-Idshka-Permissions`.
- Scope mismatch — `403`, не `401`.
- Не прокидывать raw JWT в бизнес-логи downstream-сервиса.
