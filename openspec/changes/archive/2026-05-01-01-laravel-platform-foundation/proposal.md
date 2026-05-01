# Proposal: 01-laravel-platform-foundation

## Intent

Migrated legacy plan. Review and refine this proposal before implementation.

## Scope

- Review migrated legacy scope.

## Approach

Review legacy plan notes and refine the OpenSpec change design.

## Legacy source

Migrated from:
- .ai-factory/plans/01-laravel-platform-foundation.md
- .ai-factory/plans/01-laravel-platform-foundation/task.md
- .ai-factory/plans/01-laravel-platform-foundation/context.md
- .ai-factory/plans/01-laravel-platform-foundation/rules.md
- .ai-factory/plans/01-laravel-platform-foundation/verify.md
- .ai-factory/plans/01-laravel-platform-foundation/status.yaml

## Legacy plan notes

# 01-laravel-platform-foundation

## Цель
Подготовить Laravel foundation, локальную инфраструктуру и skeleton доменных модулей.

## Area
laravel, ops

## Checklist
- [x] Laravel app skeleton
- [x] Docker Compose: nginx/php-fpm/PostgreSQL/Redis
- [x] app/Domain structure
- [x] routes/web.php api.php oauth.php
- [x] health/readiness endpoints
- [x] tests/CI skeleton

## Зависимости
Нет

## Acceptance criteria
- [x] docker compose поднимает Laravel app и зависимости
- [x] php artisan test выполняется
- [x] health/readiness endpoints отвечают
- [x] структура app/Domain создана

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
