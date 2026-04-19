# 03-site-registry-and-modes

## Цель
Сделать подключение сайтов, верификацию домена и выбор режимов api_resource/web_client.

## Area
site_registry, laravel, portal

## Что должно появиться
- sites/site_verifications/site_modes migrations
- POST /v1/sites
- POST /v1/sites/{site}/verify
- DNS TXT checker
- well-known file checker
- mode enable endpoints

## Зависимости
02-user-auth-socialite

## Acceptance criteria
- можно создать site apishka.ru
- DNS/file verification меняет status на verified
- можно включить api_resource и web_client
- неверифицированный домен не получает production credentials

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
