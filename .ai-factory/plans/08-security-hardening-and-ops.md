# 08-security-hardening-and-ops

## Цель
Укрепить безопасность, observability, rate limits, key rotation и production checklist.

## Area
security, ops, laravel, issuer, gateway

## Что должно появиться
- rate limits
- structured logs with request_id
- key rotation commands
- backup/restore notes
- security runbook
- CI security checks
- gateway stale key policy

## Зависимости
01-07 plans

## Acceptance criteria
- raw secrets не появляются в logs
- rate limits покрывают auth/token endpoints
- key rotation имеет documented flow
- есть runbook для leaked key/token
- CI выполняет tests/build/smoke

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
