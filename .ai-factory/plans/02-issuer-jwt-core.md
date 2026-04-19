# 02-issuer-jwt-core

## Цель
Сделать `idska-api` источником истины по токенам: выпуск, отзыв, аудит и публикация JWKS.

## Что должно появиться
- schema/migrations для токенов и ключей
- key lifecycle (`next`, `active`, `retired`)
- `POST /v1/tokens`
- `GET /v1/tokens`
- `POST /v1/tokens/{id}/revoke`
- `GET /oauth/jwks.json`
- аудит выдачи и revoke

## Зависимости
- `01-platform-foundation`

## Acceptance criteria
- можно выпустить JWT для `aud=apishka`;
- токен содержит agreed claims;
- JWKS публикует актуальный public key по `kid`;
- revoke фиксируется в БД и доступен для denylist использования.

## Связь с roadmap
Поддерживает Milestone M1.
