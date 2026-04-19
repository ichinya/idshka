---
artifact_type: task
plan_id: 05-security-hardening-and-ops
title: Задачи плана 05-security-hardening-and-ops
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Задачи

| ID | Задача | Depends On |
|----|--------|------------|
| SHO-01 | Добавить rotation workflow active/next/retired и операционные команды | — |
| SHO-02 | Ввести Redis denylist для revoked_jti и подключить lookup на gateway | SHO-01 |
| SHO-03 | Добавить emergency introspection endpoint и feature flag fallback | SHO-02 |
| SHO-04 | Поставить rate limits на token issue, JWKS и introspection | SHO-01 |
| SHO-05 | Собрать auth metrics и dashboards: issue/revoke/success/failure/cache | SHO-02, SHO-04 |
| SHO-06 | Написать runbooks: key rotation, revoke incident, JWKS cache desync | SHO-03, SHO-05 |
| SHO-07 | Провести финальный security review поверхности токена и заголовков | SHO-02, SHO-03, SHO-05 |

## Порядок выполнения
1. **SHO-01** — Добавить rotation workflow active/next/retired и операционные команды
2. **SHO-02** — Ввести Redis denylist для revoked_jti и подключить lookup на gateway
3. **SHO-03** — Добавить emergency introspection endpoint и feature flag fallback
4. **SHO-04** — Поставить rate limits на token issue, JWKS и introspection
5. **SHO-05** — Собрать auth metrics и dashboards: issue/revoke/success/failure/cache
6. **SHO-06** — Написать runbooks: key rotation, revoke incident, JWKS cache desync
7. **SHO-07** — Провести финальный security review поверхности токена и заголовков

## Commit checkpoints
- checkpoint 1: после завершения первых 2-3 задач и появления рабочего скелета
- checkpoint 2: после появления интеграционной поверхности/контракта
- checkpoint 3: перед /aif-verify, когда остались только polish и docs
