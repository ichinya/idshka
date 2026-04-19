# Verify: 05-web-login-oidc-for-apishka

## Checklist
- apishka-web redirect работает
- callback обменивает code на tokens
- id_token проверяется
- redirect URI mismatch блокируется

## Required evidence
- Unit/contract test output or smoke curl.
- For gateway/auth flows: include one success and one failure case.
- For UI flows: include screenshot note or route-level smoke.

## Regression checks
- grep should not find старое ошибочное написание spelling in project artifacts.
- `X-Idshka-*` headers are consistently named.
- API-only and web-client modes are not mixed accidentally.
