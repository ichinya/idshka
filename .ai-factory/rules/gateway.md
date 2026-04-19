# Area: gateway

## Scope
- OpenResty/Nginx integration.
- Local JWT validation via JWKS.
- Optional introspection.
- Header sanitation and context injection.

## Rules
- Gateway fail closed.
- Перед injection удалить все входящие `X-Idshka-*` headers.
- Проверять `alg`, `kid`, подпись, `iss`, `aud`, `exp`, `nbf`.
- Проверять `jti` denylist, если включён revoke cache.
- JWKS cache должен иметь TTL и поведение при key miss.
- Upstream должен быть недоступен напрямую из публичной сети.
- Если upstream не в private boundary, добавить и проверять signed context.
- Gateway logs не должны содержать полный JWT.

## Outputs
- `X-Idshka-Authenticated: 1`.
- `X-Idshka-User-Id`.
- `X-Idshka-Site-Id`.
- `X-Idshka-Audience`.
- `X-Idshka-Scopes`.
- `X-Idshka-Permissions`.
- `X-Idshka-JTI`.
- Optional `X-Idshka-Context` and signature.
