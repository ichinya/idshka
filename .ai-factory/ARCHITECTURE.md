# ARCHITECTURE

## Архитектурный стиль
`idshka.ru` строится как **identity/control plane**, а подключённые сайты (`apishka.ru` и другие) — как внешние consumers.

В MVP репозиторий может быть monorepo, но архитектурная граница такая:

- `idshka-api` — источник истины по пользователям, сайтам, ключам, токенам, клиентам и аудитам.
- `idshka-portal` — личный кабинет и self-service UI.
- `connected site` — внешний сайт владельца; в репозитории допускаются только examples/adapters.
- `edge gateway` — слой на стороне подключённого API-сайта, который валидирует токен до upstream.

## Два режима подключения сайта

### Mode A: `api_resource`
`apishka.ru` — API-only.

```text
Пользователь
  │
  │ 1. входит на idshka.ru и создаёт API token для apishka.ru
  ▼
idshka-portal ───► idshka-api ───► PostgreSQL / Redis
                       │
                       ├──► /oauth/jwks.json
                       └──► /v1/tokens

Клиент / скрипт пользователя
  │
  │ 2. Authorization: Bearer <idshka_jwt>
  ▼
api.apishka.ru / OpenResty Gateway
  │ 3. проверка подписи/JWKS/iss/aud/exp/jti
  │ 4. очистка входящих X-Idshka-*
  │ 5. добавление X-Idshka-* и/или signed context
  ▼
apishka-api upstream
```

Поведение:
- токен выпускает только `idshka-api`;
- `aud` токена равен audience подключённого сайта, например `apishka.ru` или `site_apishka`;
- gateway на `apishka.ru` проверяет токен до upstream;
- upstream принимает auth-context только от gateway.

### Mode B: `web_client`
`apishka.ru` — web-сайт с полноценным входом через `idshka.ru`.

```text
Браузер пользователя
  │
  │ 1. GET https://apishka.ru/login
  ▼
apishka-web
  │
  │ 2. redirect to https://idshka.ru/oauth/authorize?...client_id=...
  ▼
idshka.ru
  │
  │ 3. login / consent
  ▼
apishka.ru/callback
  │
  │ 4. exchange code -> tokens
  │ 5. validate id_token / create local session
  ▼
apishka-web session
```

Поведение:
- `apishka.ru` регистрируется как OIDC client;
- владелец сайта задаёт redirect URI;
- используется Authorization Code + PKCE;
- `idshka.ru` возвращает `id_token` и access token;
- `apishka.ru` создаёт свою локальную session cookie.

## Основные компоненты

### `idshka-api`
Bounded contexts:
- Identity: пользователи, логин, сессии `idshka.ru`.
- Site Registry: подключённые домены, режимы, verification status.
- API Resource Registry: audiences, scopes, permissions для API-only режима.
- OIDC Client Registry: client_id, client_secret, redirect URI для web-client режима.
- Token Issuer: выпуск JWT, refresh/exchange flows при необходимости.
- Key Management: private keys, JWKS, rotation lifecycle.
- Audit: token created/used/revoked, login started/completed, site verified.

### `idshka-portal`
Назначение:
- регистрация/вход пользователя в `idshka.ru`;
- подключение домена;
- выбор режимов `api_resource` / `web_client`;
- управление scopes/permissions;
- создание токенов;
- управление client credentials;
- revoke и audit.

### `apishka-edge` / gateway example
Назначение:
- публичная точка входа в API-only режим;
- локальная проверка JWT по JWKS;
- опциональный online introspection fallback;
- нормализация прав в upstream contract.

Gateway обязан:
1. Требовать `Authorization: Bearer <token>`.
2. Проверять `alg`, `kid`, подпись, `iss`, `aud`, `exp`, `nbf`.
3. Проверять `jti` по denylist, если включён revoke cache.
4. Удалять все входящие `X-Idshka-*` headers.
5. Добавлять собственные `X-Idshka-*` headers.
6. При необходимости добавлять подписанный context envelope.
7. Fail closed при любой ошибке.

### `apishka-api` / upstream example
Назначение:
- бизнес-логика подключённого API;
- чтение `X-Idshka-*` или проверка `X-Idshka-Context-Signature`;
- выполнение permission checks по уже подтверждённому контексту.

