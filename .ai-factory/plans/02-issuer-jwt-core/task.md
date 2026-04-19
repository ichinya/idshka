---
artifact_type: task
plan_id: 02-issuer-jwt-core
title: Задачи плана 02-issuer-jwt-core
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Задачи

| ID | Задача | Depends On |
|----|--------|------------|
| IJC-01 | Определить схему данных: users, sites, api_tokens, signing_keys, audit_events, revoked_jti | — |
| IJC-02 | Реализовать key management service и states next/active/retired | IJC-01 |
| IJC-03 | Определить JWT claims contract v1 и helper для подписи RS256 с kid | IJC-02 |
| IJC-04 | Сделать GET /oauth/jwks.json с безопасным cache-control | IJC-02 |
| IJC-05 | Сделать POST /v1/tokens с label, aud, scopes, permissions, expiry | IJC-03 |
| IJC-06 | Сделать GET /v1/tokens и POST /v1/tokens/{id}/revoke | IJC-05 |
| IJC-07 | Подключить аудит token.issued и token.revoked | IJC-05, IJC-06 |
| IJC-08 | Добавить smoke/integration checks для подписи и JWKS совместимости | IJC-04, IJC-05, IJC-06 |

## Порядок выполнения
1. **IJC-01** — Определить схему данных: users, sites, api_tokens, signing_keys, audit_events, revoked_jti
2. **IJC-02** — Реализовать key management service и states next/active/retired
3. **IJC-03** — Определить JWT claims contract v1 и helper для подписи RS256 с kid
4. **IJC-04** — Сделать GET /oauth/jwks.json с безопасным cache-control
5. **IJC-05** — Сделать POST /v1/tokens с label, aud, scopes, permissions, expiry
6. **IJC-06** — Сделать GET /v1/tokens и POST /v1/tokens/{id}/revoke
7. **IJC-07** — Подключить аудит token.issued и token.revoked
8. **IJC-08** — Добавить smoke/integration checks для подписи и JWKS совместимости

## Commit checkpoints
- checkpoint 1: после завершения первых 2-3 задач и появления рабочего скелета
- checkpoint 2: после появления интеграционной поверхности/контракта
- checkpoint 3: перед /aif-verify, когда остались только polish и docs
