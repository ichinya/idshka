# Proposal: 08-security-hardening-and-ops

## Why

The identity issuer, gateway integration, and self-service portal handle credentials and signed authentication context. Before production use, the platform needs explicit hardening for rate limits, secret-safe observability, key rotation, backup/restore, operational runbooks, and CI security checks.

## What Changes

- Add rate limits for authentication, OAuth/OIDC, token, revoke, and portal credential endpoints.
- Add request correlation and structured logs that never include raw secrets.
- Add documented key rotation commands and operator flow for issuer signing keys.
- Add backup/restore notes for identity, site registry, token, client, audit, and key material data.
- Add security runbook steps for leaked keys, tokens, client secrets, and gateway header trust failures.
- Add CI security checks for tests, build, and smoke validation.
- Define gateway behavior for stale JWKS/signing keys.

## Scope

- Laravel security and ops hardening.
- Issuer and gateway operational policy.
- CI checks and production runbooks.
- Documentation for backup/restore and incident response.

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
