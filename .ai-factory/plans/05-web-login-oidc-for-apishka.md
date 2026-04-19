# 05-web-login-oidc-for-apishka

## Цель
Реализовать OIDC-like Authorization Code + PKCE flow для входа на apishka.ru через idshka.ru.

## Area
web_client, issuer, portal

## Что должно появиться
- oidc_clients schema
- redirect uri registry
- GET /oauth/authorize
- POST /oauth/token
- id_token issue/validation
- example apishka-web callback
- state/nonce/PKCE checks

## Зависимости
`02-site-registry-and-modes`, `03-token-issuer-and-jwks`

## Acceptance criteria
- apishka-web redirect работает
- callback обменивает code на tokens
- id_token проверяется
- redirect URI mismatch блокируется

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
