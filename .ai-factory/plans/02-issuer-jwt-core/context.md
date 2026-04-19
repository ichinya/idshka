---
artifact_type: context
plan_id: 02-issuer-jwt-core
title: Контекст плана 02-issuer-jwt-core
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Контекст

## Зачем этот план существует
Сделать `idska-api` источником истины по токенам: выпуск, отзыв, аудит и публикация JWKS.

## In scope
- DB schema issuer
- JWT signing and JWKS
- Token issue/revoke API
- Audit events

## Out of scope
- Portal UI
- OpenResty Lua validation
- Redis denylist propagation

## Зависимости и последовательность
- `01-platform-foundation`

## Основные риски
- Неполный claims contract вызовет breaking changes позже
- Ошибки с key rotation усложнят gateway cache

## Acceptance criteria
- POST /v1/tokens возвращает валидный JWT и metadata
- JWT содержит iss, aud, sub, jti, iat, nbf, exp, scope, roles, permissions, site_id
- GET /oauth/jwks.json отдаёт ключ, которым JWT успешно проверяется
- POST /v1/tokens/{id}/revoke идемпотентен и создаёт audit event

## Артефакты плана
- Summary: `.ai-factory/plans/02-issuer-jwt-core.md`
- Tasks: `.ai-factory/plans/02-issuer-jwt-core/task.md`
- Rules: `.ai-factory/plans/02-issuer-jwt-core/rules.md`
- Verify: `.ai-factory/plans/02-issuer-jwt-core/verify.md`
- Status: `.ai-factory/plans/02-issuer-jwt-core/status.yaml`
