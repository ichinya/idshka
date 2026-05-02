# Context

Plan: `06-web-login-through-idshka`

Project: `idshka.ru` Laravel + Socialite identity/control plane.

Goal: Реализовать web login: example.test входит через idshka.ru по Authorization Code + PKCE.

Refined on: `2026-04-26`

Branch: `feature/06-web-login-through-idshka`

Current repo state:
- `routes/oauth.php` has only `GET /oauth/jwks.json`.
- `app/Domain/OidcClients` is still a skeleton.
- `app/Domain/Issuer` already has signing keys, JWKS, user API token issue/revoke and audit hooks.
- `app/Domain/Sites` already has verified sites and explicit `web_client` mode.
- `examples/laravel-web-client` is a README placeholder.
- Plan 04 finalization has unrelated dirty working-tree artifacts; do not modify them while implementing plan 06 unless explicitly requested.

Relevant docs:
- `.ai-factory/TECH_STACK.md`
- `.ai-factory/ARCHITECTURE.md`
- `.ai-factory/RULES.md`
- `docs/API_FLOWS.md`
- `docs/GATEWAY_CONTRACT.md`
- `docs/LARAVEL_MODULES.md`
- `docs/SOCIALITE.md`

Relevant implemented specs:
- `.ai-factory/specs/03-site-registry-and-modes/`
- `.ai-factory/specs/04-token-issuer-and-jwks/`
- `.ai-factory/specs/05-api-resource-gateway-for-demo-resource/`

Integration boundary:
- `idshka.ru` owns OAuth/OIDC-like provider endpoints and signing.
- `laravel-web-client` is an external consumer example and must use HTTP endpoints only.
- Socialite remains only for external login into `idshka.ru`; do not place issuer/provider logic in Socialite callbacks.
