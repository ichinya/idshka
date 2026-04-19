# Security Area Rules

## Scope
Криптография, revoke, ключи, rate limiting, угрозы и инварианты.

## Инварианты
- Приватный signing key никогда не покидает issuer runtime/secret store.
- JWT без валидного `kid` или с неподдерживаемым `alg` отклоняется.
- Любой revoke создаёт audit event.
- Любой административный security-action обратим либо имеет documented runbook.

## Политика
- Для MVP используется `RS256`; смена алгоритма — отдельный plan item.
- Одновременно поддерживаются минимум два ключа: `active` и `next`.
- JWKS rotation делается с overlap-периодом.
- Direct API JWT должны быть короткоживущими; долгоживущие ключи — только после отдельной hardening-фазы.
- На endpoints token issuance, JWKS и introspection действуют rate limits.
- Denylist TTL должен быть >= максимальному TTL токена.

## Threat model minimum
- header spoofing
- audience confusion
- stale key cache
- replay до revoke propagation
- overbroad scopes
- raw token leakage in logs

## Запрещено
- Использовать один shared secret на все environments.
- Держать signing key в env-переменной без плана миграции в secret manager.
- Считать revoke завершённым без подтверждения распространения denylist/cache.
