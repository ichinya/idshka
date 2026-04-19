# Context: 03-token-issuer-and-jwks

## Product context
Проект строит `idshka.ru` как identity/control plane для подключённых сайтов.
Пример подключённого сайта — `apishka.ru`.

Два режима интеграции:
- `api_resource`: API-only, запросы идут с token через gateway.
- `web_client`: полноценный web login через `idshka.ru`.

## This plan
Сделать выпуск JWT для API-only режима, публикацию JWKS и revoke/denylist.

## Relevant docs
- `.ai-factory/DESCRIPTION.md`
- `.ai-factory/TECH_STACK.md`
- `.ai-factory/ARCHITECTURE.md`
- `.ai-factory/RULES.md`
- `docs/API_FLOWS.md`
- `docs/GATEWAY_CONTRACT.md`
