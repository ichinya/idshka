---
artifact_type: verify
plan_id: 03-apishka-edge-gateway
title: Проверка плана 03-apishka-edge-gateway
artifact_status: draft
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Verify Checklist

## Functional
- [ ] Gateway возвращает 401 на missing/invalid token
- [ ] Gateway возвращает 403 на policy-level deny при валидном токене
- [ ] На valid token upstream получает X-Idska-Authenticated=1 и прочие agreed headers
- [ ] Spoofed X-Idska-* из клиента не доходят до upstream

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
