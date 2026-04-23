[← Previous Page](LARAVEL_MODULES.md) · [Back to README](../README.md) · [Next Page →](GATEWAY_CONTRACT.md)

# API Flows

Документ разделяет уже реализованные HTTP-сценарии и целевые flows следующих фаз. Это важно: по состоянию репозитория рабочими являются endpoints session auth + Socialite из `routes/web.php` и site registry из `routes/api.php`; `routes/oauth.php` пока содержит только planned issuer endpoints.

## Реализовано сейчас: session auth + Socialite + site registry

### Модель аутентификации и защиты

- Site registry endpoints работают под `web` + `auth:web`.
- Для browser-based state-changing запросов нужна обычная Laravel session плюс CSRF token.
- Для site-bound endpoints дополнительно включён `can:manage,site`.
- На write endpoints действует limiter `site-registry` (`30` запросов в минуту на user id / IP).

### Session auth и Socialite login

- `POST /register` — регистрация + автоматический session login.
- `POST /login` — email/password login (`throttle:auth-login`).
- `POST /logout` — завершение session.
- `GET /auth/{provider}/redirect` — старт Socialite login flow (`google`, `vk`, `yandex`).
- `GET /auth/{provider}/callback` — callback Socialite flow.
- `GET /auth/{provider}/link` и `DELETE /auth/{provider}/link` — link/unlink Socialite account для уже аутентифицированного пользователя.

Ошибки для Socialite/session-auth flow:
- `401` invalid credentials или provider denied auth
- `404` unsupported provider
- `409` social account/email conflict
- `419` expired/mismatched social state
- `429` limiter for auth endpoints

### Создать сайт

```http
POST https://idshka.ru/api/v1/sites
Cookie: laravel_session=<session-cookie>
X-CSRF-TOKEN: <csrf-token>
Content-Type: application/json

{
  "domain": "apishka.ru",
  "display_name": "Apishka"
}
```

Ответ:

```json
{
  "site_id": "site_01kpv62acyvkq3cav4dzhmhdr7",
  "domain": "apishka.ru",
  "verified": false,
  "verification": {
    "dns_txt_name": "_idshka.apishka.ru",
    "dns_txt_value": "idshka-site-verification=<token>",
    "file_url": "https://apishka.ru/.well-known/idshka-site-verification.txt",
    "file_body": "<token>",
    "expires_at": "2026-04-22T22:30:00Z"
  }
}
```

### Проверить домен

```http
POST https://idshka.ru/api/v1/sites/{site}/verify
Cookie: laravel_session=<session-cookie>
X-CSRF-TOKEN: <csrf-token>
Content-Type: application/json

{ "method": "dns_txt" }
```

Допустимые значения `method`:
- `dns_txt`
- `file`

Ответ:

```json
{
  "site_id": "site_01kpv62acyvkq3cav4dzhmhdr7",
  "method": "dns_txt",
  "status": "verified",
  "verified": true,
  "error_code": null
}
```

### Включить режим сайта

```http
POST https://idshka.ru/api/v1/sites/{site}/modes/api_resource
Cookie: laravel_session=<session-cookie>
X-CSRF-TOKEN: <csrf-token>
Content-Type: application/json

{}
```

Поддерживаемые mode values:
- `api_resource`
- `web_client`

Ответ:

```json
{
  "site_id": "site_01kpv62acyvkq3cav4dzhmhdr7",
  "mode": "api_resource",
  "enabled_at": "2026-04-22T22:00:00Z"
}
```

### Поведение ошибок

| Status | Когда возвращается |
|--------|--------------------|
| `401` | нет аутентифицированной web session |
| `403` | пользователь не владеет сайтом или site ещё не verified |
| `409` | verified domain уже закреплён за другим owner |
| `422` | невалидный payload, unknown `method` или unsupported `mode` |
| `429` | сработал limiter `site-registry` |

## Planned later: issuer flows

Ниже — не текущие endpoints, а целевые сценарии следующих планов.

### Выпуск user API token для `api_resource`

Будущий owner/user API:

```http
POST https://idshka.ru/api/v1/user/api-tokens
```

Этот endpoint ещё не реализован в текущем `routes/api.php`.

### Gateway validation через JWKS

Целевой runtime contract:
- gateway проверяет JWT по JWKS;
- валидирует `iss`, `aud`, `exp`, `nbf`, `jti`;
- удаляет входящие `X-Idshka-*` и выставляет trusted context upstream.

Пока это design contract; рабочий JWKS endpoint ещё не опубликован.

### Web login через OAuth / OIDC-like flow

Планируется в следующих фазах:
- `GET /oauth/authorize`
- `POST /oauth/token`
- `GET /.well-known/...` / `GET /oauth/jwks.json`
- `GET /oauth/userinfo`

Сейчас `routes/oauth.php` содержит только placeholder.

## See Also

- [Gateway Contract](GATEWAY_CONTRACT.md) — целевой contract для API-only режима
- [Socialite](SOCIALITE.md) — текущая роль Socialite в login/link flow
- [Laravel Modules](LARAVEL_MODULES.md) — где в коде живёт site registry slice
