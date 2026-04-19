---
artifact_type: verify
plan_id: 01-platform-foundation
title: Проверка плана 01-platform-foundation
artifact_status: draft
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Verify Checklist

## Functional
- [ ] docker compose up поднимает PostgreSQL, Redis, OpenResty и сервисы
- [ ] GET /health на idska-api и apishka-api отвечает 200
- [ ] contracts package содержит схемы JWT claims и X-Idska-* headers
- [ ] CI workflow проходит на пустой вертикали проекта

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
