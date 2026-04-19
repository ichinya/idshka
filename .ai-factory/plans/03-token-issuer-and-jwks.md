# 03-token-issuer-and-jwks

## Цель
Сделать выпуск JWT для API-only режима, публикацию JWKS и revoke/denylist.

## Area
issuer, security

## Что должно появиться
- signing_keys schema
- api_tokens schema
- revoked_jti schema
- POST /v1/user/api-tokens
- GET /oauth/jwks.json
- POST /v1/user/api-tokens/{id}/revoke
- audit events

## Зависимости
`01-platform-foundation`, `02-site-registry-and-modes`

## Acceptance criteria
- можно выпустить токен для aud=apishka.ru
- JWKS содержит active public key по kid
- revoked jti появляется в БД/Redis
- raw token показывается один раз

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
