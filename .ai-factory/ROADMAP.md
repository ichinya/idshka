# ROADMAP

## Вектор продукта
Сделать `idshka.ru` сервисом подключения сайтов, где владелец может включить:

- **API-only режим**: токены создаются на `idshka.ru`, а API-сайт `apishka.ru` пускает запросы через gateway-проверку.
- **Web login режим**: сайт `apishka.ru` использует `idshka.ru` как провайдера входа.

## Milestone M0 — Project Bootstrap & Contracts
**Статус:** planned  
**План:** `01-platform-foundation`

Результат:
- monorepo готов;
- `idshka-api`, `idshka-portal`, examples и infra skeleton есть;
- базовый contracts layer создан.

Done when:
- локальный `docker compose up` поднимает PostgreSQL, Redis, idshka-api, idshka-portal и gateway example;
- есть health endpoints;
- есть typed contracts для JWT claims, upstream headers и OIDC client metadata.

## Milestone M1 — Connected Site Registry
**Статус:** planned  
**План:** `02-site-registry-and-modes`

Результат:
- владелец добавляет `apishka.ru`;
- подтверждает домен;
- выбирает режимы `api_resource` и/или `web_client`.

Done when:
- домен можно подтвердить через DNS TXT или `/.well-known/` file;
- сайт получает `site_id`;
- для сайта можно включить API audience и OIDC client.

## Milestone M2 — Token Issuer & JWKS
**Статус:** planned  
**План:** `03-token-issuer-and-jwks`

Результат:
- `idshka-api` выпускает JWT для API-only режима;
- JWKS endpoint работает;
- revoke сохраняется.

Done when:
- можно создать токен для `aud=apishka.ru`;
- токен содержит user/site/scopes/permissions;
- gateway может валидировать подпись по JWKS;
- revoke фиксируется в denylist.

## Milestone M3 — API-only Gateway for `apishka.ru`
**Статус:** planned  
**План:** `04-api-resource-gateway-for-apishka`

Результат:
- example gateway защищает API-only сайт;
- upstream получает `X-Idshka-*` и/или signed context.

Done when:
- валидный токен проходит;
- поддельный/просроченный/audience mismatch токен блокируется;
- клиентские `X-Idshka-*` удаляются;
- upstream получает проверяемый context.

## Milestone M4 — Web Login through `idshka.ru`
**Статус:** planned  
**План:** `05-web-login-oidc-for-apishka`

Результат:
- `apishka.ru` может быть web-client;
- вход идёт через Authorization Code + PKCE;
- callback создаёт локальную сессию `apishka.ru`.

Done when:
- `GET /oauth/authorize` и `POST /oauth/token` работают для registered client;
- строгая проверка redirect URI включена;
- `id_token` валидируется;
- example `apishka-web` логинится через `idshka.ru`.

## Milestone M5 — Portal Management UX
**Статус:** planned  
**План:** `06-portal-token-and-client-management`

Результат:
- пользователь видит сайты, режимы, токены, web clients, audit;
- может создать/revoke токен;
- владелец сайта может управлять redirect URI и scopes.

Done when:
- raw token показывается один раз;
- client secret показывается один раз или ротируется;
- есть snippets для API-only и web-client;
- audit понятен из UI.

## Milestone M6 — Security Hardening & Ops
**Статус:** planned  
**План:** `07-security-hardening-and-ops`

Результат:
- key rotation;
- hard revoke / denylist;
- rate limits;
- observability;
- incident runbooks.

Done when:
- ключи переходят `next -> active -> retired` без downtime;
- revoked `jti` блокируется gateway в пределах согласованного TTL;
- есть метрики, структурированные логи и runbooks.

## Очерёдность исполнения
1. `01-platform-foundation`
2. `02-site-registry-and-modes`
3. `03-token-issuer-and-jwks`
4. `04-api-resource-gateway-for-apishka`
5. `05-web-login-oidc-for-apishka`
6. `06-portal-token-and-client-management`
7. `07-security-hardening-and-ops`

## После MVP
- opaque long-lived PAT + exchange на короткие JWT;
- SDK для подключённых сайтов;
- Envoy/Kong gateway adapters;
- org/team model;
- billing/quotas;
- policy templates;
- webhook-события для подключённых сайтов.
