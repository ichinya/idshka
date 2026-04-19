# Area: gateway

- Gateway работает fail closed.
- Все входящие `X-Idshka-*` удаляются до проксирования.
- Gateway сам выставляет trusted `X-Idshka-*` после успешной проверки.
- Проверять `alg`, `kid`, подпись, `iss`, `aud`, `exp`, `nbf`, `token_type`.
- JWKS кешировать с TTL, но учитывать ротацию ключей.
- При слабой сетевой границе добавлять signed context.
- Upstream не должен быть доступен напрямую из интернета.
