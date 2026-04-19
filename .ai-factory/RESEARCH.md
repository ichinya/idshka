# RESEARCH

## Active Summary (input for /aif-plan)
Проект стартует как greenfield.
Базовое решение для MVP:
- `idska.ru` — issuer/control plane;
- `apishka.ru` — API consumer с OpenResty gateway;
- токен в первой версии — JWT `RS256` с `aud=apishka`, `kid`, `jti`, `roles`, `permissions`, `scope`;
- gateway валидирует подпись локально по JWKS и пробрасывает `X-Idska-*` в upstream;
- стек по умолчанию: TypeScript monorepo + PostgreSQL + Redis + OpenResty.

Открытые вопросы для следующих итераций:
- когда вводить opaque PAT + exchange flow;
- нужен ли site-specific RBAC editor уже в MVP;
- нужна ли emergency introspection как обязательная часть M1 или только M4.

## Sessions
### 2026-04-15
Собран стартовый комплект артефактов под AI Factory + AIFHub extension:
config, description, architecture, roadmap, global/base/area rules и full-plan пакеты.
