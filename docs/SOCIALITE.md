# SOCIALITE

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
