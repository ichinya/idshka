# 02-user-auth-socialite

## Цель
Реализовать вход на idshka.ru через Laravel session и Socialite-провайдеры.

## Area
laravel, socialite, security

## Что должно появиться
- users/social_accounts migrations
- email/password или выбранный auth scaffold
- /auth/{provider}/redirect
- /auth/{provider}/callback
- Google/VK/Yandex provider adapters
- account linking/unlinking
- login audit events

## Зависимости
01-laravel-platform-foundation

## Acceptance criteria
- пользователь входит через минимум один Socialite provider
- social_accounts создаётся с unique(provider, provider_user_id)
- повторный вход находит того же пользователя
- provider tokens не попадают в logs

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
