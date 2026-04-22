# Project Roadmap

## Summary

Проект уже вышел из чистого `spec-first`: в репозитории есть [DESCRIPTION](DESCRIPTION.md), [ARCHITECTURE](ARCHITECTURE.md), [TECH_STACK](TECH_STACK.md), [SECURITY](SECURITY.md), исполняемый Laravel foundation, `compose.yml`, `README.md`, `tests/`, `infra/` и CI workflow. Это даёт не только архитектурную и контрактную базу, но и реальный runtime baseline для следующих фаз.

Главный разрыв сместился из platform bootstrap в предметную реализацию. Сильнее всего сейчас выглядят architecture/contracts/docs и foundation-уровень запуска, а основная незавершённость лежит в Socialite/login flow, site registry, issuer/JWKS, предметных migrations и security enforcement.

---

## Slice: Launch / Runtime

**Status:** `partial`

**Evidence:**
- Есть `composer.json`, `package.json`, `compose.yml`, `.env.example`, [README.md](../README.md) и container definitions в `infra/docker/`.
- Есть директории `app/`, `routes/`, `database/`, `infra/`, `resources/`, `tests/` и базовый OpenResty reference в `infra/openresty/apishka/`.
- Проверены `docker compose up -d --build`, `GET /health` и `GET /ready`; приложение поднимается с PostgreSQL и Redis локально.

**Commentary:**
- Исполняемая платформа уже собрана и пригодна для локальной разработки. Незавершённой остаётся production-oriented часть: self-sufficient container build, release/bootstrap runbook и более жёсткий deployment profile.

**Next Steps:**
- [ ] Сделать container build самодостаточным, чтобы `docker compose up` не зависел от заранее установленного `vendor/` на хосте.
- [ ] Добавить production profile/runbook для миграций, bootstrap и release-потока.

---

## Slice: Architecture / Structure

**Status:** `partial`

**Evidence:**
- Есть [ARCHITECTURE.md](ARCHITECTURE.md) с выбранным паттерном `Modular Monolith`.
- Есть [docs/LARAVEL_MODULES.md](../docs/LARAVEL_MODULES.md) и [rules/base.md](rules/base.md).
- Есть `app/Domain/Identity`, `Sites`, `ApiResources`, `OidcClients`, `Issuer`, `Audit`, а также `app/Contracts/Auth`.
- Есть реальные entry points: `routes/web.php`, `routes/api.php`, `routes/oauth.php`, middleware `AssignRequestId` и API controllers для health/readiness.

**Commentary:**
- Архитектурные границы уже зафиксированы в директориях и entry points. Но это пока каркас: модули представлены placeholder-структурой без предметных `Actions`, `Services`, `Models` и public contracts в коде.

**Next Steps:**
- [ ] Заменить README-placeholder'ы в доменных модулях на первые исполняемые vertical slices.
- [ ] Выносить межмодульные контракты в PHP types/DTO/interfaces по мере появления реальной логики, не размывая границы controllers vs domain.

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
- Есть `routes/api.php` и `routes/oauth.php`, а также реализованные operational endpoints `/health` и `/ready`.
- Есть placeholder для `app/Contracts/Auth`, но ещё нет исполняемых claims/scopes/header value objects и предметных request classes.

**Commentary:**
- Внешняя поверхность проекта уже начала материализоваться в коде на уровне route files и operational endpoints. Но owner API и OAuth/OIDC surface всё ещё находятся в состоянии contract-by-docs, а не contract-by-code.

**Next Steps:**
- [ ] Перенести claims, headers и scopes из docs/README-placeholder'ов в PHP contracts / DTO / value objects.
- [ ] Реализовать documented owner API и `/oauth/*` surface в том же порядке, в каком они описаны в docs.

---

## Slice: Data / Database / Migrations

**Status:** `partial`

