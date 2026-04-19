# Area: web_client

- Redirect URI matching должен быть строгим, без wildcard в MVP.
- `state` обязателен, хранится в session клиента.
- `nonce` обязателен для id_token.
- Authorization code должен быть одноразовым.
- `client_secret` показывать владельцу один раз и хранить хэш/зашифрованную версию по выбранной модели.
- Laravel `apishka-web` example может использовать custom Socialite provider для `idshka.ru`.
