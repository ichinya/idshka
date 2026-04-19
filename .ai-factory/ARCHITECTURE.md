# ARCHITECTURE

## Архитектурный стиль
Рекомендуемый старт: **modular monorepo** с явным разделением на control plane,
edge auth gateway и business API.

Почему так:
- продукт стартует как greenfield;
- нужна быстрая итерация по одному репозиторию;
- при этом auth-граница должна быть жёсткой и независимой от кода upstream.

## Контекст системы

```text
Пользователь
  │
  │ 1. создаёт токен / отзывает токен
  ▼
idska-portal
  │
  ▼
idska-api ──────► PostgreSQL
  │  │
  │  └──────────► Redis (revocation, rate limits, cache)
  │
  ├─────────────► /oauth/jwks.json
  └─────────────► /v1/tokens, /v1/sites, /v1/audit

Клиент/скрипт
  │ Authorization: Bearer <jwt>
  ▼
api.apishka.ru (OpenResty / Nginx + Lua)
  │ 2. validate JWT locally via cached JWKS
  │ 3. sanitize headers
  │ 4. inject X-Idska-* headers
  ▼
apishka-api (internal only)
```

## Ключевые компоненты

### 1. `idska-portal`
Назначение:
- UI личного кабинета;
- создание токенов;
- отображение consumer-сайтов, ролей, scopes, аудита.

Ответственность:
- только orchestration/UI;
- не хранит signing secrets;
- не принимает решений по авторизации upstream.

### 2. `idska-api`
Назначение:
- управление пользователями, сайтами, токенами и ключами;
- выпуск подписанных JWT;
- публикация JWKS;
- отзыв токенов и аудит.

Основные bounded contexts:
- Identity
- Token Management
- Site Registry
- Key Management
- Audit

### 3. `apishka-edge`
Назначение:
- единственная публичная точка входа в API;
- быстрая локальная JWT-валидация;
- проброс доверенных заголовков в upstream.

Обязанности:
- удалять любые входящие `X-Idska-*` из внешнего запроса;
- проверять `alg`, `kid`, подпись, `iss`, `aud`, `exp`, `nbf`;
- опционально проверять denylist по `jti`;
- выставлять typed headers для upstream.

### 4. `apishka-api`
Назначение:
- бизнес-логика API;
- проверка permissions уже из проброшенного контекста;
- отсутствие внешней JWT-валидации.

Ограничение:
- доступ только из внутренней сети / через ingress gateway.

### 5. `packages/contracts`
Общее место для:
- схем claims JWT;
- схем `X-Idska-*` заголовков;
- OpenAPI fragments;
- error contracts (`401`, `403`, `429`).

## Контракт токена v1

### Подпись
- Алгоритм: `RS256`
- Обязателен `kid`
- Публичные ключи публикуются в `/.well-known/jwks.json` или `/oauth/jwks.json`

### Обязательные claims
- `iss`: `https://idska.ru`
- `aud`: consumer identifier, например `apishka`
- `sub`: user_id
- `jti`: уникальный id токена
- `iat`
- `nbf`
- `exp`
- `scope`: строка scopes через пробел
- `roles`: массив ролей
- `permissions`: массив детальных разрешений
- `site_id`: идентификатор consumer-сайта
- `email`: опционально, если нужно upstream
- `token_type`: `user_api`

### Рекомендованные ограничения
- default TTL: 1 час
- max TTL для MVP: 24 часа
- для более долгих интеграций — отдельный план на opaque PAT + exchange flow

## Контракт заголовков между edge и upstream

Gateway обязан выставлять:

- `X-Idska-Authenticated: 1`
- `X-Idska-User-Id: <sub>`
- `X-Idska-Email: <email>`
- `X-Idska-Roles: role1,role2`
- `X-Idska-Scopes: orders.read orders.write`
- `X-Idska-Permissions: orders.read,orders.write`
- `X-Idska-JTI: <jti>`
- `X-Idska-Token-Exp: <unix_ts>`
- `X-Idska-Site-Id: <site_id>`
- `X-Request-Id: <request_id>`

Upstream не должен читать оригинальный `Authorization` как источник прав после gateway-валидации.
Если `Authorization` нужен дальше для трассировки, gateway прокидывает его отдельно только во внутреннем контуре.

## Хранилища данных

### PostgreSQL
Базовые таблицы:
- `users`
- `sites`
- `site_memberships`
- `api_tokens`
- `signing_keys`
- `audit_events`
- `revoked_jti`

### Redis
Назначение:
- rate limit на выпуск токенов;
- кэш JWKS для edge;
- denylist revoked `jti`;
- короткоживущие auth caches.

## Репозиторий и модули

```text
apps/
  idska-api/
  idska-portal/
  apishka-api/

infra/
  openresty/
    apishka/
      nginx.conf
      lua/
        jwt_validate.lua
        jwks_cache.lua
        denylist.lua

packages/
  contracts/
    jwt/
    headers/
    openapi/
  observability/
  shared-config/

docs/
  architecture/
  runbooks/
```

## Правила зависимостей
- `idska-portal` зависит только от публичных API `idska-api`.
- `apishka-api` не зависит от JWT library как от primary auth-layer.
- `apishka-edge` может читать только `packages/contracts`, конфиг и сетевые endpoints `idska`.
- Только `idska-api` имеет право подписывать/отзывать токены.
- Только `Key Management` модуль пишет в `signing_keys`.
- Любой код, меняющий контракт claims или headers, обязан обновить `packages/contracts`.

## Nginx / OpenResty стратегия
Основной режим MVP: **локальная проверка подписи по JWKS**.
Fallback-режим для hard revoke/инцидентов: **introspection endpoint** на `idska`, включаемый feature flag.

Порядок проверки на gateway:
1. Есть `Authorization: Bearer ...`
2. JWT parse без trust
3. По `kid` найден подходящий публичный ключ
4. Подпись валидна
5. `iss` совпадает
6. `aud` совпадает с конфигом `apishka`
7. `nbf/exp` валидны
8. `jti` не найден в denylist
9. Удалить пользовательские `X-Idska-*`
10. Установить доверенные headers и проксировать

## Нефункциональные требования
- Fail closed по auth.
- Время ответа gateway на валидном cached key: p95 < 30 ms поверх upstream.
- Вся auth-активность логируется структурированно.
- Ротация ключей не должна приводить к downtime.
- Любой revoke должен распространяться максимум за TTL кэша denylist.

## Риски
- Слишком длинный TTL усложнит revoke.
- Расползание auth-логики в upstream сломает границы.
- Непродуманный claim contract приведёт к частым breaking changes.
- Неполная sanitization заголовков создаст privilege escalation.
