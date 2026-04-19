# idshka.ru

Laravel-first identity provider, issuer и control plane для подключённых сайтов.

## Что уже есть

- Laravel 13 foundation в корне репозитория.
- `routes/web.php`, `routes/api.php`, `routes/oauth.php`.
- `health`, `ready` и built-in `up` endpoints.
- Skeleton модулей `app/Domain/*` и `app/Contracts/Auth`.
- Docker Compose с `nginx`, `php-fpm`, `PostgreSQL`, `Redis` и OpenResty gateway skeleton.
- GitHub Actions CI skeleton для `composer`, `npm`, tests и проверки `docker compose config`.

## Быстрый старт

### Локально без Docker

```bash
composer install
npm install
php artisan key:generate --ansi --force
php artisan test --without-tty
npm run build
```

### Через Docker Compose

```bash
composer install
docker compose up -d --build
docker compose exec app php artisan key:generate --ansi --force
docker compose exec app php artisan migrate
```

Приложение будет доступно на `http://localhost:8080`, gateway skeleton — на `http://localhost:8081`.

## Структура

- `app/Domain/*` — bounded contexts монолита.
- `app/Contracts/Auth` — auth-контракты и protocol constants.
- `docs/` — API flows, gateway contract, Socialite notes и архитектурные материалы.
- `infra/docker/` — контейнеры `php-fpm` и `nginx`.
- `infra/openresty/apishka/` — каркас gateway example.
- `.ai-factory/` — project context, rules, roadmap и implementation plans.

## Полезные команды

```bash
composer test
php artisan route:list
docker compose config
```

## Следующие фазы

1. `02-user-auth-socialite`
2. `03-site-registry-and-modes`
3. `04-token-issuer-and-jwks`
4. `05-api-resource-gateway-for-apishka`
5. `06-web-login-through-idshka`
