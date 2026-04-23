[← Previous Page](API_FLOWS.md) · [Back to README](../README.md) · [Next Page →](SOCIALITE.md)

# GATEWAY_CONTRACT

> Статус: целевой контракт, а не уже поднятый runtime API. В репозитории есть OpenResty skeleton и документированные правила, но issuer/JWKS endpoints ещё не реализованы.

## Назначение
Документ фиксирует контракт между gateway `api.apishka.ru` и upstream API.

## Текущий статус в репозитории

- Есть infra skeleton в `infra/openresty/apishka/`.
- `routes/oauth.php` пока не публикует реальные provider endpoints.
- JWKS, token issue и revoke/introspection — это следующие implementation phases.

## Вход от клиента
Клиент передаёт:

```http
Authorization: Bearer <idshka_jwt>
```

Любые входящие `X-Idshka-*` от клиента считаются недоверенными и удаляются.

## Проверки gateway
1. Header `Authorization` существует и имеет Bearer token.
2. JWT header содержит допустимый `alg` и известный `kid`.
3. Подпись валидна по `https://idshka.ru/oauth/jwks.json`.
4. `iss` = `https://idshka.ru`.
5. `aud` соответствует подключённому API resource.
6. `exp` не истёк, `nbf` наступил.
7. `jti` не находится в denylist, если revoke cache включён.
8. Token type подходит: `token_type=user_api`.

## Headers в upstream
Gateway выставляет:

```http
X-Idshka-Authenticated: 1
X-Idshka-User-Id: <sub>
X-Idshka-Site-Id: <site_id>
X-Idshka-Audience: <aud>
X-Idshka-Email: <email_if_allowed>
X-Idshka-Roles: role1,role2
X-Idshka-Scopes: orders.read orders.write
X-Idshka-Permissions: orders.read,orders.write
X-Idshka-JTI: <jti>
X-Idshka-Token-Exp: <unix_ts>
X-Request-Id: <request_id>
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

HMAC key хранится на `apishka.ru` gateway и upstream стороне, не на клиенте.

## Ошибки
- `401 missing_token`
- `401 invalid_token`
- `401 expired_token`
- `401 audience_mismatch`
- `403 insufficient_scope`
- `502 jwks_unavailable` только если политика не позволяет использовать stale valid key

Gateway должен возвращать errors без раскрытия sensitive details.

## See Also

- [API Flows](API_FLOWS.md) — где этот contract уже отделён от текущих реализованных endpoints
- [Socialite](SOCIALITE.md) — граница между external login и будущим issuer layer
- [Laravel Modules](LARAVEL_MODULES.md) — модули, которые позже будут обслуживать этот contract
