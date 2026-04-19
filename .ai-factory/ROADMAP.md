# ROADMAP

## Вектор продукта
Построить минимальный, понятный и безопасный control plane для API-доступа:
`idska` выпускает токены и знает права, `apishka` принимает только уже проверенный auth-context.

## Milestone M0 — Project Bootstrap
**Статус:** planned  
**План:** `01-platform-foundation`

Результат:
- monorepo готов;
- базовые сервисы поднимаются локально;
- contracts package и базовые env/compose/CI есть.

Done when:
- локальный `docker compose up` поднимает PostgreSQL, Redis и OpenResty;
- `idska-api` и `apishka-api` имеют `/health`;
- есть typed contracts для token claims и upstream headers.

## Milestone M1 — Token Issuer Core
**Статус:** planned  
**План:** `02-issuer-jwt-core`

Результат:
- `idska-api` умеет выпускать и отзывать токены;
- публичный JWKS endpoint работает;
- аудит сохраняется.

Done when:
- можно создать JWT для audience `apishka`;
- gateway может валидировать подпись по JWKS;
- revoke записывается и доступен для denylist cache.

## Milestone M2 — Edge Auth Gateway for apishka
**Статус:** planned  
**План:** `03-apishka-edge-gateway`

Результат:
- `api.apishka.ru` защищён OpenResty;
- upstream получает только доверенный набор `X-Idska-*` заголовков.

Done when:
- валидный токен проходит;
- просроченный/поддельный/неподходящий по audience токен блокируется;
- входящие пользовательские `X-Idska-*` не проходят до upstream.

## Milestone M3 — Self-Service Portal & Site Onboarding
**Статус:** planned  
**План:** `04-portal-onboarding-and-token-management`

Результат:
- пользователь сам создаёт токен;
- видит привязанные consumer-сайты и usage/revoke;
- есть инструкции для вызова `apishka`.

Done when:
- токен можно выпустить через UI;
- токен показывается один раз;
- revoke доступен из кабинета;
- есть рабочий curl snippet.

## Milestone M4 — Hardening, Revocation, Observability
**Статус:** planned  
**План:** `05-security-hardening-and-ops`

Результат:
- безопасная ротация ключей;
- denylist и emergency revoke;
- rate limits, dashboards, incident runbooks.

Done when:
- ревокнутый `jti` перестаёт работать на gateway в пределах целевого TTL;
- ключ можно перевести в `next -> active -> retired` без простоя;
- базовые auth-метрики и дашборды есть.

## Очерёдность исполнения
1. `01-platform-foundation`
2. `02-issuer-jwt-core`
3. `03-apishka-edge-gateway`
4. `04-portal-onboarding-and-token-management`
5. `05-security-hardening-and-ops`

## Дальше, после MVP
- opaque PAT + exchange flow для долгоживущих интеграций;
- multi-tenant orgs / teams;
- site-specific role templates;
- external developer portal и SDK;
- OAuth/OIDC client onboarding для сторонних сайтов;
- billing / quotas.
