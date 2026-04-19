---
artifact_type: verify
plan_id: 02-issuer-jwt-core
title: Проверка плана 02-issuer-jwt-core
artifact_status: draft
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Verify Checklist

## Functional
- [ ] POST /v1/tokens возвращает валидный JWT и metadata
- [ ] JWT содержит iss, aud, sub, jti, iat, nbf, exp, scope, roles, permissions, site_id
- [ ] GET /oauth/jwks.json отдаёт ключ, которым JWT успешно проверяется
- [ ] POST /v1/tokens/{id}/revoke идемпотентен и создаёт audit event

## Contracts
- [ ] Обновлены контракты claims / headers / errors при необходимости
- [ ] Не появились несанкционированные новые `X-Idska-*` заголовки
- [ ] Версии и названия полей согласованы между issuer, gateway и upstream

## Security
- [ ] Нет raw token / private key в логах и примерах
- [ ] Ошибки auth не раскрывают лишние детали
- [ ] Проверен happy path и минимум один negative path

## Ops
- [ ] Есть локальный способ воспроизвести проверку
- [ ] Обновлены env/example или runbook при появлении новых переменных
- [ ] Есть evidence для roadmap / plan status update
