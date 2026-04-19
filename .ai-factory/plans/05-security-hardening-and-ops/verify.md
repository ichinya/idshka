---
artifact_type: verify
plan_id: 05-security-hardening-and-ops
title: Проверка плана 05-security-hardening-and-ops
artifact_status: draft
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Verify Checklist

## Functional
- [ ] Revoke по jti перестаёт пропускаться через gateway в пределах целевого TTL
- [ ] Gateway продолжает валидировать токены во время ротации active/next ключей
- [ ] Metrics доступны и отражают auth success/failure/revoke/cache state
- [ ] Runbooks позволяют отработать инцидент без поиска знаний по чату

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
