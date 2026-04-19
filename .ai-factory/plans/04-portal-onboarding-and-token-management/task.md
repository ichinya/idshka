---
artifact_type: task
plan_id: 04-portal-onboarding-and-token-management
title: Задачи плана 04-portal-onboarding-and-token-management
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Задачи

| ID | Задача | Depends On |
|----|--------|------------|
| POT-01 | Сделать страницу consumer-сайта apishka с описанием audience и scopes | — |
| POT-02 | Сделать форму выпуска токена: label, expiry, scopes, permissions | POT-01 |
| POT-03 | Подключить POST /v1/tokens и one-time token reveal screen | POT-02 |
| POT-04 | Сделать список токенов с metadata: created_at, expires_at, status, last_used_at | POT-03 |
| POT-05 | Подключить revoke action из UI с confirm и refresh списка | POT-04 |
| POT-06 | Показать curl/http snippet для вызова api.apishka.ru | POT-03 |
| POT-07 | Добавить UX guardrails для broad scopes и near-expiry tokens | POT-02, POT-04 |

## Порядок выполнения
1. **POT-01** — Сделать страницу consumer-сайта apishka с описанием audience и scopes
2. **POT-02** — Сделать форму выпуска токена: label, expiry, scopes, permissions
3. **POT-03** — Подключить POST /v1/tokens и one-time token reveal screen
4. **POT-04** — Сделать список токенов с metadata: created_at, expires_at, status, last_used_at
5. **POT-05** — Подключить revoke action из UI с confirm и refresh списка
6. **POT-06** — Показать curl/http snippet для вызова api.apishka.ru
7. **POT-07** — Добавить UX guardrails для broad scopes и near-expiry tokens

## Commit checkpoints
- checkpoint 1: после завершения первых 2-3 задач и появления рабочего скелета
- checkpoint 2: после появления интеграционной поверхности/контракта
- checkpoint 3: перед /aif-verify, когда остались только polish и docs
