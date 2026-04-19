# Ops Area Rules

## Scope
Docker, compose, deploy, monitoring, dashboards, incident response.

## Правила
- Локальная среда должна подниматься одной командой.
- Все сервисы обязаны иметь `/health` и `/ready` или эквивалент.
- Логи уходят в stdout в структурированном JSON.
- Для auth-потока фиксируются метрики:
  - token_issued_total
  - token_revoked_total
  - gateway_auth_success_total
  - gateway_auth_failure_total
  - jwks_cache_hit_ratio
  - revoke_lookup_latency
- Любой rollout gateway-конфига должен иметь быстрый rollback.

## Environment
- Отдельные ключи и базы на `dev`, `stage`, `prod`.
- Любой внешний URL (`idska issuer`, `jwks`, `introspection`) должен быть явно параметризован.
- Секреты не попадают в compose file напрямую; для local допускается `.env.local` вне git.

## Запрещено
- Деплоить `apishka-api` наружу в обход gateway.
- Менять JWT/headers contract без smoke-check после deploy.
- Переиспользовать production-like signing key в dev.
