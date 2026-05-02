# Proposal: backfill-plans-01-05-specs

## Intent

Backfill canonical OpenSpec base specs for the already completed and archived legacy plans 01 through 05.

The current repository has legacy AI Factory archive summaries for these plans and OpenSpec archive entries that preserve proposal, design, and task history, but it does not yet have corresponding `openspec/specs/**/spec.md` requirements. This change adds those requirements without changing application code.

## Scope

- Add base OpenSpec requirements for the Laravel platform foundation.
- Add base OpenSpec requirements for first-party identity auth and Socialite login.
- Add base OpenSpec requirements for site registry, verification, and site modes.
- Add base OpenSpec requirements for user API token issuer and JWKS.
- Add base OpenSpec requirements for the demo resource API gateway.

## Out of Scope

- No PHP, Lua, Docker, CI, or documentation runtime behavior changes.
- No rewrite of existing `.ai-factory/specs/**` legacy archives.
- No recreation of already archived OpenSpec change folders for plans 01 through 05.
- No specs for plans 06 through 08.

## Source Evidence

Requirements are derived from:

- `.ai-factory/specs/01-laravel-platform-foundation/spec.md`
- `.ai-factory/specs/02-user-auth-socialite/spec.md`
- `.ai-factory/specs/03-site-registry-and-modes/spec.md`
- `.ai-factory/specs/04-token-issuer-and-jwks/spec.md`
- `.ai-factory/specs/05-api-resource-gateway-for-demo-resource/spec.md`

## Expected Result

After validation and archive, OpenSpec should contain real base specs under:

- `openspec/specs/platform-foundation/spec.md`
- `openspec/specs/identity-auth/spec.md`
- `openspec/specs/site-registry/spec.md`
- `openspec/specs/token-issuer/spec.md`
- `openspec/specs/api-resource-gateway/spec.md`
