---
artifact_type: context
plan_id: 03-apishka-edge-gateway
title: Контекст плана 03-apishka-edge-gateway
artifact_status: ready
owner: project-bootstrap
created_at: 2026-04-15T00:00:00Z
updated_at: 2026-04-15T00:00:00Z
---


# Контекст

## Зачем этот план существует
Поставить перед `apishka-api` обязательный gateway, который сам проверяет токен и прокидывает в upstream только доверенный auth-context.

## In scope
- OpenResty/Nginx config
- Lua validation logic
- Sanitized headers contract
- Gateway-to-upstream trust boundary

## Out of scope
- Portal UI
- Full revoke denylist
- Introspection fallback

## Зависимости и последовательность
- `01-platform-foundation`
- `02-issuer-jwt-core`

## Основные риски
- Ошибки header sanitation дадут privilege escalation
- Слишком агрессивный JWKS cache усложнит ротацию ключей

## Acceptance criteria
- Gateway возвращает 401 на missing/invalid token
- Gateway возвращает 403 на policy-level deny при валидном токене
- На valid token upstream получает X-Idska-Authenticated=1 и прочие agreed headers
- Spoofed X-Idska-* из клиента не доходят до upstream

## Артефакты плана
- Summary: `.ai-factory/plans/03-apishka-edge-gateway.md`
- Tasks: `.ai-factory/plans/03-apishka-edge-gateway/task.md`
- Rules: `.ai-factory/plans/03-apishka-edge-gateway/rules.md`
- Verify: `.ai-factory/plans/03-apishka-edge-gateway/verify.md`
- Status: `.ai-factory/plans/03-apishka-edge-gateway/status.yaml`
