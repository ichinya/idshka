# Design: 04-token-issuer-and-jwks

## Technical Approach

# Context

Plan: `04-token-issuer-and-jwks`

Project: `idshka.ru` Laravel + Socialite identity/control plane.

Goal: Сделать выпуск JWT для API-only режима, публикацию JWKS и revoke/denylist.

Relevant docs:
- `.ai-factory/TECH_STACK.md`
- `.ai-factory/ARCHITECTURE.md`
- `.ai-factory/RULES.md`
- `docs/API_FLOWS.md`
- `docs/GATEWAY_CONTRACT.md`

## Data / Artifact Model

Migrated from legacy AI Factory plan artifacts. Preserve runtime-only source material under `.ai-factory/state/<change-id>/` and QA evidence under `.ai-factory/qa/<change-id>/`.

## Integration Points

- Legacy source: .ai-factory/plans/04-token-issuer-and-jwks/context.md

## Alternatives Considered

- Preserve raw context only as runtime notes. Rejected when the context contains design-relevant implementation guidance.

## Risks

- Migrated context may include raw notes that need manual refinement before implementation.
