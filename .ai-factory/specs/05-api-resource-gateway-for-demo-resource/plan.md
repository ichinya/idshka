# 05-api-resource-gateway-for-demo-resource

## Цель
Собрать OpenResty gateway example для `api.example.test`, который локально проверяет user API JWT от `idshka.ru`, fail-closed отклоняет недоверенные запросы, затирает входящие `X-Idshka-*` и прокидывает в upstream только подтвержденный gateway context.

## Area
`gateway`, `api_resource`, `security`, `ops`

## Зависимости
- `04-token-issuer-and-jwks` должен быть завершен: доступен выпуск user API token для `aud=example.test` и public `GET /oauth/jwks.json`.
- `compose.yml` уже содержит service `gateway` на OpenResty и Laravel/nginx service, через который gateway может читать JWKS во внутренней Docker network.

## Границы scope
- В scope:
  - OpenResty/Nginx config для gateway reference.
  - Lua validation path: Bearer token parsing, JWKS cache, JWT signature/claims validation, trusted header injection.
  - Минимальный upstream echo/consumer для smoke-проверки gateway headers.
  - Curl/smoke сценарии для valid token, invalid signature, invalid audience, expired/not-before cases и header sanitization.
  - CI smoke gate для gateway flow, если runtime стабилен в Docker Compose.
- Не в scope:
  - OAuth Authorization Code + PKCE flow, `/oauth/token`, `/oauth/userinfo`.
  - Online introspection endpoint и hard realtime revoke на gateway.
  - Полноценный product-grade API `example.test`.
  - Portal UI для настройки gateway.

## Refined Checklist
- [x] **T1. Gateway runtime dependencies и config surface**
  - Зафиксировать, как OpenResty получает Lua dependencies для JWT/JWKS verification: image build, vendored Lua modules или pinned install step.
  - Не полагаться на неявно доступные Lua-библиотеки внутри `openresty/openresty:*`.
  - Добавить gateway config values через env/defaults или nginx `set`:
    - `issuer` для `iss`;
    - `audience=example.test`;
    - internal `jwks_url` для Docker Compose;
    - allowed `alg` (`RS256`);
    - JWKS cache TTL и fail-closed stale policy;
    - optional signed-context secret только если включается `context_sign.lua`.
  - Обновить `.env.example`/README, если для локального gateway smoke нужны новые переменные.

- [x] **T2. OpenResty routing skeleton и upstream boundary**
  - Заменить текущий `501 gateway_not_implemented` в `infra/openresty/demo-resource/nginx.conf` на proxy flow через `access_by_lua*`.
  - Сохранить `/healthz` без auth и с security headers.
  - Настроить upstream для минимального `demo-resource-api` consumer/echo service.
  - Upstream не должен быть доступен как trusted boundary в обход gateway в documented local flow.
  - Все gateway errors возвращать детерминированным JSON body: `error`, `message`, `request_id`.

- [x] **T3. `jwks_cache.lua`**
  - Реализовать загрузку JWKS с `GET /oauth/jwks.json` через internal Docker URL.
  - Кешировать JWKS в `lua_shared_dict` с TTL, совместимым с `config/issuer.php` `jwks.cache_seconds`.
  - Выбирать key по `kid`; unknown `kid` должен fail closed.
  - Private key material никогда не логировать и не прокидывать.
  - Зафиксировать поведение при JWKS outage: `502 jwks_unavailable`, если usable cached key отсутствует.

- [x] **T4. `jwt_validate.lua`**
  - Парсить только `Authorization: Bearer <token>`; missing/malformed token возвращает `401 missing_token` или `401 invalid_token`.
  - Проверять header: allowed `alg`, обязательный `kid`, `typ=JWT` при наличии.
  - Проверять подпись по public JWK.
  - Проверять claims:
    - `iss`;
    - `aud=example.test`;
    - `sub`;
    - `site_id`;
    - `token_type=user_api`;
    - `scope`;
    - `permissions`;
    - `jti`;
    - `iat`, `nbf`, `exp`.
  - `exp`/`nbf` учитывать с минимальным clock skew, если он явно задан.
  - Неверная подпись, unknown `kid`, wrong `iss`, wrong `aud`, missing critical claims и unsupported `token_type` должны fail closed.
  - Raw JWT не писать в logs и не прокидывать upstream.

