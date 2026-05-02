# Verify

- [x] OpenResty gateway runtime has explicit JWT/JWKS Lua dependencies or vendored modules; implementation does not depend on accidental image contents.
- [x] Gateway config has explicit `issuer`, `audience=example.test`, internal `jwks_url`, allowed `alg`, JWKS TTL and stale/fail-closed policy.
- [x] `/healthz` remains public and returns `200` without auth.
- [x] Protected gateway route rejects missing or malformed Bearer token with deterministic `401` JSON containing `error`, `message`, `request_id`.
- [x] Valid user API token for `aud=example.test` reaches upstream through gateway.
- [x] Invalid signature returns `401 invalid_token`.
- [x] Unknown `kid` or unsupported `alg` returns `401 invalid_token`.
- [x] Wrong `aud` returns `401 audience_mismatch`.
- [x] Wrong `iss`, missing critical claim, unsupported `token_type`, expired token or not-yet-valid token fail closed.
- [x] JWKS cache reads public keys from `GET /oauth/jwks.json`, selects by `kid`, and never exposes private key material.
- [x] Client-supplied `X-Idshka-*` headers are removed before proxying.
- [x] Upstream receives gateway-generated `X-Idshka-Authenticated`, `X-Idshka-User-Id`, `X-Idshka-Site-Id`, `X-Idshka-Audience`, `X-Idshka-Scopes`, `X-Idshka-Permissions`, `X-Idshka-JTI`, `X-Idshka-Token-Exp`.
- [x] Raw JWT is not present in upstream response, gateway error body, or logs produced by smoke flow.
- [x] Optional signed context is either fully config-gated and smoke-tested, or explicitly documented as deferred.
- [x] Minimal `demo-resource-api` smoke upstream exists and can prove header sanitization/replacement.
- [x] Gateway smoke commands cover valid token, missing token, invalid signature, wrong audience, expired/not-before token, and header sanitization.
- [x] `docker compose config` passes.
- [x] `docker compose up -d --build` plus gateway smoke passes, or verification notes explain the exact blocker.
- [x] Existing Laravel verification still passes: `php vendor/bin/pint --test` and `php artisan test --without-tty`, or notes explain blocked commands.
- [x] `docs/API_FLOWS.md`, `docs/GATEWAY_CONTRACT.md`, `docs/README.md`, and `infra/openresty/demo-resource/lua/README.md` match implemented gateway behavior.

## Evidence

- 2026-04-26 fresh verification: `php vendor/bin/pint --test` passed.
- 2026-04-26 fresh verification: `composer validate --strict` passed.
- 2026-04-26 fresh verification: `docker compose config` passed.
- 2026-04-26 fresh verification: `git diff --check` passed with only CRLF normalization warnings in existing markdown files.
- 2026-04-26 fresh verification: changed-file unfinished marker scan returned no matches.
- 2026-04-26 fresh verification: `php artisan test --without-tty` passed: 43 tests, 324 assertions.
- 2026-04-26 fresh verification: `npm run build` passed.
- 2026-04-26 fresh verification: `docker compose -p idshka_aif_verify up -d --build` passed.
- 2026-04-26 fresh verification: `bash infra/openresty/demo-resource/smoke.sh "docker compose -p idshka_aif_verify"` passed in a fresh Compose project.
- 2026-04-26 cleanup: `docker compose -p idshka_aif_verify down -v` removed the verification stack and volumes.
