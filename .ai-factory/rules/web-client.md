# Area: web_client

## Scope
Web-сайт с входом через `idshka.ru`, например `apishka.ru/login` -> `idshka.ru/oauth/authorize`.

## Rules
- Использовать Authorization Code + PKCE.
- `state` обязателен и проверяется на callback.
- `nonce` обязателен и проверяется в `id_token`.
- Redirect URI должен совпадать точно с зарегистрированным.
- Client secret не должен попадать во frontend.
- Public SPA/mobile client обязан использовать PKCE и не хранить secret.
- После callback `apishka.ru` создаёт свою локальную session cookie.
- Logout/revoke semantics должны быть описаны отдельно.

## Forbidden
- Implicit flow в MVP запрещён.
- Password grant запрещён.
- Wildcard redirect URI запрещён.
