# Proposal: 07-portal-token-and-client-management

## Intent

Migrated legacy plan. Review and refine this proposal before implementation.

## Scope

- Review migrated legacy scope.

## Approach

Review legacy plan notes and refine the OpenSpec change design.

## Legacy source

Migrated from:
- .ai-factory/plans/07-portal-token-and-client-management.md
- .ai-factory/plans/07-portal-token-and-client-management/task.md
- .ai-factory/plans/07-portal-token-and-client-management/context.md
- .ai-factory/plans/07-portal-token-and-client-management/rules.md
- .ai-factory/plans/07-portal-token-and-client-management/verify.md
- .ai-factory/plans/07-portal-token-and-client-management/status.yaml

## Legacy plan notes

# 07-portal-token-and-client-management

## Цель
Собрать self-service кабинет для сайтов, токенов, клиентов, revoke и audit.

## Area
portal, laravel, site_registry, issuer

## Что должно появиться
- Blade/Tailwind pages
- my sites UI
- verification instructions UI
- api token create/list/revoke UI
- web client credentials UI
- redirect URI management UI
- audit log UI

## Зависимости
02-user-auth-socialite, 03-site-registry-and-modes, 04-token-issuer-and-jwks

## Acceptance criteria
- владелец проходит flow создания сайта в UI
- secret/token показывается один раз
- revoke работает из UI
- audit events видны пользователю
- danger actions требуют подтверждения

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
