# Project Roadmap

## Summary

Проект выглядит сильным на уровне спецификаций: в репозитории уже есть [DESCRIPTION](DESCRIPTION.md), [ARCHITECTURE](ARCHITECTURE.md), [TECH_STACK](TECH_STACK.md), [SECURITY](SECURITY.md), контракты в [docs/API_FLOWS.md](../docs/API_FLOWS.md) и [docs/GATEWAY_CONTRACT.md](../docs/GATEWAY_CONTRACT.md), а также разложенные по фазам планы в `.ai-factory/plans/`. Это даёт хорошую базу для архитектуры, API-контрактов и проектной документации.

Критический разрыв в том, что репозиторий пока остаётся `spec-first`: отсутствуют `composer.json`, `app/`, `routes/`, `database/`, `tests/`, `infra/`, `.github/workflows`, Docker/Compose и другие runtime markers. Поэтому roadmap ниже трактуется как аудит зрелости: сильнее всего сейчас документация и архитектурная проработка, а запуск, бизнес-логика, данные, тесты и delivery остаются на стадии подготовки.

---

## Slice: Launch / Runtime

**Status:** `missing`

**Evidence:**
- Отсутствуют `composer.json`, `Dockerfile`, `compose.yml`, `docker-compose.yml`, `.env.example`.
- Отсутствуют директории `app/`, `routes/`, `database/`, `infra/`, `resources/`.
- Целевой runtime описан только в [TECH_STACK.md](TECH_STACK.md) и в [plans/01-laravel-platform-foundation/status.yaml](plans/01-laravel-platform-foundation/status.yaml).

**Commentary:**
- Запуск и локальная среда хорошо определены как цель, но в репозитории пока нет исполняемой Laravel-основы и инфраструктурных файлов.

**Next Steps:**
- [ ] Создать Laravel skeleton с `composer.json`, `app/`, `routes/`, `config/`, `bootstrap/`.
- [ ] Добавить `.env.example`, Docker Compose и базовые `health` / `readiness` endpoints.

---

## Slice: Architecture / Structure

**Status:** `partial`

**Evidence:**
- Есть [ARCHITECTURE.md](ARCHITECTURE.md) с выбранным паттерном `Modular Monolith`.
- Есть [docs/LARAVEL_MODULES.md](../docs/LARAVEL_MODULES.md) и [rules/base.md](rules/base.md).
- Отсутствуют целевые директории `app/Domain`, `routes/`, `database/`, указанные в архитектуре.

**Commentary:**
- Архитектурные границы и dependency rules сформулированы чётко, но пока не зафиксированы в реальной структуре кода.

**Next Steps:**
- [ ] Скелетировать модули `Identity`, `Sites`, `ApiResources`, `OidcClients`, `Issuer`, `Audit`.
- [ ] Завести реальные entry points `routes/web.php`, `routes/api.php`, `routes/oauth.php` и публичные contracts между модулями.

---

## Slice: Core Business Logic

**Status:** `missing`

**Evidence:**
- Доменные сценарии описаны в [DESCRIPTION.md](DESCRIPTION.md), [docs/LARAVEL_MODULES.md](../docs/LARAVEL_MODULES.md) и планах `02..07`.
- Статусы ключевых фаз остаются `planned`, например [plans/02-user-auth-socialite/status.yaml](plans/02-user-auth-socialite/status.yaml) и [plans/04-token-issuer-and-jwks/status.yaml](plans/04-token-issuer-and-jwks/status.yaml).
- Реализаций в `app/Domain/*` нет.

**Commentary:**
- Бизнес-правила и bounded contexts описаны как проектное намерение, но ещё не перешли в исполняемые actions, services, events и policies.

**Next Steps:**
- [ ] После foundation реализовать первый рабочий доменный вертикальный срез: `Identity` + user login.
- [ ] Затем поднять `Sites` и `Issuer` как отдельные модули с явными инвариантами и public API.

---

## Slice: API / Contracts

**Status:** `partial`

**Evidence:**
- Есть [docs/API_FLOWS.md](../docs/API_FLOWS.md) с request/response сценариями.
- Есть [docs/GATEWAY_CONTRACT.md](../docs/GATEWAY_CONTRACT.md) и [specs/index.yaml](specs/index.yaml).
- Отсутствуют `routes/api.php`, `routes/oauth.php`, request classes, controllers и executable schema validation.

