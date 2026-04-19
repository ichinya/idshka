# Area: ops

- Docker Compose должен поднимать Laravel app, nginx/php-fpm, PostgreSQL, Redis и gateway example.
- Health endpoint не раскрывает секреты и внутреннюю конфигурацию.
- Readiness проверяет DB/Redis без тяжёлых запросов.
- Логи должны иметь `request_id`.
- CI выполняет composer install, tests, npm build и gateway smoke.
- Миграции должны быть обратимыми, где это возможно.
