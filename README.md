# idshka.ru

> Laravel-first identity provider, issuer и control plane для подключённых сайтов.

`idshka.ru` строится как Laravel modular monolith для подключения доменов, верификации сайтов и дальнейших auth/token flows. В репозитории уже есть foundation, site registry, session auth + Socialite login/linking, issuer/JWKS и OpenResty gateway reference для API-only режима.

## Быстрый старт

Требования: `PHP ^8.5`, Composer, Node.js/NPM и Docker Compose для самого короткого локального старта. CI сейчас прогоняется на `PHP 8.5` и `Node 24`.

### Через Docker Compose

```bash
composer install
docker compose up -d --build
```

`compose.yml` runs pending migrations before PHP-FPM starts so `/portal` is not exposed against an empty schema. In `APP_ENV=production`, the entrypoint runs `php artisan migrate` without `--force`, leaving Laravel's production confirmation in place.

### Локально без Docker

```bash
composer install
npm ci
php artisan key:generate --ansi --force
php artisan test --without-tty
npm run build
```

Приложение поднимается на `http://localhost:8080`. Публичный ingress не отдаёт `/ready`; readiness probe доступен только по внутреннему runtime path.

Demo API resource gateway поднимается отдельно через examples profile:

```bash
docker compose --profile examples up -d --build demo-resource-gateway
```

## Что уже реализовано

- Laravel 13 foundation с public probes `health`/`up` и internal-only `ready`.
- Site registry: `POST /v1/sites`, verification через `dns_txt` и `file`, явное включение `api_resource` и `web_client`.
- Session auth + Socialite: `POST /register`, `POST /login`, `POST /logout`, `/auth/{provider}/redirect|callback|link`.
- Защита owner flows: `auth:web`, policy check `can:manage,site`, throttle `site-registry`, fail-closed verification hardening.
- Docker Compose с core-сервисами `nginx`, `php-fpm`, `PostgreSQL`, `Redis`; OpenResty gateway и internal `demo-resource-api` вынесены в profile `examples`.
- Issuer/JWKS: user API token issue/revoke, signing keys, public `/oauth/jwks.json`.
- OpenResty gateway reference: JWKS validation, `X-Idshka-*` sanitization, trusted context proxying и smoke script.
- GitHub Actions CI с `composer`, `npm`, тестами, smoke-проверкой compose runtime и `PHP 8.5`.

## Пример текущего сценария

```text
owner portal session
  -> POST /v1/sites
  -> POST /v1/sites/{site}/verify
  -> POST /v1/sites/{site}/modes/{mode}
```

Детальные request/response примеры и границы между реализованными и плановыми flows вынесены в `docs/`.

## Документация

| Guide | Description |
|-------|-------------|
| [Docs Overview](docs/README.md) | Карта документации и текущий статус |
| [Laravel Modules](docs/LARAVEL_MODULES.md) | Границы модулей и что уже реализовано |
| [API Flows](docs/API_FLOWS.md) | Текущие и плановые HTTP-сценарии |
| [Gateway Contract](docs/GATEWAY_CONTRACT.md) | Реализованный gateway reference и upstream contract |
| [Socialite](docs/SOCIALITE.md) | Роль Socialite в текущем login/link flow |
| [Operations](docs/OPERATIONS.md) | Backup/restore, restore drill evidence and JWKS cache rebuild |
| [Security Runbook](docs/SECURITY_RUNBOOK.md) | Incident playbooks for leaked tokens, secrets, keys and gateway trust failures |

## Ближайшие фазы

1. `06-web-login-through-idshka`
2. `07-portal-token-and-client-management`
3. `08-security-hardening-and-ops`