**Commentary:**
- Внешняя поверхность проекта уже зафиксирована в документации, что снижает риск расползания контракта. Но пока это contract-by-docs, а не contract-by-code.

**Next Steps:**
- [ ] Перенести claims, headers и scopes в PHP contracts / DTO / value objects.
- [ ] Реализовать documented owner API и `/oauth/*` surface в том же порядке, в каком они описаны в docs.

---

## Slice: Data / Database / Migrations

**Status:** `missing`

**Evidence:**
- Список core tables описан в [TECH_STACK.md](TECH_STACK.md) и [ARCHITECTURE.md](ARCHITECTURE.md).
- Плановые схемы перечислены в [plans/04-token-issuer-and-jwks/status.yaml](plans/04-token-issuer-and-jwks/status.yaml).
- Отсутствуют `database/`, migrations, seeders, Eloquent models и runtime validation rules.

**Commentary:**
- Модель данных определена концептуально, но на уровне репозитория пока нет ни одной миграции или фактической схемы.

**Next Steps:**
- [ ] Создать базовые migrations для `users`, `social_accounts`, `sites`, `site_verifications`, `site_modes`.
- [ ] После этого добавить issuer- и client-related schemas: `api_tokens`, `oidc_clients`, `signing_keys`, `revoked_jti`, `audit_events`.

---

## Slice: Security / Auth / Secrets

**Status:** `partial`

**Evidence:**
- Есть [SECURITY.md](SECURITY.md) с threat model и базовыми мерами.
- Есть security rules в [RULES.md](RULES.md) и [rules/base.md](rules/base.md).
- Security-sensitive contracts описаны в [docs/GATEWAY_CONTRACT.md](../docs/GATEWAY_CONTRACT.md) и [docs/SOCIALITE.md](../docs/SOCIALITE.md).
- Отсутствуют middleware, rate limits, real secret handling, revoke cache, audit hooks и проверочные тесты.

**Commentary:**
- Для spec-first проекта security-проработка уже неплохая: есть явные угрозы, fail-closed rules и границы доверия. Но enforcement пока отсутствует, поэтому зрелость остаётся только частичной.

**Next Steps:**
- [ ] Реализовать PKCE/state/nonce/redirect validation и базовый audit trail.
- [ ] Добавить rate limits, secret storage discipline и smoke/tests для `aud`, `iss`, `jti`, header sanitization.

---

## Slice: Integrations / External Services

**Status:** `partial`

**Evidence:**
- Интеграции с Socialite-провайдерами описаны в [docs/SOCIALITE.md](../docs/SOCIALITE.md).
- Gateway и connected-site flow зафиксированы в [docs/API_FLOWS.md](../docs/API_FLOWS.md) и [docs/GATEWAY_CONTRACT.md](../docs/GATEWAY_CONTRACT.md).
- Целевые examples отражены в [plans/05-api-resource-gateway-for-apishka](plans/05-api-resource-gateway-for-apishka/context.md) и [plans/06-web-login-through-idshka](plans/06-web-login-through-idshka/context.md).
- Отсутствуют provider adapters, `infra/openresty/apishka`, `examples/apishka-api`, `examples/apishka-web-laravel`.

**Commentary:**
- Внешние интеграционные границы уже описаны достаточно подробно, но рабочие adapters/examples пока не собраны.

**Next Steps:**
- [ ] Поднять хотя бы один реальный Socialite provider для MVP и flow account linking.
- [ ] Добавить gateway reference и минимальные example-consumers, чтобы контракты стали проверяемыми.

---

## Slice: Quality / Tests / Validation

**Status:** `missing`

**Evidence:**
- Отсутствует директория `tests/`.
- Отсутствуют PHPUnit/Pest config, static-analysis config, lint commands и executable smoke scripts.
- Есть только плановые checklist-файлы, например [plans/01-laravel-platform-foundation/verify.md](plans/01-laravel-platform-foundation/verify.md) и [plans/06-web-login-through-idshka/verify.md](plans/06-web-login-through-idshka/verify.md).

**Commentary:**
- Quality gates спроектированы как ожидание, но в репозитории пока нет ни тестового каркаса, ни автоматической валидации контрактов.

**Next Steps:**
- [ ] Выбрать единый тестовый стек: PHPUnit или Pest.
- [ ] Добавить первые feature tests для login, site creation и token issuance, затем smoke checks для gateway/OAuth flows.

