[Back to README](../README.md) · [Next Page →](LARAVEL_MODULES.md)

# Документация `idshka.ru`

Этот каталог фиксирует текущее состояние репозитория и целевые контракты следующих фаз. Важно не смешивать уже реализованные site registry / issuer JWKS slices с ещё не реализованными OAuth/OIDC provider endpoints.

## Срез по состоянию проекта

| Area | Status | Notes |
|------|--------|-------|
| Foundation runtime | implemented | `health`, `up`, internal `ready`, Docker Compose, CI |
| Site registry | implemented | создание сайта, verification, mode enablement |
| Socialite login | implemented | session auth + Socialite redirect/callback/link/unlink routes in `routes/web.php` |
| Issuer / JWKS | implemented slice | user API token issue/revoke + `GET /oauth/jwks.json` |
| Gateway contract | implemented reference | OpenResty gateway validates JWT via JWKS, sanitizes `X-Idshka-*`, and has curl smoke coverage |

## Карта документации

| Guide | Purpose | Status |
|-------|---------|--------|
| [Laravel Modules](LARAVEL_MODULES.md) | Границы bounded contexts и текущая реализация | mixed |
| [API Flows](API_FLOWS.md) | HTTP сценарии: что есть сейчас и что ещё planned | mixed |
| [Gateway Contract](GATEWAY_CONTRACT.md) | Gateway/JWKS contract для `api.apishka.ru` | implemented reference |
| [Socialite](SOCIALITE.md) | Роль Socialite и граница между login и issuer | mixed |

## Что важно не перепутать

- `routes/api.php` уже содержит рабочий site registry API.
- `routes/web.php` уже содержит рабочие auth/social endpoints (register, login, logout, Socialite redirect/callback/link/unlink).
- `routes/oauth.php` уже публикует `GET /oauth/jwks.json`; остальные OAuth/OIDC provider endpoints остаются будущей фазой.
- Gateway reference в `infra/openresty/apishka/` уже проверяет JWT через JWKS и прокидывает trusted context; signed context, edge revoke cache и online introspection остаются будущими hardening phases.

## See Also

- [Laravel Modules](LARAVEL_MODULES.md) — какие bounded contexts уже materialized в коде
- [API Flows](API_FLOWS.md) — текущие запросы и ответы для site registry
- [Socialite](SOCIALITE.md) — текущий login slice и его границы
