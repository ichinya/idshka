---
artifact_type: context
plan_id: 05-security-hardening-and-ops
title: Контекст плана 05-security-hardening-and-ops
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Контекст

## Зачем этот план существует
Довести MVP до безопасной и операционно предсказуемой эксплуатации.

## In scope
- Key rotation
- Revocation propagation
- Rate limiting
- Metrics and runbooks

## Out of scope
- External billing
- Enterprise SSO
- Cross-region multi-cloud HA

## Зависимости и последовательность
- `02-issuer-jwt-core`
- `03-apishka-edge-gateway`
- `04-portal-onboarding-and-token-management`

## Основные риски
- Неправильный denylist TTL сделает revoke неполным
- Fallback introspection может стать скрытым SPOF

## Acceptance criteria
- Revoke по jti перестаёт пропускаться через gateway в пределах целевого TTL
- Gateway продолжает валидировать токены во время ротации active/next ключей
- Metrics доступны и отражают auth success/failure/revoke/cache state
- Runbooks позволяют отработать инцидент без поиска знаний по чату

## Артефакты плана
- Summary: `.ai-factory/plans/05-security-hardening-and-ops.md`
- Tasks: `.ai-factory/plans/05-security-hardening-and-ops/task.md`
- Rules: `.ai-factory/plans/05-security-hardening-and-ops/rules.md`
- Verify: `.ai-factory/plans/05-security-hardening-and-ops/verify.md`
- Status: `.ai-factory/plans/05-security-hardening-and-ops/status.yaml`
