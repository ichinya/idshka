# TECH_STACK

## Решение по стеку для MVP
Стек проекта: **Laravel + Socialite**. `idshka.ru` строится как Laravel modular monolith: один backend одновременно обслуживает личный кабинет, регистрацию сайтов, Socialite-login, выпуск токенов, JWKS и OAuth/OIDC-like endpoints.

## Runtime и язык
- Основной язык: **PHP**.
- Framework: **Laravel**.
- Package manager: **Composer**.
- Frontend assets: **Vite** + npm.
- Основной стиль разработки: Laravel modular monolith, без отдельного Node/Next/Fastify backend.

## Основное приложение `idshka.ru`

### Backend
- Framework: **Laravel**.
- Routing:
  - `routes/web.php` — portal, user login, Socialite redirects/callbacks, consent screens.
  - `routes/api.php` — site registry, user tokens, owner API.
  - `routes/oauth.php` или route group — `/oauth/*` и `/.well-known/*`.
- ORM: **Eloquent**.
- Migrations: Laravel migrations.
- Jobs/queues: Laravel Queues + Redis.
- Cache/rate limit: Redis.
- Events: Laravel events/listeners для audit log.
- Tests: Pest или PHPUnit; выбрать один и использовать последовательно.

### Вход пользователей на `idshka.ru`
- Session: secure httpOnly cookies.
- Базовый login scaffolding: Laravel Breeze, Fortify или собственные controllers.
- Social login: **Laravel Socialite**.
- Провайдеры MVP:
  - Google;
  - VK через community/custom provider;
  - Yandex через community/custom provider;
  - email/password как fallback.
- Таблица привязок: `social_accounts` или `user_identities`.
- Один пользователь может привязать несколько внешних провайдеров.

### Личный кабинет / Portal
- UI: Blade + Vite + Tailwind.
- Interactivity: Alpine.js или Livewire, если нужна динамика без SPA.
- Admin/internal CRUD option: MoonShine можно добавить позже, но MVP не должен зависеть от админки.
- Основные страницы:
  - мои сайты;
  - верификация домена;
  - режимы сайта;
  - API scopes/permissions;
  - web clients / redirect URI;
  - мои токены;
  - аудит и сессии.

## Issuer / OAuth / OIDC-like слой
Socialite не является OAuth/OIDC server. Поэтому provider-часть делается отдельными Laravel-сервисами.

### Эндпоинты
- `GET /.well-known/openid-configuration`
- `GET /oauth/authorize`
- `POST /oauth/token`
- `GET /oauth/jwks.json`
- `GET /oauth/userinfo`
- `POST /oauth/revoke`
- `POST /oauth/introspect` — optional для online-проверки gateway.

### Реализация
- `TokenIssuer` — выпуск JWT access/id tokens.
- `AuthorizationCodeService` — хранение и одноразовое использование authorization code.
- `PkceService` — проверка code challenge/verifier.
- `JwksService` — публикация public keys.
- `SigningKeyService` — генерация, хранение, ротация ключей.
- `RevocationService` — revoke и denylist по `jti`.
- `ConsentService` — согласие пользователя на вход/доступ сайта.

### JWT/JWK/JWKS
- Для MVP выбрать одну PHP-библиотеку для JWT/JWK/JWKS и зафиксировать в `composer.json`.
- Допустимые варианты:
  - `lcobucci/jwt` + собственная сериализация public JWK;
  - или JOSE/JWK framework, если нужен полный JWK/JWKS lifecycle.
- Алгоритм MVP: RS256 или EdDSA, один активный ключ + prepared next key.
- Private keys не логируются и не отдаются наружу.
- Public keys публикуются через JWKS.

## Database
Основной вариант: **PostgreSQL**.

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
- `oauth_refresh_tokens` — только если нужен refresh flow;
- `signing_keys`
- `revoked_jti`
- `audit_events`

MySQL допустим, если проект уже на MySQL, но SQL и индексы должны проектироваться без vendor lock-in там, где это не мешает безопасности.

## Edge для подключённых API-сайтов
`example.test` — внешний сайт пользователя. Его gateway-конфиг лежит как reference example.

- Основной вариант: **OpenResty** (`Nginx + Lua`).
- JWT validation: Lua module around JWT/JWKS verification.
- JWKS cache: shared dict + TTL.
- Denylist cache: Redis или online introspection, если нужен мгновенный revoke.
- Upstream context:
  - sanitized `X-Idshka-*` headers;
  - optional signed `X-Idshka-Context` + `X-Idshka-Context-Signature`.

## Примеры подключённых сайтов
В репозитории можно держать reference examples:

- `examples/demo-resource-api` — минимальный API, который читает `X-Idshka-*` и/или проверяет `X-Idshka-Context-Signature`.
- `examples/laravel-web-client` — Laravel web-сайт, который входит через `idshka.ru` с кастомным Socialite provider или обычным OAuth callback controller.
- `infra/openresty/demo-resource` — gateway-конфиг для API-only режима.

## Контракты и спецификации
Контракты не должны быть размазаны по коду.

Рекомендуемая структура:
- `app/Contracts/Auth/JwtClaims.php`
- `app/Contracts/Auth/GatewayHeaders.php`
- `app/Contracts/Auth/SignedContext.php`
- `app/Contracts/Auth/Scopes.php`
- `docs/API_FLOWS.md`
- `docs/GATEWAY_CONTRACT.md`
- `.ai-factory/specs/index.yaml`

## Инфраструктура local/dev
Docker Compose:
- nginx;
- php-fpm / Laravel app;
- PostgreSQL;
- Redis;
- OpenResty gateway example;
- optional `demo-resource-api` example.

Dev commands:
- `composer install`
- `npm install`
- `php artisan migrate`
- `php artisan test`
- `php artisan queue:work`
- `npm run dev`

## CI/CD
Обязательные jobs:
- composer validate/install;
- PHP lint/static analysis, если включён PHPStan/Larastan;
- tests;
- migrations smoke;
- npm build;
- gateway smoke tests через curl;
- docker build.

## Observability
- Logs: structured JSON logs with `request_id`, `user_id`, `site_id`, `jti`, `aud`.
- Metrics: Laravel-friendly Prometheus endpoint или reverse proxy metrics.
- Traces: OpenTelemetry можно добавить позже.
- Audit events обязательны для token/client/security действий.

## Security tooling
- Secret scanning в CI.
- Dependency audit для Composer и npm.
- Запрет логирования raw tokens, client secrets, private keys, authorization code и refresh token.
- Rate limit на login, token issue, authorize, token endpoint, introspection.

## Что можно заменить без ломки архитектуры
- PostgreSQL можно заменить на MySQL, если сохранить migrations/contracts.
- OpenResty можно заменить на Envoy/Kong/Traefik plugin, если сохранить gateway contract.
- Blade можно заменить на Inertia/Vue/React, если сохранить Laravel API и portal flows.
- JWT-библиотеку можно заменить, если сохранить claims, JWKS shape и key rotation lifecycle.
