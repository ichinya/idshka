[← Previous Page](GATEWAY_CONTRACT.md) · [Back to README](../README.md)

# SOCIALITE

> Статус: implemented slice для Socialite login/linking в `routes/web.php`; issuer/OIDC web-client endpoints реализованы в `routes/oauth.php`.

## Где используется Socialite
Socialite используется для входа пользователей на `idshka.ru` через внешние OAuth-провайдеры.

```http
GET https://idshka.ru/auth/google/redirect
GET https://idshka.ru/auth/google/callback?code=...&state=...
```

Аналогично:

```http
GET /auth/vk/redirect
GET /auth/vk/callback
GET /auth/yandex/redirect
GET /auth/yandex/callback
```

Дополнительно для уже аутентифицированной session:

```http
GET /auth/{provider}/link
DELETE /auth/{provider}/link
```

## Таблица `social_accounts`
Минимальные поля:

```text
id
user_id
provider
provider_user_id
email
name
avatar_url
access_token_encrypted nullable
refresh_token_encrypted nullable
expires_at nullable
created_at
updated_at
unique(provider, provider_user_id)
```

## Важная граница
Socialite не выпускает токены для `apishka.ru` и не публикует JWKS. Это делает `Issuer` domain внутри Laravel.

## `apishka.ru` как Laravel web-client
Если подключённый сайт тоже Laravel, для входа через `idshka.ru` можно сделать custom Socialite provider:

- authorization URL: `https://idshka.ru/oauth/authorize`
- token URL: `https://idshka.ru/oauth/token`
- userinfo URL: `https://idshka.ru/oauth/userinfo`
- scopes: `openid profile email`

Для MVP можно обойтись без отдельного provider package и написать обычный callback controller.

Текущий issuer flow:

1. `apishka.ru` генерирует `state`, `nonce`, PKCE verifier и S256 challenge.
2. Пользователь уходит на `GET https://idshka.ru/oauth/authorize`.
3. После first-party session login на `idshka.ru` callback получает one-time authorization code.
4. `apishka.ru` вызывает `POST https://idshka.ru/oauth/token` с client secret и PKCE verifier.
5. `apishka.ru` валидирует `id_token` через `GET /oauth/jwks.json`, вызывает `GET /oauth/userinfo` с web access token и открывает локальную session.

Refresh tokens в MVP отключены; web-client использует собственную локальную session.

## Что уже есть, а что ещё впереди

- Уже зафиксирована архитектурная граница: Socialite отвечает только за внешний login пользователя.
- Provider contract для `apishka.ru` как web-client реализован через Authorization Code + PKCE.
- Реальные `web` routes для auth/social flow уже реализованы.
- `oauth` routes для issuer/provider flow уже реализованы: authorize, token, userinfo и JWKS.

## See Also

- [API Flows](API_FLOWS.md) — текущие auth/site flows и будущие issuer flows
- [Gateway Contract](GATEWAY_CONTRACT.md) — чем Socialite не является и что будет делать issuer layer
- [Laravel Modules](LARAVEL_MODULES.md) — где позже появятся `Identity` и `Issuer`