---

## Slice: CI/CD / Delivery

**Status:** `missing`

**Evidence:**
- Отсутствует `.github/workflows/`.
- Нет build automation, deploy scripts, container build definitions и release automation.
- Требуемые jobs описаны только в [TECH_STACK.md](TECH_STACK.md) и в plan artifacts.

**Commentary:**
- Процесс доставки ещё не существует в исполняемом виде. Пока это чисто проектное намерение.

**Next Steps:**
- [ ] После появления runtime skeleton добавить CI для install, tests, npm build и smoke checks.
- [ ] Зафиксировать container/build pipeline и минимальный release flow.

---

## Slice: Observability / Logs / Metrics

**Status:** `partial`

**Evidence:**
- Требования к `request_id`, structured logs и metrics описаны в [TECH_STACK.md](TECH_STACK.md).
- Ops expectations зафиксированы в [rules/ops.md](rules/ops.md).
- Целевые outputs перечислены в [plans/08-security-hardening-and-ops/status.yaml](plans/08-security-hardening-and-ops/status.yaml).
- Отсутствуют health/readiness endpoints, logging config, metrics/tracing integration и alerting artifacts.

**Commentary:**
- Набор operational expectations уже определён, но это пока не instrumentation, а только требования к будущей реализации.

**Next Steps:**
- [ ] Добавить `request_id` propagation и базовый structured logging profile.
- [ ] Реализовать health/readiness endpoints и минимальный metrics surface для Laravel/gateway.

---

## Slice: Documentation / DX

**Status:** `partial`

**Evidence:**
- Есть [docs/README.md](../docs/README.md), [docs/API_FLOWS.md](../docs/API_FLOWS.md), [docs/GATEWAY_CONTRACT.md](../docs/GATEWAY_CONTRACT.md), [docs/LARAVEL_MODULES.md](../docs/LARAVEL_MODULES.md), [docs/SOCIALITE.md](../docs/SOCIALITE.md).
- Есть AI Factory context: [DESCRIPTION.md](DESCRIPTION.md), [ARCHITECTURE.md](ARCHITECTURE.md), [TECH_STACK.md](TECH_STACK.md), [RULES.md](RULES.md), планы `01..08`.
- Отсутствует корневой `README.md`, а setup-команды пока не могут быть реально воспроизведены на текущем содержимом репозитория.

**Commentary:**
- Это самый сильный срез проекта на текущий момент: документации много, и она согласована между собой. Но DX всё ещё неполный, потому что нет рабочего bootstrap path от checkout до запуска.

**Next Steps:**
- [ ] После foundation добавить корневой README с реальным onboarding flow.
- [ ] Синхронизировать docs с первыми исполняемыми Laravel artifacts, чтобы не возник spec drift.

---

## Strategic Priorities

1. Поднять `01-laravel-platform-foundation`, потому что без runtime skeleton остальные срезы не могут перейти из design в implementation.
2. Перевести auth/domain contracts в рабочие модули `Identity`, `Sites` и `Issuer`, начиная с минимального вертикального сценария.
3. Как можно раньше подключить tests, smoke validation и CI, чтобы документация перестала быть единственным источником уверенности.

## Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Spec drift между docs и будущей реализацией | High | Реализовывать фазы строго по documented contracts и обновлять docs в том же изменении |
| Security assumptions останутся непроверенными слишком долго | High | Рано добавить smoke/tests для PKCE, audience, gateway header sanitization и token lifecycle |
| Слишком долгое отсутствие runtime foundation замедлит все остальные области | High | Сначала закрыть `01-laravel-platform-foundation`, не распыляясь на поздние фичи |
| Отсутствие CI приведёт к тихой деградации контрактов и документации | Medium | После появления skeleton сразу завести минимальный pipeline на install, tests и docs/smoke checks |

## Suggested Planning Order

1. `01-laravel-platform-foundation`
2. `02-user-auth-socialite`
3. `03-site-registry-and-modes`
4. `04-token-issuer-and-jwks`
5. `05-api-resource-gateway-for-apishka`
6. `06-web-login-through-idshka`
7. `07-portal-token-and-client-management`
8. `08-security-hardening-and-ops`

---

*Last updated: 2026-04-19*
*Analyzed slices: 11*
