# 01-platform-foundation

## Цель
Подготовить monorepo, локальную инфраструктуру, contracts package и skeleton сервисов.

## Area
base, ops

## Что должно появиться
- pnpm workspace skeleton
- apps/idshka-api
- apps/idshka-portal
- examples/apishka-api
- examples/apishka-web
- infra/openresty/apishka
- packages/contracts
- Docker Compose
- health/readiness endpoints

## Зависимости
Нет

## Acceptance criteria
- docker compose поднимает базовые сервисы
- contracts содержит JWT claims, OIDC metadata и X-Idshka headers
- CI skeleton выполняет lint/typecheck/test или smoke

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
