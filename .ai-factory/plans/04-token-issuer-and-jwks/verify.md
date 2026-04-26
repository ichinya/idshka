# Verify

- [ ] `composer.json` contains the chosen JWT/JWK dependency and issuer config is documented.
- [ ] Contracts exist for JWT claims/scopes and enforce required `kid`, `token_type=user_api`, and no permissive defaults.
- [ ] Migrations/models exist for `signing_keys`, `api_tokens`, `revoked_jti` with indexes on `user_id`, `site_id`, `audience`, `jti`, `kid`, and expiry/status fields.
- [ ] Private signing key material is encrypted at rest and never appears in API responses or logs.
- [ ] Можно выпустить token для verified owned `api_resource` site with `aud=apishka.ru`.
- [ ] JWT contains `iss`, `aud`, `sub`, `site_id`, `token_type`, `scope`, `permissions`, `jti`, `iat`, `nbf`, `exp`.
- [ ] JWT header contains `kid`, allowed `alg`, and `typ=JWT`.
- [ ] `GET /oauth/jwks.json` returns active public key by `kid`, has no private key material, and does not set session/CSRF cookies.
- [ ] Raw token is returned once on issue and is not stored/logged in raw form.
- [ ] Revoke is idempotent and writes both `api_tokens.revoked_at` and `revoked_jti` until token expiry.
- [ ] Unauthenticated, cross-owner, unverified site, missing `api_resource`, and invalid scope cases fail closed with deterministic JSON errors.
- [ ] Token issue/revoke emit audit events without secrets and use dedicated rate limits.
- [ ] `docs/API_FLOWS.md`, `docs/GATEWAY_CONTRACT.md`, and `docs/LARAVEL_MODULES.md` reflect the implemented issuer/JWKS behavior.
- [ ] Unit tests cover claims building, key selection/JWKS shape, and revoke behavior.
- [ ] Feature tests cover issue, JWKS, one-time raw token, revoke, fail-closed cases, and no cookies on JWKS.
- [ ] `php vendor/bin/pint --test` and `php artisan test` pass, or verification notes explain any blocked command.
