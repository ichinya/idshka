---
artifact_type: context
plan_id: 01-platform-foundation
title: Контекст плана 01-platform-foundation
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Контекст

## Зачем этот план существует
Подготовить монорепозиторий, общий contracts layer и локальную инфраструктуру так, чтобы дальнейшие планы выполнялись без переизобретения структуры.

## In scope
- Монорепо и каталоги приложений
- Контракты и типы для auth-поверхности
- Local infra
- Health checks и базовые quality gates

## Out of scope
- Реальный выпуск токенов
- UI выпуска токенов
- Полная gateway-валидация

## Зависимости и последовательность
- Это стартовый план без зависимостей.

## Основные риски
- Слишком ранняя привязка к лишним инфраструктурным инструментам
- Отсутствие единого contracts package приведёт к расхождению issuer/gateway/upstream

## Acceptance criteria
- docker compose up поднимает PostgreSQL, Redis, OpenResty и сервисы
- GET /health на idska-api и apishka-api отвечает 200
- contracts package содержит схемы JWT claims и X-Idska-* headers
- CI workflow проходит на пустой вертикали проекта

## Артефакты плана
- Summary: `.ai-factory/plans/01-platform-foundation.md`
- Tasks: `.ai-factory/plans/01-platform-foundation/task.md`
- Rules: `.ai-factory/plans/01-platform-foundation/rules.md`
- Verify: `.ai-factory/plans/01-platform-foundation/verify.md`
- Status: `.ai-factory/plans/01-platform-foundation/status.yaml`