**Evidence:**
- Список core tables описан в [TECH_STACK.md](TECH_STACK.md) и [ARCHITECTURE.md](ARCHITECTURE.md).
- Плановые схемы перечислены в [plans/04-token-issuer-and-jwks/status.yaml](plans/04-token-issuer-and-jwks/status.yaml).
- Есть `database/`, базовые Laravel migrations (`users`, `cache`, `jobs`), `DatabaseSeeder` и `app/Models/User.php`.
- PostgreSQL уже включён в runtime через `.env.example` и `compose.yml`, но предметных migrations для `social_accounts`, `sites`, `oidc_clients`, `signing_keys`, `audit_events` пока нет.

**Commentary:**
- Persistence foundation уже существует, поэтому следующий шаг не в создании data layer с нуля, а в замене generic Laravel baseline на предметную схему проекта.

**Next Steps:**
- [ ] Добавить migrations для `social_accounts`, `sites`, `site_verifications`, `site_modes` и связать их с текущим `users`.
- [ ] Затем добавить issuer- и client-related schemas: `api_tokens`, `oidc_clients`, `signing_keys`, `revoked_jti`, `audit_events`.

---

## Slice: Security / Auth / Secrets

**Status:** `partial`

**Evidence:**
- Есть [SECURITY.md](SECURITY.md) с threat model и базовыми мерами.
- Есть security rules в [RULES.md](RULES.md) и [rules/base.md](rules/base.md).
- Security-sensitive contracts описаны в [docs/GATEWAY_CONTRACT.md](../docs/GATEWAY_CONTRACT.md) и [docs/SOCIALITE.md](../docs/SOCIALITE.md).
- Есть базовый middleware `AssignRequestId`, lifecycle logging запросов и readiness-проверки для DB/Redis.
- Отсутствуют auth-specific middleware, rate limits, real secret handling, revoke cache, audit hooks и проверочные security tests.

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
- Есть `infra/openresty/apishka/nginx.conf`, `infra/openresty/apishka/lua/README.md`, `examples/apishka-api/README.md` и `examples/apishka-web-laravel/README.md`.
- Отсутствуют provider adapters, реальные example-consumers и end-to-end integration tests.

**Commentary:**
- Внешние интеграционные границы уже описаны и частично заскелечены в infra/examples. Но это ещё reference baseline, а не рабочие adapters/examples, через которые можно прогонять полные flows.

**Next Steps:**
- [ ] Поднять хотя бы один реальный Socialite provider для MVP и flow account linking.
- [ ] Добавить gateway reference и минимальные example-consumers, чтобы контракты стали проверяемыми.

---

## Slice: Quality / Tests / Validation

**Status:** `partial`

**Evidence:**
- Есть `tests/`, `phpunit.xml`, `tests/TestCase.php`, `tests/Feature/FoundationSmokeTest.php` и composer script `composer test`.
- Проверены `php artisan test --without-tty`, `npm run build`, `composer validate --strict` и `docker compose config`.
- Отсутствуют static analysis, contract tests, security tests и feature tests для бизнес-флоу.

**Commentary:**
- Базовый quality gate для foundation уже есть и реально выполняется. Но он пока покрывает только bootstrap/runtime, а не доменные и security-sensitive сценарии.

**Next Steps:**
- [ ] Добавить feature tests для login, site creation и token issuance, затем smoke checks для gateway/OAuth flows.
- [ ] Подключить static analysis и отдельные проверки security/contract drift.

---

## Slice: CI/CD / Delivery

**Status:** `partial`

**Evidence:**
- Есть `.github/workflows/ci.yml` с шагами `composer validate`, `composer install`, `npm ci`, `npm run build`, `php artisan test` и `docker compose config`.
- Есть container build definitions в `infra/docker/` и локальный orchestration layer в `compose.yml`.
- Отсутствуют publish/deploy jobs, image registry flow и release automation.

**Commentary:**
- Базовый CI уже существует и контролирует foundation на каждом push/PR. Но delivery pipeline в production-смысле пока отсутствует.

**Next Steps:**
- [ ] Добавить integration jobs с PostgreSQL/Redis и smoke-проверки containerized runtime.
- [ ] Зафиксировать container/build pipeline и минимальный release flow.

