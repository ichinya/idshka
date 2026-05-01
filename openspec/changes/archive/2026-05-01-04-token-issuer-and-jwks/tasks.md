# Tasks

## Migrated legacy tasks

# Task

1. T1: JWT/JWK dependency, issuer config и contracts first (`JwtClaims`, `Scopes`, required `kid`).
2. T2: Migrations/models для `signing_keys`, `api_tokens`, `revoked_jti` с индексами и без raw token storage.
3. T3: `SigningKeyService` и `JwksService` с encrypted private keys и public-only JWKS.
4. T4: `ApiResources` audience/scope/permissions resolver и eligibility guard для verified owned `api_resource` site.
5. T5: `TokenIssuer` и `IssueUserApiTokenAction` для `token_type=user_api` JWT.
6. T6: `POST /api/v1/user/api-tokens` с FormRequest, guards/policies, `throttle:token-issue` и deterministic errors.
7. T7: `RevocationService` и `POST /api/v1/user/api-tokens/{id}/revoke` с idempotent revoke и DB denylist.
8. T8: Public stateless `GET /oauth/jwks.json` без session/CSRF cookies и без private key material.
9. T9: Audit events, logging/rate-limit hardening и sync docs (`API_FLOWS`, `GATEWAY_CONTRACT`, `LARAVEL_MODULES`).
10. T10: Feature/unit verification for issue, JWKS, one-time raw token, revoke, fail-closed cases, and no cookies on JWKS.

## Dependencies
- `T3` depends on `T1`, `T2`
- `T4` depends on `03-site-registry-and-modes`
- `T5` depends on `T1`, `T2`, `T3`, `T4`
- `T6` depends on `T5`
- `T7` depends on `T2`, `T5`
- `T8` depends on `T3`
- `T9` depends on `T6`, `T7`, `T8`
- `T10` depends on `T1`, `T2`, `T3`, `T4`, `T5`, `T6`, `T7`, `T8`, `T9`
