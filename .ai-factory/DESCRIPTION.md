# DESCRIPTION

## Что строим
`idska.ru` — issuer/control plane для пользовательских API-токенов и прав.
`apishka.ru` — API-only сервис, который принимает токены от `idska` и доверяет
только предварительно проверенному контексту, проброшенному edge-слоем.

Пользовательский сценарий MVP:
1. Пользователь заходит на `idska.ru`.
2. Создаёт токен для конкретной аудитории/сайта, например `apishka`.
3. Вызывает `api.apishka.ru`, передавая `Authorization: Bearer <token>`.
4. Nginx/OpenResty на входе `apishka` валидирует подпись, audience, expiry и обязательные claims.
5. Gateway очищает входящие auth-заголовки, добавляет `X-Idska-*` и проксирует запрос в upstream.
6. Upstream знает, кто пользователь и какие у него роли/права, не перепроверяя JWT из публичного трафика.

## Зачем продукт нужен
- Централизовать выпуск токенов и права доступа.
- Не дублировать auth-логику в каждом API.
- Разделить control plane (`idska`) и data plane (`apishka`).
- Получить аудит: кто создал токен, когда использовал, какие scopes были выданы.

## Основные возможности MVP
- Личный кабинет пользователя на `idska`.
- Самостоятельное создание токенов с label, audience, scopes, expiry.
- Каталог подключённых consumer-сайтов/аудиторий.
- JWKS endpoint для публичной проверки подписи.
- JWT-подпись с ротацией ключей по `kid`.
- Edge-валидация на `apishka` и проброс auth-context через заголовки.
- Базовый аудит выдачи, использования и отзыва токенов.

## Не в MVP
- Полноценный OAuth Authorization Code Flow для third-party приложений.
- SCIM / enterprise provisioning.
- SSO для браузерных приложений.
- Мульти-тенантные организации с биллингом.
- Сложные ABAC-политики и policy DSL.

## Бизнес-правила первой версии
- Каждый токен привязан к одной audience (`apishka`, позже — к site_id).
- Права кодируются двумя уровнями:
  - `roles`: компактные роли для UI и coarse-grained checks
  - `permissions` / `scope`: плоский список разрешений для upstream
- Upstream запрещено доверять пользовательским `X-Idska-*` из внешнего трафика.
- Токен действует только пока подпись валидна, срок не истёк, audience совпадает и `jti` не отозван.

## Базовый стек для старта
- Monorepo на TypeScript.
- `idska-api`: Node.js 22 + Fastify + Zod/TypeBox + Prisma/PostgreSQL.
- `idska-portal`: Next.js 15 + React + server actions / API client.
- `apishka-api`: Node.js 22 + Fastify.
- `apishka-edge`: OpenResty (Nginx + Lua) с локальной JWT-проверкой через JWKS.
- Redis для denylist/revocation cache и rate limiting.
- Docker Compose для локального запуска.

Если позже стек изменится, обязательными остаются архитектурные границы и контракт `JWT -> sanitized headers -> upstream`.
