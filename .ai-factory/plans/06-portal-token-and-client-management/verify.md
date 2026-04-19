# Verify: 06-portal-token-and-client-management

## Checklist
- пользователь понимает difference API token vs web login
- raw secrets shown once
- revoke отражается в UI
- snippets соответствуют docs/API_FLOWS.md

## Required evidence
- Unit/contract test output or smoke curl.
- For gateway/auth flows: include one success and one failure case.
- For UI flows: include screenshot note or route-level smoke.

## Regression checks
- grep should not find старое ошибочное написание spelling in project artifacts.
- `X-Idshka-*` headers are consistently named.
- API-only and web-client modes are not mixed accidentally.
