# 06-portal-token-and-client-management

## Цель
Сделать личный кабинет для управления сайтами, режимами, токенами, OIDC clients и snippets.

## Area
portal, site_registry

## Что должно появиться
- site list UI
- domain verify UI
- mode toggles
- token create/revoke UI
- client secret rotation UI
- curl/OIDC snippets
- audit list

## Зависимости
`02-site-registry-and-modes`, `03-token-issuer-and-jwks`, `05-web-login-oidc-for-apishka`

## Acceptance criteria
- пользователь понимает difference API token vs web login
- raw secrets shown once
- revoke отражается в UI
- snippets соответствуют docs/API_FLOWS.md

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
