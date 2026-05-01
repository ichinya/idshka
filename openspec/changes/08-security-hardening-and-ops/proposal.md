# Proposal: 08-security-hardening-and-ops

## Intent

Migrated legacy plan. Review and refine this proposal before implementation.

## Scope

- Review migrated legacy scope.

## Approach

Review legacy plan notes and refine the OpenSpec change design.

## Legacy source

Migrated from:
- .ai-factory/plans/08-security-hardening-and-ops.md
- .ai-factory/plans/08-security-hardening-and-ops/task.md
- .ai-factory/plans/08-security-hardening-and-ops/context.md
- .ai-factory/plans/08-security-hardening-and-ops/rules.md
- .ai-factory/plans/08-security-hardening-and-ops/verify.md
- .ai-factory/plans/08-security-hardening-and-ops/status.yaml

## Legacy plan notes

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
