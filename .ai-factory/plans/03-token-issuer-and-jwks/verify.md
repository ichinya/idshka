# Verify: 03-token-issuer-and-jwks

## Checklist
- можно выпустить токен для aud=apishka.ru
- JWKS содержит active public key по kid
- revoked jti появляется в БД/Redis
- raw token показывается один раз

## Required evidence
- Unit/contract test output or smoke curl.
- For gateway/auth flows: include one success and one failure case.
- For UI flows: include screenshot note or route-level smoke.

## Regression checks
- grep should not find старое ошибочное написание spelling in project artifacts.
- `X-Idshka-*` headers are consistently named.
- API-only and web-client modes are not mixed accidentally.
