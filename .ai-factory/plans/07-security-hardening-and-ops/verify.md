# Verify: 07-security-hardening-and-ops

## Checklist
- key rotation без downtime
- revoked jti блокируется gateway
- auth metrics есть
- runbooks описывают key leak/JWKS outage/Redis outage

## Required evidence
- Unit/contract test output or smoke curl.
- For gateway/auth flows: include one success and one failure case.
- For UI flows: include screenshot note or route-level smoke.

## Regression checks
- grep should not find старое ошибочное написание spelling in project artifacts.
- `X-Idshka-*` headers are consistently named.
- API-only and web-client modes are not mixed accidentally.
