---
artifact_type: task
plan_id: 01-platform-foundation
title: Задачи плана 01-platform-foundation
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Задачи

| ID | Задача | Depends On |
|----|--------|------------|
| PF-01 | Инициализировать monorepo и package manager strategy | — |
| PF-02 | Создать каркас приложений idska-api, idska-portal и apishka-api | PF-01 |
| PF-03 | Добавить infra/openresty/apishka и шаблон gateway-конфига | PF-01 |
| PF-04 | Завести packages/contracts для JWT claims, auth headers и error bodies | PF-02 |
| PF-05 | Подготовить Docker Compose с PostgreSQL, Redis и локальным ingress | PF-02, PF-03 |
| PF-06 | Подключить structured logging и request_id middleware во все сервисы | PF-02 |
| PF-07 | Добавить health/readiness endpoints и smoke scripts | PF-02, PF-05 |
| PF-08 | Настроить CI skeleton: lint, typecheck, test/smoke | PF-04, PF-07 |

## Порядок выполнения
1. **PF-01** — Инициализировать monorepo и package manager strategy
2. **PF-02** — Создать каркас приложений idska-api, idska-portal и apishka-api
3. **PF-03** — Добавить infra/openresty/apishka и шаблон gateway-конфига
4. **PF-04** — Завести packages/contracts для JWT claims, auth headers и error bodies
5. **PF-05** — Подготовить Docker Compose с PostgreSQL, Redis и локальным ingress
6. **PF-06** — Подключить structured logging и request_id middleware во все сервисы
7. **PF-07** — Добавить health/readiness endpoints и smoke scripts
8. **PF-08** — Настроить CI skeleton: lint, typecheck, test/smoke

## Commit checkpoints
- checkpoint 1: после завершения первых 2-3 задач и появления рабочего скелета
- checkpoint 2: после появления интеграционной поверхности/контракта
- checkpoint 3: перед /aif-verify, когда остались только polish и docs
