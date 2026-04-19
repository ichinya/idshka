---
artifact_type: task
plan_id: 03-apishka-edge-gateway
title: Задачи плана 03-apishka-edge-gateway
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Задачи

| ID | Задача | Depends On |
|----|--------|------------|
| AEG-01 | Создать nginx.conf и Lua entrypoint для auth-проверки | — |
| AEG-02 | Подключить JWKS fetch + cache + key selection по kid | AEG-01 |
| AEG-03 | Реализовать обязательные проверки: alg, iss, aud, exp, nbf, signature | AEG-02 |
| AEG-04 | Добавить полную sanitization входящих X-Idska-* и injection доверенных заголовков | AEG-03 |
| AEG-05 | Сделать стабильные JSON-ошибки 401/403 с request_id | AEG-03 |
| AEG-06 | Закрыть apishka-api от внешнего трафика и доверять только gateway | AEG-04 |
| AEG-07 | Добавить integration/smoke сценарии: valid, expired, wrong aud, bad signature, spoofed headers | AEG-05, AEG-06 |

## Порядок выполнения
1. **AEG-01** — Создать nginx.conf и Lua entrypoint для auth-проверки
2. **AEG-02** — Подключить JWKS fetch + cache + key selection по kid
3. **AEG-03** — Реализовать обязательные проверки: alg, iss, aud, exp, nbf, signature
4. **AEG-04** — Добавить полную sanitization входящих X-Idska-* и injection доверенных заголовков
5. **AEG-05** — Сделать стабильные JSON-ошибки 401/403 с request_id
6. **AEG-06** — Закрыть apishka-api от внешнего трафика и доверять только gateway
7. **AEG-07** — Добавить integration/smoke сценарии: valid, expired, wrong aud, bad signature, spoofed headers

## Commit checkpoints
- checkpoint 1: после завершения первых 2-3 задач и появления рабочего скелета
- checkpoint 2: после появления интеграционной поверхности/контракта
- checkpoint 3: перед /aif-verify, когда остались только polish и docs
