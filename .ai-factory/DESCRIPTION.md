# DESCRIPTION

## Что строим
`idshka.ru` — сервис идентификации, выпуска токенов и управления подключёнными сайтами.

Пользователь подключает свой сайт, например `apishka.ru`, и выбирает один или оба режима:

1. **API-only / resource API**  
   `apishka.ru` работает только как API. Для запроса нужен токен, выпущенный на `idshka.ru`. На входе в `apishka.ru` стоит Nginx/OpenResty gateway: он проверяет токен, удаляет поддельные auth-заголовки и добавляет в upstream подтверждённый auth-context пользователя.

2. **Web login / OIDC client**  
   `apishka.ru` имеет web-интерфейс и полноценный вход через `idshka.ru`: пользователь нажимает «Войти через idshka», уходит на `idshka.ru`, после входа возвращается на callback `apishka.ru`, а сайт создаёт локальную web-сессию.

`idshka.ru` не является бизнес-API `apishka.ru`; он является control plane / identity provider / issuer.

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
- Создаёт токен для доступа к `apishka.ru` API, если сайт работает в API-only режиме.
- Или входит на `apishka.ru` через `idshka.ru`, если сайт работает как web-client.
- Видит свои токены, сессии, сайты, историю входов и может отзывать доступ.

### Подключённый сайт `apishka.ru`
- Принимает запросы только после проверки на edge-слое.
- Получает в upstream уже нормализованный контекст пользователя:
  - user id;
  - email/username, если разрешено;
  - roles;
  - scopes;
  - permissions;
  - site id;
  - token id / session id.

## MVP-возможности
- Регистрация и вход пользователя на `idshka.ru`.
- Подключение сайта с доменом `apishka.ru`.
- Верификация домена через DNS TXT и файл в `/.well-known/`.
- Два режима сайта: `api_resource` и `web_client`.
- Выпуск user API token для `aud=apishka.ru` / `site_id=<apishka>`.
- JWKS endpoint для локальной проверки подписи gateway-слоем.
- Optional introspection endpoint для online-проверки и hard revoke.
- Nginx/OpenResty gateway example для `apishka.ru`.
- Проброс `X-Idshka-*` headers и опционального подписанного `X-Idshka-Context`.
- OIDC Authorization Code + PKCE flow для web-входа на `apishka.ru`.
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
- TypeScript monorepo.
- `idshka-api`: Node.js + Fastify + PostgreSQL + Redis.
- `idshka-portal`: Next.js + React + Tailwind.
- Edge для `apishka.ru`: OpenResty/Nginx + Lua.
- JWT/JWKS: `jose` на issuer-стороне, Lua JWT/JWKS validation на gateway.
- Контракты: OpenAPI + TypeBox/Zod schemas.
- Локальный запуск: Docker Compose.

## Главный принцип
`apishka.ru` не должен сам «угадывать», кто пользователь, по сырому входящему трафику. Сначала идёт проверка через `idshka.ru`-совместимый edge/gateway, потом upstream получает доверенный контекст.
