# 05-security-hardening-and-ops

## Цель
Довести MVP до безопасной и операционно предсказуемой эксплуатации.

## Что должно появиться
- overlap rotation active/next keys
- revoke denylist в Redis
- optional introspection fallback
- rate limits
- auth metrics / dashboards
- security runbooks

## Зависимости
- `02-issuer-jwt-core`
- `03-apishka-edge-gateway`
- `04-portal-onboarding-and-token-management`

## Acceptance criteria
- revoke начинает блокировать запросы через gateway в согласованный срок;
- ротация ключей проходит без downtime;
- есть метрики и базовые runbooks для incident response.

## Связь с roadmap
Поддерживает Milestone M4.
