# Proposal: 02-user-auth-socialite

## Intent

Migrated legacy plan. Review and refine this proposal before implementation.

## Scope

- Review migrated legacy scope.

## Approach

Review legacy plan notes and refine the OpenSpec change design.

## Legacy source

Migrated from:
- .ai-factory/plans/02-user-auth-socialite.md
- .ai-factory/plans/02-user-auth-socialite/task.md
- .ai-factory/plans/02-user-auth-socialite/context.md
- .ai-factory/plans/02-user-auth-socialite/rules.md
- .ai-factory/plans/02-user-auth-socialite/verify.md
- .ai-factory/plans/02-user-auth-socialite/status.yaml

## Legacy plan notes

# 02-user-auth-socialite

## Цель
Реализовать вход на idshka.ru через Laravel session и Socialite-провайдеры.

## Area
laravel, socialite, security

## Что должно появиться
- [x] users/social_accounts migrations
- [x] email/password или выбранный auth scaffold
- [x] /auth/{provider}/redirect
- [x] /auth/{provider}/callback
- [x] Google/VK/Yandex provider adapters
- [x] account linking/unlinking
- [x] login audit events

## Зависимости
01-laravel-platform-foundation

## Acceptance criteria
- пользователь входит через минимум один Socialite provider
- social_accounts создаётся с unique(provider, provider_user_id)
- повторный вход находит того же пользователя
- provider tokens не попадают в logs

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
