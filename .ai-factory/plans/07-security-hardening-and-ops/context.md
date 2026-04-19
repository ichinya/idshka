# Context: 07-security-hardening-and-ops

## Product context
Проект строит `idshka.ru` как identity/control plane для подключённых сайтов.
Пример подключённого сайта — `apishka.ru`.

Два режима интеграции:
- `api_resource`: API-only, запросы идут с token через gateway.
- `web_client`: полноценный web login через `idshka.ru`.

## This plan
Довести MVP до безопасной эксплуатации: key rotation, rate limits, observability, runbooks.

## Relevant docs
- `.ai-factory/DESCRIPTION.md`
- `.ai-factory/TECH_STACK.md`
- `.ai-factory/ARCHITECTURE.md`
- `.ai-factory/RULES.md`
- `docs/API_FLOWS.md`
- `docs/GATEWAY_CONTRACT.md`
