# Verify: 01-platform-foundation

## Checklist
- docker compose поднимает базовые сервисы
- contracts содержит JWT claims, OIDC metadata и X-Idshka headers
- CI skeleton выполняет lint/typecheck/test или smoke

## Required evidence
- Unit/contract test output or smoke curl.
- For gateway/auth flows: include one success and one failure case.
- For UI flows: include screenshot note or route-level smoke.

## Regression checks
- grep should not find старое ошибочное написание spelling in project artifacts.
- `X-Idshka-*` headers are consistently named.
- API-only and web-client modes are not mixed accidentally.
