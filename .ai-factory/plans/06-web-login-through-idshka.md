# 06-web-login-through-idshka

## Цель
Реализовать web login: apishka.ru входит через idshka.ru по Authorization Code + PKCE.

## Area
web_client, issuer, laravel, socialite

## Что должно появиться
- oidc_clients schema
- oidc_redirect_uris schema
- oauth_authorization_codes schema
- GET /oauth/authorize
- POST /oauth/token
- GET /oauth/userinfo
- id_token issue
- example apishka-web-laravel callback/custom Socialite provider

## Зависимости
03-site-registry-and-modes, 04-token-issuer-and-jwks

## Acceptance criteria
- redirect на authorize работает
- callback обменивает code на tokens
- authorization code одноразовый
- PKCE/state/nonce проверяются
- redirect URI mismatch блокируется
- example Laravel apishka-web создаёт локальную сессию

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
