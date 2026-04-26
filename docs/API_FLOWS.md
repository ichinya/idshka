[← Previous Page](LARAVEL_MODULES.md) · [Back to README](../README.md) · [Next Page →](GATEWAY_CONTRACT.md)

# API Flows

Документ разделяет уже реализованные HTTP-сценарии и целевые flows следующих фаз. По состоянию репозитория рабочими являются endpoints session auth + Socialite из `routes/web.php`, site registry и user API token endpoints из `routes/api.php`, а также public JWKS endpoint из `routes/oauth.php`.

## Реализовано сейчас: session auth + Socialite + site registry + issuer API tokens/JWKS

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

## Issuer API token flow

### Выпуск user API token для `api_resource`

```http
POST https://idshka.ru/api/v1/user/api-tokens
```

Issue flow:

1. Требуется аутентифицированный owner (`auth:web`).
2. `site_id` должен быть verified, принадлежать owner и иметь mode `api_resource`.
3. Requested `scopes`/`permissions` проходят strict allow-list validation.
4. В ответ возвращается raw JWT один раз + metadata (`token_id`, `jti`, `kid`, `expires_at`).
5. В БД хранится только metadata/hash, без raw token.

Revoke flow:

```http
POST https://idshka.ru/api/v1/user/api-tokens/{id}/revoke
```

1. Только owner токена может выполнить revoke.
2. Revoke idempotent: повторный вызов возвращает ok без дублей.
3. На revoke пишется `api_tokens.revoked_at` и `revoked_jti`.
4. Redis denylist используется как best-effort cache-accelerator; БД остается source of truth, а revoke response не зависит от доступности cache backend.

### Gateway validation через JWKS

Реализованный OpenResty reference в `infra/openresty/apishka/`:
- проверяет Bearer JWT по public JWKS через internal Docker URL `http://nginx/oauth/jwks.json`;
- валидирует `alg=RS256`, `kid`, подпись, `iss`, `aud=apishka.ru`, `exp`, `nbf`, `sub`, `site_id`, `token_type=user_api`, `scope`, `permissions`, `jti`;
- удаляет входящие `X-Idshka-*` и `Authorization`;
- выставляет trusted context в upstream `apishka-api`;
- возвращает deterministic JSON errors с `error`, `message`, `request_id`.

JWKS опубликован в Laravel:

```http
GET https://idshka.ru/oauth/jwks.json
```

Endpoint работает через stateless `api` middleware, не должен выставлять session/CSRF cookies и отдает только public JWK fields.

Gateway smoke:

```bash
docker compose up -d --build
bash infra/openresty/apishka/smoke.sh
```

Smoke покрывает valid token, missing token, invalid signature, wrong audience, expired/not-before token и header sanitization.

Deterministic issuer errors:

- Body: `{ "error": "...", "message": "...", "request_id": "..." }`
- `401` authentication_required
- `403` owner/mode/eligibility violations
- `422` validation_failed / invalid_scope / invalid_permissions
- `503` signing_key_* errors

Deterministic gateway errors:

- Body: `{ "error": "...", "message": "...", "request_id": "..." }`
- `401` missing_token
- `401` invalid_token
- `401` expired_token
- `401` audience_mismatch
- `502` jwks_unavailable

### Web login through OAuth / OIDC-like flow

Implemented in plan `06-web-login-through-idshka`.

Public endpoints:

```http
GET  https://idshka.ru/oauth/authorize
POST https://idshka.ru/oauth/token
GET  https://idshka.ru/oauth/userinfo
GET  https://idshka.ru/oauth/jwks.json
```

Authorize request:

```http
GET https://idshka.ru/oauth/authorize?response_type=code&client_id=client_...&redirect_uri=https%3A%2F%2Fapishka.ru%2Fauth%2Fidshka%2Fcallback&scope=openid+profile+email&state=<state>&nonce=<nonce>&code_challenge=<s256>&code_challenge_method=S256
Cookie: laravel_session=<idshka-session>
```

Rules:

- `response_type` must be `code`.
- `redirect_uri` must exactly match a registered URI for the active client.
- Client site must be verified and have `web_client` mode enabled.
- `scope` must include `openid`; supported scopes are `openid`, `profile`, `email`.
- PKCE supports only `S256`.

Successful authorize redirects to the registered callback with `code` and the original `state`. Authorization codes are stored only as SHA-256 hashes, are short-lived, are bound to client/user/site/redirect URI/nonce/PKCE metadata, and can be consumed once.

Token exchange:

```http
POST https://idshka.ru/oauth/token
Content-Type: application/json

{
  "grant_type": "authorization_code",
  "client_id": "client_...",
  "client_secret": "<secret>",
  "code": "<authorization-code>",
  "redirect_uri": "https://apishka.ru/auth/idshka/callback",
  "code_verifier": "<pkce-verifier>"
}
```

Response:

```json
{
  "access_token": "<jwt>",
  "id_token": "<jwt>",
  "token_type": "Bearer",
  "expires_in": 600,
  "scope": "email openid profile"
}
```

`id_token` is signed with RS256 and contains `iss`, `aud=client_id`, `sub`, `site_id`, `client_id`, `nonce`, `token_type=id_token`, `iat`, `nbf`, `exp`, `jti`, `kid` and `typ=JWT`. The web access token has `token_type=web_access` and is accepted by `GET /oauth/userinfo`.

Userinfo:

```http
GET https://idshka.ru/oauth/userinfo
Authorization: Bearer <access_token>
```

Response includes `sub`, plus `name` for `profile` scope and `email` for `email` scope.

Deterministic OAuth errors:

- Body: `{ "error": "...", "message": "...", "request_id": "..." }`
- `401` invalid client, invalid grant, missing/invalid bearer token
- `403` unverified site or missing `web_client` mode
- `422` validation failure, invalid scope or redirect URI mismatch
- `429` throttle limit

Security logging rule: raw authorization codes, client secrets, PKCE verifiers, JWTs and private key material are never logged. Logs use request id, client id, site id, user id, jti and code/hash prefixes only.

## See Also

- [Gateway Contract](GATEWAY_CONTRACT.md) — gateway/JWKS contract для API-only режима
- [Socialite](SOCIALITE.md) — текущая роль Socialite в login/link flow
- [Laravel Modules](LARAVEL_MODULES.md) — где в коде живёт site registry slice
