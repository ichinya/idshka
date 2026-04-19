# ARCHITECTURE

## Архитектурный стиль
`idshka.ru` строится как **Laravel modular monolith**: один Laravel-проект содержит личный кабинет, Socialite-login, site registry, token issuer, JWKS, OAuth/OIDC-like endpoints и audit.

Подключённые сайты (`apishka.ru` и другие) остаются внешними consumers.

Архитектурные границы:

- `idshka.ru Laravel app` — источник истины по пользователям, сайтам, ключам, токенам, клиентам и аудитам.
- `idshka portal` — Blade/Livewire/Alpine UI внутри Laravel.
- `connected site` — внешний сайт владельца; в репозитории допускаются только examples/adapters.
- `edge gateway` — слой на стороне подключённого API-сайта, который валидирует токен до upstream.

## Где используется Laravel Socialite

### Вход на `idshka.ru` через внешние сервисы
```text
Пользователь
  │
  │ 1. Нажимает «Войти через Google/VK/Yandex»
  ▼
idshka.ru / Laravel
  │
  │ 2. Socialite redirect
  ▼
External provider
  │
  │ 3. login / consent
  ▼
idshka.ru / auth/{provider}/callback
  │
  │ 4. Socialite получает provider user
  │ 5. Laravel создаёт/обновляет user + social_account
  ▼
Portal session
```

Socialite отвечает только за внешнюю авторизацию пользователя на `idshka.ru`.

### Вход `apishka.ru` через `idshka.ru`
`idshka.ru` сам становится provider/issuer. Для этого нужны отдельные Laravel endpoints `/oauth/authorize`, `/oauth/token`, `/oauth/jwks.json`. Если `apishka.ru` тоже Laravel, он может использовать кастомный Socialite provider для `idshka.ru`, но это уже код на стороне `apishka.ru`.

## Два режима подключения сайта

### Mode A: `api_resource`
`apishka.ru` — API-only.

```text
Пользователь
  │
  │ 1. входит на idshka.ru и создаёт API token для apishka.ru
  ▼
idshka.ru Portal ───► Laravel controllers/services ───► PostgreSQL / Redis
                             │
                             ├──► /oauth/jwks.json
                             └──► /v1/user/api-tokens

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
- токен выпускает только Laravel app `idshka.ru`;
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
idshka.ru / Laravel
  │
  │ 3. login через session/email/socialite + consent
  ▼
apishka.ru/callback
  │
  │ 4. exchange code -> tokens
  │ 5. validate id_token / create local session
  ▼
apishka-web session
```

Поведение:
- `apishka.ru` регистрируется как web client;
- владелец сайта задаёт redirect URI;
- используется Authorization Code + PKCE;
- `idshka.ru` возвращает `id_token` и access token;
- `apishka.ru` создаёт свою локальную session cookie.

## Laravel bounded contexts

Рекомендуемая структура внутри `app/Domain`:

```text
app/
  Domain/
    Identity/          # users, sessions, Socialite identities
    Sites/             # domain registry, verification, modes
    ApiResources/      # audience, scopes, permissions
    OidcClients/       # client_id, client_secret, redirect URIs
    Issuer/            # JWT, JWKS, authorization codes, token endpoint
    Gateway/           # context/signature helpers, contracts
    Audit/             # audit events
  Http/
    Controllers/
      Auth/            # Socialite redirects/callbacks
      Portal/          # Blade/Livewire portal pages
      Api/             # owner/user API
      OAuth/           # authorize/token/userinfo/jwks/revoke
    Middleware/
  Support/
```

## Основные компоненты

### Laravel app `idshka.ru`
Bounded contexts:
- Identity: пользователи, Laravel sessions, Socialite accounts.
- Site Registry: подключённые домены, режимы, verification status.
- API Resource Registry: audiences, scopes, permissions для API-only режима.
- OIDC Client Registry: client_id, client_secret, redirect URI для web-client режима.
- Token Issuer: выпуск JWT access/id tokens.
- Authorization Server: authorize endpoint, token endpoint, PKCE, authorization codes.
- Key Management: private keys, JWKS, rotation lifecycle.
- Audit: token created/used/revoked, login started/completed, site verified.

### Portal
Назначение:
- регистрация/вход пользователя в `idshka.ru`;
- Socialite вход и привязка внешних аккаунтов;
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
- если upstream находится в недоверенной сети, он обязан проверять signed context.

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

## Контракт web-client / OIDC-like flow

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

## Данные и таблицы
Core tables:
- `users`
- `user_sessions`
- `social_accounts`
- `sites`
- `site_verifications`
- `site_modes`
- `api_resources`
- `api_scopes`
- `api_permissions`
- `api_tokens`
- `oidc_clients`
- `oidc_redirect_uris`
- `oauth_authorization_codes`
- `oauth_refresh_tokens`
- `signing_keys`
- `revoked_jti`
- `audit_events`

## Репозиторий

```text
app/
  Domain/
  Http/
  Support/
bootstrap/
config/
database/
  migrations/
  seeders/
docs/
  API_FLOWS.md
  GATEWAY_CONTRACT.md
infra/
  docker/
  openresty/
    apishka/
      nginx.conf
      lua/
resources/
  views/
  js/
  css/
routes/
  web.php
  api.php
  oauth.php
examples/
  apishka-api/
  apishka-web-laravel/
tests/
  Feature/
  Unit/
```

## Security boundaries
- Browser session on `idshka.ru` is first-party Laravel session.
- Socialite provider access tokens are not used as `idshka` API tokens.
- API-only tokens are issued by `idshka.ru` and scoped to connected site audience.
- Gateway headers are trusted only if they originate from the gateway private boundary.
- Client secrets, private keys and raw tokens are never logged.
