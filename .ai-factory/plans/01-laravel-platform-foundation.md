# 01-laravel-platform-foundation

## Цель
Подготовить Laravel foundation, локальную инфраструктуру и skeleton доменных модулей.

## Area
laravel, ops

## Что должно появиться
- Laravel app skeleton
- Docker Compose: nginx/php-fpm/PostgreSQL/Redis
- app/Domain structure
- routes/web.php api.php oauth.php
- health/readiness endpoints
- tests/CI skeleton

## Зависимости
Нет

## Acceptance criteria
- docker compose поднимает Laravel app и зависимости
- php artisan test выполняется
- health/readiness endpoints отвечают
- структура app/Domain создана

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
