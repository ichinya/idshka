[Back to README](../README.md) · [Next Page →](LARAVEL_MODULES.md)

# Документация `idshka.ru`

Этот каталог фиксирует текущее состояние репозитория и целевые контракты следующих фаз. Важно не смешивать уже реализованный site registry slice с ещё не реализованными `web`/`oauth` endpoints.

## Срез по состоянию проекта

| Area | Status | Notes |
|------|--------|-------|
| Foundation runtime | implemented | `health`, `up`, internal `ready`, Docker Compose, CI |
| Site registry | implemented | создание сайта, verification, mode enablement |
| Socialite login | implemented | session auth + Socialite redirect/callback/link/unlink routes in `routes/web.php` |
| Issuer / JWKS | planned | `routes/oauth.php` пока placeholder |
| Gateway contract | target design | есть skeleton infra и doc contract |

## Карта документации

| Guide | Purpose | Status |
|-------|---------|--------|
| [Laravel Modules](LARAVEL_MODULES.md) | Границы bounded contexts и текущая реализация | mixed |
| [API Flows](API_FLOWS.md) | HTTP сценарии: что есть сейчас и что ещё planned | mixed |
| [Gateway Contract](GATEWAY_CONTRACT.md) | Целевой gateway/JWKS contract для `api.apishka.ru` | planned |
| [Socialite](SOCIALITE.md) | Роль Socialite и граница между login и issuer | planned |

## Что важно не перепутать

- `routes/api.php` уже содержит рабочий site registry API.
- `routes/web.php` уже содержит рабочие auth/social endpoints (register, login, logout, Socialite redirect/callback/link/unlink).
- `routes/oauth.php` всё ещё остаётся placeholder для issuer/OIDC фазы.
- Документы про gateway, JWKS и web login описывают целевую интеграцию, а не уже поднятые endpoints.

## See Also

- [Laravel Modules](LARAVEL_MODULES.md) — какие bounded contexts уже materialized в коде
- [API Flows](API_FLOWS.md) — текущие запросы и ответы для site registry
- [Socialite](SOCIALITE.md) — текущий login slice и его границы
