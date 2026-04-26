# 04-token-issuer-and-jwks

## Цель
Сделать выпуск short-lived JWT для API-only режима, публикацию JWKS и revoke/denylist по `jti` без смешивания с Socialite или web-client OAuth flow.

## Area
`issuer`, `security`, `laravel`

## Зависимости
- `03-site-registry-and-modes`

## Границы scope
- В scope: user API token для verified site с включенным `api_resource`, `GET /oauth/jwks.json`, signing key lifecycle, revoke прямого JWT.
- Не в scope: Authorization Code + PKCE, `POST /oauth/token`, `GET /oauth/userinfo`, id_token и refresh token. Это остается для `06-web-login-through-idshka`.

## Refined Checklist
- [x] **T1. JWT/JWK dependency, issuer config и contracts first**
  - Выбрать и зафиксировать JWT/JWK библиотеку в `composer.json` (`lcobucci/jwt` или JOSE/JWK framework).
  - Добавить issuer config для `iss`, TTL, allowed `alg`, key storage и JWKS cache policy.
  - Создать contracts/value objects в `app/Contracts/Auth/*`:
    - `JwtClaims` для `iss`, `aud`, `sub`, `site_id`, `token_type`, `scope`, `permissions`, `jti`, `iat`, `nbf`, `exp`;
    - `Scopes` / permissions constants без permissive defaults;
    - при необходимости `GatewayHeaders` для синхронизации с gateway contract.
  - Зафиксировать, что `kid` обязателен в JWT header.

- [x] **T2. Persistence schema и Eloquent models**
  - Добавить migrations и models для `signing_keys`, `api_tokens`, `revoked_jti`.
  - `signing_keys`: `kid`, `algorithm`, public key material, encrypted private key material, status (`active`/`next`/`retired`), activation/retire timestamps.
  - `api_tokens`: `user_id`, `site_id`, `audience`, `jti`, token metadata/hash, scopes, permissions, `expires_at`, `revoked_at`.
  - `revoked_jti`: `jti`, `api_token_id`, `user_id`, `site_id`, `audience`, `expires_at`, `revoked_at`.
  - Добавить индексы по `user_id`, `site_id`, `audience`, `jti`, `kid`, статусам и expiry.
  - Raw JWT не хранить; хранить только metadata/hash, если lookup нужен после issue.

- [x] **T3. SigningKeyService и JwksService**
  - Реализовать `SigningKeyService`: генерация/загрузка active key, prepared next key, fail-closed если active key отсутствует.
  - Private keys хранить зашифрованно, никогда не логировать и не отдавать наружу.
  - Реализовать `JwksService`, который возвращает только public key material active/prepared keys по `kid`.
  - Подготовить deterministic ошибки для missing/invalid key state без раскрытия секретов.
  - Зависит от: `T1`, `T2`.

- [x] **T4. Audience/scope/permissions resolver и eligibility guard**
  - Добавить минимальный `ApiResources` public contract/service для `audience`, allowed scopes и permissions API-only сайта.
  - Выпуск токена разрешать только когда:
    - пользователь владеет site;
    - site verified;
    - включен mode `api_resource`;
    - запрошенные scopes/permissions входят в allow-list.
  - Использовать `VerifiedSiteLookup` и не читать внутренности соседних модулей без public contract.
  - Отсутствующие scopes/permissions не должны расширять права.
  - Зависит от: `03-site-registry-and-modes`.

- [x] **T5. TokenIssuer и IssueUserApiTokenAction**
  - Реализовать `TokenIssuer` для user API tokens с `token_type=user_api`.
  - Claims: `iss`, `aud`, `sub`, `site_id`, `token_type`, `scope`, `permissions`, `jti`, `iat`, `nbf`, `exp`.
  - JWT header: `alg`, `kid`, `typ=JWT`.
  - Реализовать `IssueUserApiTokenAction`, который создает `api_tokens` metadata row и возвращает raw token только один раз.
  - Не логировать raw token, private key или claims с секретными значениями.
  - Зависит от: `T1`, `T2`, `T3`, `T4`.

