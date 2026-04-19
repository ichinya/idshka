# 04-portal-onboarding-and-token-management

## Цель
Дать пользователю self-service поток: увидеть аудиторию `apishka`, выпустить токен,
скопировать его один раз и при необходимости отозвать.

## Что должно появиться
- список consumer-сайтов/аудиторий
- форма выпуска токена
- one-time display raw token
- список выпущенных токенов
- revoke из UI
- curl snippets / usage help

## Зависимости
- `02-issuer-jwt-core`
- `03-apishka-edge-gateway` желательно завершён или близок к завершению

## Acceptance criteria
- пользователь может выпустить токен из UI;
- raw token показывается один раз;
- revoke доступен и синхронизирован с issuer API;
- у `apishka` есть рабочий snippet использования токена.

## Связь с roadmap
Поддерживает Milestone M3.
