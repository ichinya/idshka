# 01-platform-foundation

## Цель
Подготовить монорепозиторий, общий contracts layer и локальную инфраструктуру так,
чтобы дальнейшие планы выполнялись без переизобретения структуры.

## Что должно появиться
- monorepo/workspaces
- `apps/idska-api`
- `apps/idska-portal`
- `apps/apishka-api`
- `infra/openresty/apishka`
- `packages/contracts`
- локальный Docker Compose
- базовые health/readiness маршруты
- единый request_id и structured logging

## Зависимости
Нет. Это первый исполняемый план.

## Acceptance criteria
- проект поднимается локально одной командой;
- сервисы имеют health endpoints;
- contracts package содержит начальные схемы claims и `X-Idska-*` headers;
- CI может выполнить минимум `lint`, `typecheck`, `test`/`smoke`.

## Связь с roadmap
Поддерживает Milestone M0.
