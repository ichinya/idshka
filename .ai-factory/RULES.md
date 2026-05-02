# RULES

## Глобальные правила проекта
1. **Laravel-first.** Основной продукт `idshka.ru` — Laravel-приложение. Не добавлять отдельный Node/Fastify/Next backend без явного решения.
2. **Socialite — для внешнего входа.** Laravel Socialite используется для входа пользователей на `idshka.ru` через Google/VK/Yandex и другие провайдеры. Socialite не считается issuer/token server.
3. **Issuer — отдельный Laravel domain.** JWT, JWKS, authorization code, token endpoint, revoke и introspection реализуются отдельными сервисами и контроллерами Laravel.
4. **Подключённый сайт — внешний consumer.** `example.test` не является частью `idshka.ru`, в репозитории допустимы только examples/adapters.
5. **Site mode явный:** `api_resource`, `web_client` или оба.
6. **API-only запросы проходят через edge.** Upstream не доверяет публичному JWT напрямую.
7. **Web login использует Authorization Code + PKCE:** strict redirect URI, state, nonce.
8. **Fail closed.** Если gateway или issuer не уверен в подписи, audience, expiry, redirect URI или источнике заголовков — запрос отклоняется.
9. **Contracts first.** Claims, scopes, headers, context envelope и error body сначала фиксируются в Laravel contracts/classes и docs.
10. **No implicit privilege.** Отсутствие поля, роль `user` или fallback не должны расширять права.
11. **Sanitize before trust.** Все `X-Idshka-*` от клиента удаляются, новые заголовки создаёт только gateway.
12. **Signed context when boundary is weak.** Если upstream не находится в надёжном private network, он обязан проверять `X-Idshka-Context-Signature`.
13. **Audit by default.** Site connect, domain verify, token issue/revoke, login, client secret rotation и policy changes попадают в аудит.
14. **Short-lived over clever revoke.** Для прямых JWT предпочитать короткий TTL; долгоживущие ключи позже делать opaque + exchange.
15. **Deterministic errors.** `401` — проблема аутентификации, `403` — токен валиден, но прав недостаточно.
16. **No raw secrets in logs.** Raw token, client_secret, private key, authorization code, Socialite access token и refresh token никогда не логируются.
17. **PHP 8.5.** Проект использует PHP 8.5; новые runtime/config/dependency assumptions должны исходить из PHP 8.5.

## Карта area rules
- `laravel` — структура Laravel, сервисы, migrations, tests.
- `socialite` — external providers, account linking, callback safety.
- `site_registry` — подключение доменов, site modes, verification.
- `issuer` — JWT, JWKS, token endpoint, keys, revoke.
- `gateway` — OpenResty/Nginx, локальная валидация, header/context injection.
- `api_resource` — правила для API-only consumer-сайта.
- `web_client` — login flow для web-сайта через `idshka.ru`.
- `portal` — личный кабинет и UX self-service.
- `security` — криптография, секреты, threat model, rate limits.
- `ops` — observability, deployment, runbooks.

## Что считается завершением задачи
Задача считается завершённой только если:
- код/конфиг внесён;
- contracts/docs обновлены при изменении внешней поверхности;
- snippets обновлены при изменении пользовательского flow;
- есть evidence: тест, curl, лог, screenshot или краткий proof note;
- roadmap/plan status можно обновить без догадок.
