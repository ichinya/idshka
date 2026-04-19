# Context: 02-site-registry-and-modes

## Product context
Проект строит `idshka.ru` как identity/control plane для подключённых сайтов.
Пример подключённого сайта — `apishka.ru`.

Два режима интеграции:
- `api_resource`: API-only, запросы идут с token через gateway.
- `web_client`: полноценный web login через `idshka.ru`.

## This plan
Реализовать подключение домена apishka.ru, верификацию владения и включение режимов api_resource/web_client.

## Relevant docs
- `.ai-factory/DESCRIPTION.md`
- `.ai-factory/TECH_STACK.md`
- `.ai-factory/ARCHITECTURE.md`
- `.ai-factory/RULES.md`
- `docs/API_FLOWS.md`
- `docs/GATEWAY_CONTRACT.md`
