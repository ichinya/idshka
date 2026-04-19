# Area: issuer

## Scope
- JWT issuance.
- JWKS publication.
- OIDC authorize/token/userinfo endpoints.
- Key rotation.
- Revoke/denylist.

## Rules
- Только `idshka-api` подписывает токены.
- JWT header обязан иметь `kid` и approved `alg`.
- API-only token обязан иметь `iss`, `aud`, `sub`, `site_id`, `jti`, `iat`, `nbf`, `exp`, `scope`.
- Web `id_token` обязан иметь `iss`, `aud`, `sub`, `iat`, `exp`, `nonce`.
- `aud` всегда проверяется по mode: API audience для `api_resource`, `client_id` для `web_client`.
- Private keys не сериализуются в логи/ответы API.
- Revoked `jti` пишется в PostgreSQL и по возможности в Redis.
- Raw token показывается только один раз.

## Error semantics
- Invalid/missing token: `401`.
- Valid token but insufficient scope: `403`.
- Unknown client/site: `401` для auth flow, `404` только в owner UI.
