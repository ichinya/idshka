# ROADMAP

## Вектор продукта
Сделать `idshka.ru` Laravel-сервисом подключения сайтов, где владелец может включить для своего домена API-only режим, web login через `idshka.ru` или оба режима.

## Phase 1 — Laravel foundation
Цель: поднять основу проекта.

Готово, когда:
- создан Laravel app skeleton;
- настроены `.env.example`, Docker Compose, PostgreSQL, Redis, nginx/php-fpm;
- есть `health`/`readiness` endpoints;
- есть базовые migrations, factories, seeders;
- настроены tests и CI skeleton;
- структура `app/Domain/*` создана.

## Phase 2 — User auth + Socialite
Цель: пользователь может войти на `idshka.ru`.

Готово, когда:
- есть email/password вход или выбранный Laravel auth scaffold;
- подключён Laravel Socialite;
- есть routes `/auth/{provider}/redirect` и `/auth/{provider}/callback`;
- Google/VK/Yandex описаны как provider adapters;
- есть таблица `social_accounts`;
- один пользователь может привязать несколько внешних аккаунтов;
- audit фиксирует login/link/unlink.

## Phase 3 — Site registry and modes
Цель: владелец подключает `apishka.ru`.

Готово, когда:
- есть `sites`, `site_verifications`, `site_modes`;
- домен нормализуется и проверяется;
- DNS TXT и file verification работают;
- можно включить `api_resource`, `web_client` или оба режима;
- portal показывает статус сайта.

## Phase 4 — API token issuer + JWKS
Цель: `idshka.ru` выпускает токены для API-only режима.

Готово, когда:
- есть `signing_keys`, `api_tokens`, `revoked_jti`;
- `GET /oauth/jwks.json` отдаёт public keys;
- `POST /v1/user/api-tokens` выпускает JWT с `iss/aud/sub/site_id/scope/permissions/jti/exp`;
- raw token показывается один раз;
- revoke кладёт `jti` в denylist;
- tests проверяют валидный, просроченный и неверный audience.

## Phase 5 — Gateway reference for API-only `apishka.ru`
Цель: запросы к `api.apishka.ru` проходят первичную проверку до upstream.

Готово, когда:
- есть OpenResty/Nginx reference config;
- Lua-модуль кеширует JWKS;
- gateway проверяет подпись, `iss`, `aud`, `exp`, `nbf`, `jti`;
- gateway удаляет входящие `X-Idshka-*`;
- gateway добавляет trusted `X-Idshka-*`;
- smoke test curl проходит с валидным токеном и падает с невалидным.

## Phase 6 — Web login через `idshka.ru`
Цель: `apishka.ru` может быть web-сайтом с входом через `idshka.ru`.

Готово, когда:
- есть `oidc_clients` и `oidc_redirect_uris`;
- работает `GET /oauth/authorize`;
- работает `POST /oauth/token`;
- authorization code одноразовый;
- PKCE/state/nonce проверяются;
- `id_token` валидируется;
- есть example `apishka-web-laravel` с кастомным Socialite provider или callback controller.

## Phase 7 — Portal UX
Цель: self-service кабинет без ручных операций.

Готово, когда:
- владелец сайта создаёт сайт, видит verification-инструкции;
- владелец настраивает modes, scopes, redirect URIs;
- конечный пользователь создаёт, видит и отзывает токены;
- есть audit log и список сессий;
- secrets показываются только один раз.

## Phase 8 — Security hardening and ops
Цель: безопасный MVP для закрытого/пилотного использования.

Готово, когда:
- включены rate limits;
- настроена ротация signing keys;
- raw tokens/secrets не попадают в logs;
- есть backup/restore заметки;
- есть security runbook;
- есть smoke/e2e сценарии для API-only и web login.

## Очерёдность планов
1. `01-laravel-platform-foundation`
2. `02-user-auth-socialite`
3. `03-site-registry-and-modes`
4. `04-token-issuer-and-jwks`
5. `05-api-resource-gateway-for-apishka`
6. `06-web-login-through-idshka`
7. `07-portal-token-and-client-management`
8. `08-security-hardening-and-ops`
