# Verify: 04-api-resource-gateway-for-apishka

## Checklist
- валидный токен проходит
- поддельный/expired/audience mismatch блокируется
- входящие X-Idshka-* не проходят
- signed context верифицируется в example upstream

## Required evidence
- Unit/contract test output or smoke curl.
- For gateway/auth flows: include one success and one failure case.
- For UI flows: include screenshot note or route-level smoke.

## Regression checks
- grep should not find старое ошибочное написание spelling in project artifacts.
- `X-Idshka-*` headers are consistently named.
- API-only and web-client modes are not mixed accidentally.
