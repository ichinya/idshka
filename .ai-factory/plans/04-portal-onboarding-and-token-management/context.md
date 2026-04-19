---
artifact_type: context
plan_id: 04-portal-onboarding-and-token-management
title: Контекст плана 04-portal-onboarding-and-token-management
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Контекст

## Зачем этот план существует
Дать пользователю self-service поток: увидеть аудиторию `apishka`, выпустить токен, скопировать его один раз и при необходимости отозвать.

## In scope
- Portal pages and forms
- One-time token display
- Token list/revoke UI
- Basic onboarding text/snippets for apishka

## Out of scope
- OAuth client onboarding
- Billing and quotas
- Multi-tenant org management

## Зависимости и последовательность
- `02-issuer-jwt-core`
- `03-apishka-edge-gateway (желательно завершён или близок к завершению)`

## Основные риски
- Повторный показ raw token нарушит security model
- Слишком технический UX усложнит self-service

## Acceptance criteria
- UI создаёт токен и показывает raw value только один раз
- После перезагрузки страницы raw token недоступен
- Revoke из UI меняет статус токена без ручных правок в БД
- curl snippet реально работает против apishka на valid token

## Артефакты плана
- Summary: `.ai-factory/plans/04-portal-onboarding-and-token-management.md`
- Tasks: `.ai-factory/plans/04-portal-onboarding-and-token-management/task.md`
- Rules: `.ai-factory/plans/04-portal-onboarding-and-token-management/rules.md`
- Verify: `.ai-factory/plans/04-portal-onboarding-and-token-management/verify.md`
- Status: `.ai-factory/plans/04-portal-onboarding-and-token-management/status.yaml`
