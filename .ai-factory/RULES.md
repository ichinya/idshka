# RULES

## Иерархия правил
1. План-локальные правила: `.ai-factory/plans/<plan-id>/rules.md`
2. Area rules из `config.yaml -> rules.*`
3. Base rules: `.ai-factory/rules/base.md`
4. Global axioms: этот файл

Если правила конфликтуют, приоритет выше у более локального уровня.

## Глобальные аксиомы проекта
1. **idshka.ru — issuer/control plane, не бизнес-API подключённого сайта.**
2. **Подключённый сайт имеет явный режим:** `api_resource`, `web_client` или оба.
3. **API-only запросы проходят через edge.** Upstream не доверяет публичному JWT напрямую.
4. **Web login использует OIDC-подобный flow:** Authorization Code + PKCE, strict redirect URI, state, nonce.
5. **Fail closed.** Если gateway или issuer не уверен в подписи, audience, expiry, redirect URI или источнике заголовков — запрос отклоняется.
6. **Contracts first.** Claims, scopes, headers, context envelope и error body сначала фиксируются в `packages/contracts`.
7. **No implicit privilege.** Отсутствие поля, роль `user` или fallback не должны расширять права.
8. **Sanitize before trust.** Все `X-Idshka-*` от клиента удаляются, новые заголовки создаёт только gateway.
9. **Signed context when boundary is weak.** Если upstream не находится в надёжном private network, он обязан проверять `X-Idshka-Context-Signature`.
10. **Audit by default.** Site connect, domain verify, token issue/revoke, login, client secret rotation и policy changes попадают в аудит.
11. **Short-lived over clever revoke.** Для прямых JWT предпочитать короткий TTL; долгоживущие ключи позже делать opaque + exchange.
12. **Deterministic errors.** `401` — проблема аутентификации, `403` — токен валиден, но прав недостаточно.
13. **No raw secrets in logs.** Raw token, client_secret, private key, authorization code и refresh token никогда не логируются.

## Карта area rules
- `site_registry` — подключение доменов, site modes, verification.
- `issuer` — JWT, JWKS, OIDC token endpoint, keys, revoke.
- `gateway` — OpenResty/Nginx, локальная валидация, header/context injection.
- `api_resource` — правила для API-only consumer-сайта.
- `web_client` — OIDC login flow для web-сайта.
- `portal` — личный кабинет и UX self-service.
- `security` — криптография, секреты, threat model, rate limits.
- `ops` — observability, deployment, runbooks.

## Что считается завершением задачи
Задача считается завершённой только если:
- код/конфиг внесён;
- contracts обновлены при изменении внешней поверхности;
- docs/snippets обновлены при изменении пользовательского flow;
- есть evidence: тест, curl, лог, screenshot или краткий proof note;
- roadmap/plan status можно обновить без догадок.
