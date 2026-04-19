# TECH_STACK

## Решение по стеку для MVP
Стек выбран так, чтобы быстро собрать issuer, portal, gateway-интеграцию и typed contracts в одном репозитории.

## Runtime и язык
- Основной язык: **TypeScript**.
- Runtime: **Node.js LTS**.
- Package manager: **pnpm**.
- Monorepo: **pnpm workspaces**; Turborepo можно добавить позже, если появится много пакетов.

## Backend `idshka-api`
- Framework: **Fastify**.
- Validation/schema: **TypeBox** или **Zod**. В одном проекте выбрать один основной вариант.
- ORM/query layer: **Prisma** для MVP.
- DB: **PostgreSQL**.
- Cache/queues/rate limit: **Redis**.
- JWT/JWK/JWKS: **jose**.
- Password hashing: **Argon2id**.
- API docs: **OpenAPI** + generated schema from contracts.

Причина выбора Fastify:
- быстрый HTTP layer;
- простая plugin-модель;
- удобно держать auth/issuer модули отдельно;
- меньше boilerplate, чем в full enterprise framework.

## Frontend `idshka-portal`
- Framework: **Next.js**.
- UI: **React**.
- Styling: **Tailwind CSS**.
- Forms: React Hook Form + schema validation.
- API client: generated/typed client из `packages/contracts`.
- Session: secure httpOnly cookies для самого `idshka.ru`.

## Edge для подключённых API-сайтов
- Основной вариант: **OpenResty** (`Nginx + Lua`).
- JWT validation: Lua module around JWT/JWKS verification.
- JWKS cache: shared dict + TTL.
- Denylist cache: Redis или shared dict, в зависимости от режима.
- Upstream context:
  - sanitized `X-Idshka-*` headers;
  - optional signed `X-Idshka-Context` + `X-Idshka-Context-Signature`.

## Пример подключённого сайта `apishka.ru`
`apishka.ru` не является частью продукта, но в репозитории можно держать reference example:

- `examples/apishka-api` — простой API, который читает `X-Idshka-*` и/или проверяет `X-Idshka-Context-Signature`.
- `examples/apishka-web` — web-клиент с OIDC login через `idshka.ru`.
- `infra/openresty/apishka` — gateway-конфиг для API-only режима.

## Контракты и спецификации
- `packages/contracts`:
  - JWT claims v1;
  - upstream headers v1;
  - signed context envelope v1;
  - scopes/permissions registry;
  - OpenAPI fragments.
- `docs/API_FLOWS.md`:
  - concrete request/response flows.
- `docs/GATEWAY_CONTRACT.md`:
  - правила проверки токена и проброса контекста.

## Инфраструктура local/dev
- Docker Compose:
  - PostgreSQL;
  - Redis;
  - idshka-api;
  - idshka-portal;
  - OpenResty gateway example;
  - apishka-api example.
- Migrations: Prisma migrations.
- Seed: demo user, demo site `apishka.ru`, demo scopes.

## CI/CD
- GitHub Actions или совместимый CI.
- Обязательные jobs:
  - install;
  - lint;
  - typecheck;
  - unit tests;
  - contract tests;
  - gateway smoke tests через curl;
  - docker build.

## Observability
- Logs: structured JSON logs with `request_id`, `user_id`, `site_id`, `jti`, `aud`.
- Metrics: Prometheus-compatible endpoint.
- Traces: OpenTelemetry-ready instrumentation.
- Dashboards later: Grafana.

## Security tooling
- Secret scanning в CI.
- Dependency audit.
- SAST по возможности.
- Запрет логирования raw tokens, client secrets, private keys.

## Что можно заменить без ломки архитектуры
- Fastify можно заменить на NestJS/Laravel/Go, если сохранить contracts.
- Prisma можно заменить на Drizzle/Knex/SQL, если сохранить миграции и схемы.
- OpenResty можно заменить на Envoy/Kong/Traefik plugin, если сохранить gateway contract.
- Next.js можно заменить на любой frontend, если сохранить `idshka-api` и portal flows.
