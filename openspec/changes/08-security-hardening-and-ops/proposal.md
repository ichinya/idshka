# Proposal: Security Hardening and Operations

## Why

Bring the implemented identity issuer, OAuth/OIDC web login, portal credential management, and OpenResty gateway from MVP behavior to a production-oriented security and operations baseline.

The current code already has request ids, several endpoint-specific rate limiters, non-secret audit events, key generation, JWKS publication, gateway smoke coverage, and CI tests. The remaining work is to make the hardening explicit, configurable, verified, and documented end to end before production use.

## What Changes

- Add configurable, fail-closed rate limits for auth, Socialite, site registry, OAuth/OIDC, token issue/revoke, portal credential, redirect URI, and verification flows.
- Add deterministic throttling behavior for both JSON endpoints and browser portal redirects, with no state mutation after the limit is exceeded.
- Ensure Laravel and gateway logs include `request_id` plus non-secret identifiers while excluding raw tokens, client secrets, authorization codes, PKCE verifiers, Socialite provider tokens, and private key material.
- Add a safe logging context/redaction pattern so new auth, issuer, portal, console, and gateway code does not reintroduce ad hoc secret handling.
- Add executable issuer signing-key rotation commands for prepare, activate, retire, force-retire, rollback, and status inspection, including handling for non-expiring API tokens.
- Document backup/restore expectations for users, sites, modes, API token metadata, OIDC client metadata, redirect URIs, audit events, revoke state, and signing key material.
- Document security runbooks for leaked API tokens, client secrets, authorization codes, Socialite tokens, signing keys, and gateway header trust failures.
- Strengthen CI security gates around tests, frontend build, Docker Compose validation, gateway smoke, dependency audits, and hardening-specific smoke checks.
- Define and verify gateway stale-key behavior for JWKS refresh failures and unknown `kid` values.
- Out of scope: refresh tokens, full production deployment automation, external SIEM integration, Prometheus/OpenTelemetry metrics, and signed gateway context implementation beyond documentation and policy.

## Approach

Use the existing Laravel modular-monolith boundaries:

- Keep transport wiring in routes, middleware, FormRequest classes, and thin controllers.
- Keep issuer key lifecycle logic in `app/Domain/Issuer` services and expose operations through Artisan commands.
- Keep gateway JWT/JWKS behavior in `infra/openresty/demo-resource/lua/*` and its executable smoke script.
- Keep external behavior synchronized through OpenSpec, `docs/API_FLOWS.md`, `docs/GATEWAY_CONTRACT.md`, security/ops docs, and tests.

Planning defaults for implementation:

- Testing: yes. Security-sensitive endpoints and operations require feature/unit/smoke coverage.
- Logging: verbose. New logs must be structured, request-correlated, and configurable by existing Laravel/OpenResty log levels.
- Documentation: yes. This change includes mandatory docs and runbook updates.
- Roadmap linkage: Security / Auth / Secrets, Observability / Logs / Metrics, CI/CD / Delivery.

## Risks / Open Questions

- Rate-limit thresholds need conservative defaults now and environment overrides for production tuning.
- Key rotation must preserve old public keys until issued tokens expire; premature retirement can break active clients and the gateway.
- Existing non-expiring API tokens mean automatic key retirement must block, warn, or require explicit force mode until those tokens are revoked or accepted as residual risk.
- Dependency audit commands can be noisy; CI should use deterministic severity thresholds rather than blocking on advisory fetch instability without context.
- Gateway stale-key behavior must remain fail closed. If a future stale-while-revalidate mode is desired, it needs a separate threat model.
