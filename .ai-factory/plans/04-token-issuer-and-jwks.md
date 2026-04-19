# 04-token-issuer-and-jwks

## Цель
Сделать выпуск JWT для API-only режима, публикацию JWKS и revoke/denylist.

## Area
issuer, security, laravel

## Что должно появиться
- signing_keys schema
- api_tokens schema
- revoked_jti schema
- GET /oauth/jwks.json
- POST /v1/user/api-tokens
- POST /v1/user/api-tokens/{id}/revoke
- TokenIssuer/JwksService/SigningKeyService

## Зависимости
03-site-registry-and-modes

## Acceptance criteria
- можно выпустить токен для aud=apishka.ru
- JWKS содержит active public key по kid
- JWT содержит iss/aud/sub/site_id/scope/permissions/jti/exp
- raw token показывается один раз
- revoked jti появляется в БД/Redis

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
