---
artifact_type: verify
plan_id: 04-portal-onboarding-and-token-management
title: Проверка плана 04-portal-onboarding-and-token-management
artifact_status: draft
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Verify Checklist

## Functional
- [ ] UI создаёт токен и показывает raw value только один раз
- [ ] После перезагрузки страницы raw token недоступен
- [ ] Revoke из UI меняет статус токена без ручных правок в БД
- [ ] curl snippet реально работает против apishka на valid token

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