- [x] **T5. `context_headers.lua`**
  - До proxying удалить все входящие `X-Idshka-*`.
  - После успешной validation выставить trusted headers:
    - `X-Idshka-Authenticated: 1`;
    - `X-Idshka-User-Id`;
    - `X-Idshka-Site-Id`;
    - `X-Idshka-Audience`;
    - `X-Idshka-Scopes`;
    - `X-Idshka-Permissions`;
    - `X-Idshka-JTI`;
    - `X-Idshka-Token-Exp`;
    - `X-Request-Id`.
  - Нормализовать `scope` как space-separated string, `permissions` как comma-separated string или documented JSON-safe representation.
  - Не создавать permissive defaults при отсутствующих scopes/permissions.

- [x] **T6. Optional `context_sign.lua` как config-gated extension**
  - Если signed context включен, формировать `X-Idshka-Context`, `X-Idshka-Context-Signature`, `X-Idshka-Context-Timestamp`.
  - Signature input должен соответствовать `docs/GATEWAY_CONTRACT.md`: `v1.<timestamp>.<context_base64url>`.
  - HMAC secret не должен иметь insecure default для production.
  - Если feature выключена, план/код должен явно оставить signed context deferred, без полуактивного поведения.

- [x] **T7. Minimal `demo-resource-api` smoke upstream**
  - Добавить минимальный upstream consumer/echo endpoint в `examples/demo-resource-api` или отдельный lightweight service в Compose.
  - Endpoint должен возвращать только received trusted context для smoke-тестов, без raw token.
  - Smoke должен доказывать, что client-supplied `X-Idshka-*` были затерты и заменены gateway-generated values.

- [x] **T8. Gateway smoke scripts и CI integration**
  - Добавить воспроизводимый способ подготовить local valid token:
    - создать owner/site/mode fixture;
    - создать signing key;
    - выпустить user API token для `aud=example.test`.
  - Добавить smoke cases:
    - valid token проходит до upstream;
    - missing token дает `401 missing_token`;
    - invalid signature дает `401 invalid_token`;
    - wrong `aud` дает `401 audience_mismatch`;
    - expired/not-before token дает `401 expired_token` или `401 invalid_token`;
    - входящие `X-Idshka-*` не доходят до upstream;
    - upstream получает `X-Idshka-User-Id`, `X-Idshka-Scopes`, `X-Idshka-Permissions`.
  - Добавить CI step для gateway smoke или явно зафиксировать blocked reason, если OpenResty JWT dependencies нельзя стабильно поднять в текущем image.
  - Проверить `docker compose config`, `docker compose up -d --build`, gateway smoke commands и существующие Laravel tests.

- [x] **T9. Docs sync и implementation notes**
  - Обновить `docs/GATEWAY_CONTRACT.md`, `docs/API_FLOWS.md`, `docs/README.md` по фактическим gateway endpoints, errors, headers и local smoke flow.
  - Обновить `infra/openresty/demo-resource/lua/README.md` с назначением модулей и security assumptions.
  - Если часть `08-security-hardening-and-ops` остается deferred (`revoke cache`, advanced stale-key policy, metrics), явно сослаться на будущий план.

## Acceptance criteria
- [x] Valid user API token для `aud=example.test` проходит через gateway до upstream.
- [x] Missing/malformed token возвращает deterministic `401` JSON с `request_id`.
- [x] Неверная подпись, unknown `kid` или unsupported `alg` возвращают `401 invalid_token`.
- [x] Неверный `aud` возвращает `401 audience_mismatch`.
- [x] Expired или not-yet-valid token fail closed.
- [x] Входящие `X-Idshka-*` от клиента удаляются до proxying.
- [x] Upstream получает gateway-generated `X-Idshka-User-Id`, `X-Idshka-Site-Id`, `X-Idshka-Audience`, `X-Idshka-Scopes`, `X-Idshka-Permissions`, `X-Idshka-JTI`.
- [x] Raw JWT не появляется в upstream response, бизнес-логах или gateway error body.
- [x] JWKS cache выбирает public key по `kid` и не раскрывает private key material.
- [x] `docker compose` local flow и/или CI gateway smoke подтверждают внешний contract.

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`: это следующий шаг после `04-token-issuer-and-jwks` перед `06-web-login-through-idshka`.