- [x] **T6. `POST /api/v1/user/api-tokens`**
  - Добавить route/controller/FormRequest для выпуска user API token.
  - Использовать тонкий controller: validation -> action/service -> response.
  - Подключить явные guards/policies и `throttle:token-issue`.
  - Возвращать deterministic error body с `error`, `message`, `request_id`.
  - Покрыть fail-closed случаи: unauthenticated, чужой site, unverified site, site без `api_resource`, invalid scope.
  - Зависит от: `T5`.

- [x] **T7. RevocationService и `POST /api/v1/user/api-tokens/{id}/revoke`**
  - Реализовать owner-scoped revoke endpoint.
  - Revoke должен быть idempotent: повторный запрос не создает конфликт и не раскрывает лишние детали.
  - Обновлять `api_tokens.revoked_at` и добавлять `revoked_jti` до `expires_at`.
  - Redis denylist использовать как cache/ускоритель, если configured; БД остается source of truth.
  - Не логировать raw token.
  - Зависит от: `T2`, `T5`.

- [x] **T8. Public `GET /oauth/jwks.json` endpoint**
  - Реализовать `GET /oauth/jwks.json` через stateless/public middleware path.
  - Учесть, что текущий `routes/oauth.php` подключен через `web`: JWKS не должен выдавать session/CSRF cookies.
  - Ответ содержит только public JWK fields, `kid`, `alg`, `use`/`key_ops` при необходимости.
  - Добавить cache headers, которые совместимы с key rotation и не раскрывают private state.
  - Зависит от: `T3`.

- [x] **T9. Audit, logging, rate limits и docs sync**
  - Добавить events/listeners для token issue/revoke audit без секретов.
  - Добавить dedicated rate limiters для token issue/revoke; JWKS защищать cache headers и при необходимости lightweight throttle.
  - Логи должны содержать `request_id`, `user_id`, `site_id`, `aud`, `jti`, но не raw JWT/private key.
  - Обновить `docs/API_FLOWS.md`, `docs/GATEWAY_CONTRACT.md`, `docs/LARAVEL_MODULES.md` по фактическим endpoints, claims и errors.
  - Зависит от: `T6`, `T7`, `T8`.

- [x] **T10. Feature/unit verification**
  - Unit tests для claims building, signing key selection, JWKS shape и revoke idempotency.
  - Feature tests для:
    - successful issue для `aud=apishka.ru`;
    - JWKS active public key by `kid`;
    - one-time raw token response and no raw token persistence;
    - revoke writes DB denylist;
    - fail-closed auth/ownership/mode/scope cases;
    - no cookies on JWKS.
  - Запустить formatter и Laravel test suite; сохранить evidence в verification notes.
  - Зависит от: `T1`-`T9`.

## Acceptance criteria
- [ ] Можно выпустить user API token для verified owned site с mode `api_resource` и `aud=apishka.ru`.
- [ ] JWT содержит claims `iss`, `aud`, `sub`, `site_id`, `token_type=user_api`, `scope`, `permissions`, `jti`, `iat`, `nbf`, `exp`.
- [ ] JWT header содержит `kid`, допустимый `alg` и `typ=JWT`.
- [ ] JWKS содержит active public key по `kid` и не содержит private key material.
- [ ] Raw token показывается только один раз и не хранится/не логируется в сыром виде.
- [ ] Revoke idempotent, пишет `api_tokens.revoked_at` и `revoked_jti`; Redis denylist optional и не заменяет БД.
- [ ] Unverified site, чужой site, site без `api_resource` и invalid scopes fail closed.
- [ ] Token issue/revoke имеют audit events, rate limits и deterministic JSON errors.
- [ ] Docs и tests подтверждают внешний contract перед переходом к gateway plan `05`.

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
