[← Previous Page](API_FLOWS.md) · [Back to README](../README.md) · [Next Page →](SOCIALITE.md)

# GATEWAY_CONTRACT

> Статус: gateway contract + implemented reference runtime. В репозитории есть OpenResty gateway, который проверяет user API JWT по Laravel JWKS, затирает входящие `X-Idshka-*` и прокидывает trusted context в минимальный `demo-resource-api` upstream.

## Назначение
Документ фиксирует контракт между gateway `api.example.test` и upstream API.

## Текущий статус в репозитории

- Есть OpenResty gateway reference в `infra/openresty/demo-resource/`.
- `routes/oauth.php` публикует public `GET /oauth/jwks.json`.
- `examples/demo-resource-api/nginx.conf` предоставляет минимальный upstream для smoke-проверки trusted headers.
- Gateway validation покрыт `infra/openresty/demo-resource/smoke.sh`.
- Edge revoke cache, online introspection и signed context hardening остаются будущими phases.

## Вход от клиента
Клиент передаёт:

```http
Authorization: Bearer <idshka_jwt>
```

Любые входящие `X-Idshka-*` от клиента считаются недоверенными и удаляются.

## Проверки gateway
1. Header `Authorization` существует и имеет Bearer token.
2. JWT header содержит допустимый `alg` и известный `kid`.
3. Подпись валидна по JWKS; в Docker gateway читает `http://nginx/oauth/jwks.json`, публичный URL остается `https://idshka.ru/oauth/jwks.json`.
4. `iss` = configured issuer; в локальном Docker default `http://localhost:8080`.
5. `aud` соответствует подключённому API resource; для reference smoke это `example.test`.
6. `exp` не истёк, `nbf` наступил.
7. Будущий edge revoke cache / online introspection дополнительно проверит denylist по `jti`; текущий reference gateway только требует наличие `jti` claim и прокидывает его upstream.
8. Token type подходит: `token_type=user_api`.

## JWKS cache and stale-key policy

Reference gateway uses `GATEWAY_JWKS_CACHE_SECONDS` to bound public-key cache lifetime. Known cached keys are trusted only until their explicit `expires_at`; after that the cache entry is deleted before validation and the gateway refreshes JWKS. Unknown `kid` always triggers JWKS refresh. JWKS fetch, non-200 response or decode failure fail closed with `502 jwks_unavailable`; if refresh succeeds but the `kid` is absent, gateway returns `401 invalid_token`.

Gateway logs use `request_id`, `kid`, `cache_outcome` and fail-closed reason only. Raw JWTs and JWK private material are never logged.

## Headers в upstream
Reference gateway сейчас выставляет:

```http
X-Idshka-Authenticated: 1
X-Idshka-User-Id: <sub>
X-Idshka-Site-Id: <site_id>
X-Idshka-Audience: <aud>
X-Idshka-Scopes: orders.read orders.write
X-Idshka-Permissions: orders.read,orders.write
X-Idshka-JTI: <jti>
X-Idshka-Token-Exp: <unix_ts>
X-Request-Id: <request_id>
```

Future optional headers, если issuer начнёт выпускать соответствующие claims и gateway добавит smoke coverage:

```http
X-Idshka-Email: <email_if_allowed>
X-Idshka-Roles: role1,role2
```

## Signed context режим
Если upstream не полностью доверяет сети до gateway, gateway добавляет:

```http
X-Idshka-Context: <base64url-json>
X-Idshka-Context-Signature: v1=<hex-hmac-sha256>
X-Idshka-Context-Timestamp: <unix_ts>
```

Signature input:

```text
v1.<timestamp>.<context_base64url>
```

HMAC key хранится на `example.test` gateway и upstream стороне, не на клиенте.

## Ошибки
- `401 missing_token`
- `401 invalid_token`
- `401 expired_token`
- `401 audience_mismatch`
- `403 insufficient_scope`
- `502 jwks_unavailable` только если политика не позволяет использовать stale valid key

Gateway должен возвращать errors без раскрытия sensitive details.

Текущий reference gateway возвращает deterministic JSON:

```json
{
  "error": "invalid_token",
  "message": "JWT signature is invalid.",
  "request_id": "<nginx_request_id>"
}
```

Raw JWT не прокидывается upstream: gateway очищает `Authorization` перед proxying.

## Incident linkage

Gateway header trust failures, unknown `kid` incidents, leaked signing key response and raw JWT evidence handling are covered in [Security Runbook](SECURITY_RUNBOOK.md). Gateway evidence should use `request_id`, `site_id`, `jti`, `kid`, upstream status and smoke case names; raw JWTs and private key material must not be captured.

## Local smoke

```bash
docker compose --profile examples up -d --build demo-resource-gateway
bash infra/openresty/demo-resource/smoke.sh
```

Smoke подготавливает миграции, выпускает local-only JWT через `php artisan idshka:gateway-smoke-token`, проверяет valid token, missing token, invalid signature, wrong `aud`, expired/not-before token и замену spoofed `X-Idshka-*`.

## See Also

- [API Flows](API_FLOWS.md) — где этот contract уже отделён от текущих реализованных endpoints
- [Socialite](SOCIALITE.md) — граница между external login и будущим issuer layer
- [Laravel Modules](LARAVEL_MODULES.md) — модули, которые позже будут обслуживать этот contract
- [Security Runbook](SECURITY_RUNBOOK.md) — incident response for gateway trust and signing key failures

## Update: JWKS runtime endpoint (2026-04-23)

В Laravel приложении опубликован публичный endpoint:

- `GET https://idshka.ru/oauth/jwks.json`

Текущее поведение:

- Отдает только public JWK (`kty`, `kid`, `alg`, `use`, `n`, `e`).
- Возвращает active key и prepared next key (если есть).
- Не отдает private key material.
- Работает через stateless `api` middleware и не должен выставлять session/CSRF cookies.
- Добавляет cache headers (`Cache-Control`) совместимые с key rotation.