---

## Slice: Observability / Logs / Metrics

**Status:** `partial`

**Evidence:**
- Требования к `request_id`, structured logs и metrics описаны в [TECH_STACK.md](TECH_STACK.md).
- Ops expectations зафиксированы в [rules/ops.md](rules/ops.md).
- Целевые outputs перечислены в [plans/08-security-hardening-and-ops/status.yaml](plans/08-security-hardening-and-ops/status.yaml).
- Есть middleware `AssignRequestId` с request correlation и lifecycle logging.
- Есть `/health` и `/ready`, включая readiness checks к базе и Redis.
- Отсутствуют metrics/tracing integration, alerting artifacts и отдельный observability runbook.

**Commentary:**
- Operational baseline уже появился в коде: сервис даёт health/readiness и correlation id в логах. Но observability всё ещё тонкая и не покрывает метрики, трассировку и алерты.

**Next Steps:**
- [ ] Расширить structured logging за пределы foundation-запросов на предметные auth/issuer flows.
- [ ] Добавить metrics/tracing surface и минимальные observability runbooks для Laravel/gateway.

---

## Slice: Documentation / DX

**Status:** `partial`

**Evidence:**
- Есть [docs/README.md](../docs/README.md), [docs/API_FLOWS.md](../docs/API_FLOWS.md), [docs/GATEWAY_CONTRACT.md](../docs/GATEWAY_CONTRACT.md), [docs/LARAVEL_MODULES.md](../docs/LARAVEL_MODULES.md), [docs/SOCIALITE.md](../docs/SOCIALITE.md).
- Есть AI Factory context: [DESCRIPTION.md](DESCRIPTION.md), [ARCHITECTURE.md](ARCHITECTURE.md), [TECH_STACK.md](TECH_STACK.md), [RULES.md](RULES.md), планы `01..08`.
- Есть корневой [README.md](../README.md) с quickstart для локального и Docker-запуска.
- Есть reference examples и infra skeleton, которые помогают связать docs с исполняемым foundation.

**Commentary:**
- Документация остаётся сильной стороной проекта, но теперь она уже подкреплена воспроизводимым bootstrap path от checkout до запуска foundation. Риск сместился с отсутствия onboarding в spec drift между docs и будущей доменной реализацией.

**Next Steps:**
- [ ] Синхронизировать docs с первыми исполняемыми auth/site/issuer артефактами, чтобы не возник spec drift.
- [ ] Добавить более прикладные runbooks для локального ops/security сценария по мере появления реальной логики.

---

## Strategic Priorities

1. Поднять `02-user-auth-socialite`, чтобы заменить placeholder `Identity` первым рабочим вертикальным сценарием login/account-linking.
2. Реализовать `03-site-registry-and-modes` вместе с предметными migrations, чтобы skeleton перешёл в persistent domain.
3. После этого включить `04-token-issuer-and-jwks`, чтобы перевести auth/gateway contracts из docs в исполняемые сервисы и security checks.

## Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Spec drift между docs и будущей реализацией | High | Реализовывать фазы строго по documented contracts и обновлять docs в том же изменении |
| Security assumptions останутся непроверенными слишком долго | High | Рано добавить smoke/tests для PKCE, audience, gateway header sanitization и token lifecycle |
| README-placeholder'ы в `app/Domain/*` могут разойтись с реальным кодом следующих фаз | High | Быстро заменить placeholder-модули на первые executable slices и обновлять архитектурные артефакты в том же изменении |
| Текущий Docker workflow зависит от заранее установленного `vendor/` на хосте | Medium | Зафиксировать self-sufficient image build или явно поддерживаемую стратегию bootstrap для контейнеров |
| Базовый CI покрывает только foundation и не ловит security/domain regressions | Medium | Добавить integration, contract и security tests до внедрения issuer/login flows |

## Suggested Planning Order

1. `01-laravel-platform-foundation` — completed on 2026-04-19
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
