# Verify: 02-site-registry-and-modes

## Checklist
- verified domain получает site_id
- режим api_resource нельзя включить без verified domain
- режим web_client требует exact redirect_uri

## Required evidence
- Unit/contract test output or smoke curl.
- For gateway/auth flows: include one success and one failure case.
- For UI flows: include screenshot note or route-level smoke.

## Regression checks
- grep should not find старое ошибочное написание spelling in project artifacts.
- `X-Idshka-*` headers are consistently named.
- API-only and web-client modes are not mixed accidentally.
