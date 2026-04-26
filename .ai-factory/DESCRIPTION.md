# DESCRIPTION

## Что строим
`idshka.ru` — Laravel-first сервис идентификации, выпуска токенов и управления подключёнными сайтами.

Пользователь подключает свой сайт, например `apishka.ru`, и выбирает один или оба режима:

1. **API-only / resource API**  
   `apishka.ru` работает только как API. Для запроса нужен токен, выпущенный на `idshka.ru`. На входе в `apishka.ru` стоит Nginx/OpenResty gateway: он проверяет токен, удаляет поддельные auth-заголовки и добавляет в upstream подтверждённый контекст пользователя.

2. **Web login / login через idshka.ru**  
   `apishka.ru` имеет web-интерфейс и полноценный вход через `idshka.ru`: пользователь нажимает «Войти через idshka.ru», уходит на `idshka.ru`, после входа возвращается на callback `apishka.ru`, а сайт создаёт локальную web-сессию.

`idshka.ru` не является бизнес-API `apishka.ru`; он является identity provider, issuer и control plane.

## Важное уточнение по Laravel + Socialite
Проект делается на **Laravel + Socialite**, но роли разделены:

- **Socialite внутри `idshka.ru`** используется для входа пользователей через внешние провайдеры: Google, VK, Yandex и другие OAuth-провайдеры.
- **`idshka.ru` как issuer/provider для `apishka.ru`** реализуется отдельными Laravel-модулями: OAuth/OIDC-like authorize endpoint, token endpoint, JWKS, userinfo, revoke, подпись JWT.
- **`apishka.ru` как Laravel web-сайт** может подключаться к `idshka.ru` через кастомный Socialite provider или через обычный OAuth/OIDC client flow.

Socialite — это клиентская сторона OAuth-login. Поэтому выпуск токенов, JWKS и проверка `aud/iss/exp/jti` не прячутся в Socialite, а описываются отдельными сервисами Laravel-приложения.

## Основные пользователи

### Владелец сайта
- Регистрируется на `idshka.ru`.
- Подключает домен `apishka.ru`.
- Подтверждает владение доменом.
- Создаёт настройки интеграции:
  - API audience, scopes, permissions;
  - web client credentials и redirect URI;
  - gateway/JWKS/introspection настройки.

### Конечный пользователь
- Заходит на `idshka.ru`.
- Может войти через email/password или Socialite-провайдеров.
- Создаёт токен для доступа к `apishka.ru` API, если сайт работает в API-only режиме.
- Или входит на `apishka.ru` через `idshka.ru`, если сайт работает как web-client.
- Видит свои токены, сессии, сайты, историю входов и может отзывать доступ.

### Подключённый сайт `apishka.ru`
- Принимает запросы только после проверки на edge-слое, если режим API-only.
- Получает в upstream нормализованный контекст пользователя: user id, email/username, roles, scopes, permissions, site id, token id/session id.
- В web-режиме создаёт собственную локальную сессию после callback от `idshka.ru`.

## MVP-возможности
- Laravel-приложение `idshka.ru`.
- Регистрация и вход пользователя на `idshka.ru`.
- Laravel Socialite login через Google/VK/Yandex-провайдеры.
- Подключение сайта с доменом `apishka.ru`.
- Верификация домена через DNS TXT и файл в `/.well-known/`.
- Два режима сайта: `api_resource` и `web_client`.
- Выпуск user API token для `aud=apishka.ru` / `site_id=<apishka>`.
- JWKS endpoint для локальной проверки подписи gateway-слоем.
- Optional introspection endpoint для online-проверки и hard revoke.
- Nginx/OpenResty gateway example для `apishka.ru`.
- Проброс `X-Idshka-*` headers и опционального подписанного `X-Idshka-Context`.
- Authorization Code + PKCE flow для web-входа на `apishka.ru`.
- Личный кабинет: сайты, клиенты, токены, revoke, audit.

## Не в MVP
- Маркетплейс приложений.
- SCIM/enterprise provisioning.
- Billing и тарифы.
- Сложный policy DSL / ABAC.
- Полноценная low-code настройка UI авторизации для каждого сайта.
- Поддержка всех OAuth grant types.

## Базовый технический стек
Подробно см. `.ai-factory/TECH_STACK.md`.

Коротко:
- Основной backend и portal: **Laravel**.
- Вход через внешние соцсети: **Laravel Socialite** + кастомные провайдеры при необходимости.
- UI: Blade + Vite + Tailwind, опционально Livewire/Alpine.
- DB: PostgreSQL через Eloquent migrations.
- Cache/queues/rate limit: Redis.
- JWT/JWKS issuer: Laravel service layer + PHP JWT/JWK library.
- JWT implementation library: `lcobucci/jwt` (`^5.6`).
- Gateway JWT verification: OpenResty Lua + explicit `openssl` runtime dependency in the gateway image for RS256 signature checks.

- Локальный запуск: Docker Compose.

## Архитектура
Подробные архитектурные правила, модульные границы и dependency rules описаны в `.ai-factory/ARCHITECTURE.md`.
Паттерн: `Modular Monolith`
