---
artifact_type: rules
plan_id: 05-security-hardening-and-ops
title: Правила плана 05-security-hardening-and-ops
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Применимые area rules

- `security` -> `.ai-factory/rules/security.md`
- `ops` -> `.ai-factory/rules/ops.md`
- `gateway` -> `.ai-factory/rules/gateway.md`
- `issuer` -> `.ai-factory/rules/issuer.md`

## План-локальные правила
- До начала кодинга проверить, что summary, context и tasks синхронизированы.
- Любое изменение интеграционного контракта обновляет `packages/contracts` и примеры запросов.
- Нельзя закрывать задачу без evidence: тест, curl, лог или короткая proof note.
- Если задача меняет auth-поверхность, до завершения обновить `ARCHITECTURE.md` или `ROADMAP.md`, если это действительно меняет структуру/вехи.
- При конфликте между локальным решением и global rules приоритет у security boundary из `.ai-factory/RULES.md`.

## Минимум для handoff
- понятный diff;
- заметка что изменилось;
- как проверить локально;
- что осталось незакрытым.