Ограничение:
- upstream недоступен напрямую из интернета;
- если upstream всё же находится в недоверенной сети, он обязан проверять signed context.

## Контракт токена для API-only режима

Header:
- `alg`: `RS256` или `EdDSA`, выбранный в Key Management.
- `kid`: id активного public key.
- `typ`: `JWT`.

Claims v1:
- `iss`: `https://idshka.ru`
- `aud`: audience подключённого API-сайта, например `apishka.ru`
- `sub`: id пользователя на `idshka.ru`
- `site_id`: id подключённого сайта
- `token_type`: `user_api`
- `jti`: уникальный id токена
- `iat`, `nbf`, `exp`
- `scope`: строка scopes через пробел
- `roles`: массив ролей
- `permissions`: массив детальных разрешений
- `email`: опционально
- `name`: опционально

TTL:
- default: 1 час;
- max для прямого JWT MVP: 24 часа;
- для долгоживущего доступа позже вводится opaque token + exchange.

## Контракт web-client / OIDC

Endpoints `idshka.ru`:
- `GET /.well-known/openid-configuration`
- `GET /oauth/authorize`
- `POST /oauth/token`
- `GET /oauth/jwks.json`
- `GET /oauth/userinfo`
- `POST /oauth/revoke`

OIDC claims:
- `iss`
- `aud` = `client_id`
- `sub`
- `email`
- `email_verified`
- `name`
- `auth_time`
- `iat`
- `exp`
- `nonce`

Flow:
- Authorization Code + PKCE.
- Strict redirect URI matching.
- `state` required.
- `nonce` required for id_token.

## Upstream headers для API-only режима
Gateway выставляет:

- `X-Idshka-Authenticated: 1`
- `X-Idshka-User-Id: <sub>`
- `X-Idshka-Site-Id: <site_id>`
- `X-Idshka-Audience: <aud>`
- `X-Idshka-Email: <email>`
- `X-Idshka-Roles: role1,role2`
- `X-Idshka-Scopes: orders.read orders.write`
- `X-Idshka-Permissions: orders.read,orders.write`
- `X-Idshka-JTI: <jti>`
- `X-Idshka-Token-Exp: <unix_ts>`
- `X-Request-Id: <request_id>`

Optional signed context:

- `X-Idshka-Context: <base64url-json>`
- `X-Idshka-Context-Signature: v1=<hmac-sha256>`
- `X-Idshka-Context-Timestamp: <unix_ts>`

Context JSON:

```json
{
  "iss": "https://idshka.ru",
  "gateway": "api.apishka.ru",
  "sub": "usr_123",
  "site_id": "site_apishka",
  "aud": "apishka.ru",
  "roles": ["user"],
  "scopes": ["orders.read"],
  "permissions": ["orders.read"],
  "jti": "tok_123",
  "exp": 1730412345,
  "request_id": "req_123"
}
```

## Данные и таблицы

Core tables:
- `users`
- `user_sessions`
- `sites`
- `site_verifications`
- `site_modes`
- `api_resources`
- `api_scopes`
- `api_permissions`
- `api_tokens`
- `oidc_clients`
- `oidc_redirect_uris`
- `signing_keys`
- `revoked_jti`
- `audit_events`

## Репозиторий

```text
apps/
  idshka-api/
  idshka-portal/

examples/
  apishka-api/
  apishka-web/

infra/
  openresty/
    apishka/
      nginx.conf
      lua/
        jwt_validate.lua
        jwks_cache.lua
        context_sign.lua
        denylist.lua

packages/
  contracts/
    jwt/
    headers/
    oidc/
    openapi/
  shared-config/
  test-fixtures/

docs/
  API_FLOWS.md
  GATEWAY_CONTRACT.md
```

## Нефункциональные требования
- Fail closed по auth.
- Никакие raw tokens/secrets не пишутся в логи.
- Все изменения прав и ключей аудитятся.
- JWKS key rotation без downtime.
- `X-Idshka-*` всегда создаются gateway-слоем, а не клиентом.
- Для web-client flow обязательны state, nonce и PKCE.
- У каждого подключённого сайта явный режим: `api_resource`, `web_client` или оба.
