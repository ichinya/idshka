# Design: 05-api-resource-gateway-for-demo-resource

## Technical Approach

# Context

Plan: `05-api-resource-gateway-for-demo-resource`

Project: `idshka.ru` Laravel + Socialite identity/control plane.

Goal: Собрать OpenResty gateway example, который проверяет токены idshka.ru и прокидывает доверенный context в demo-resource-api.

Relevant docs:
- `.ai-factory/TECH_STACK.md`
- `.ai-factory/ARCHITECTURE.md`
- `.ai-factory/RULES.md`
- `docs/API_FLOWS.md`
- `docs/GATEWAY_CONTRACT.md`

## Data / Artifact Model

Migrated from legacy AI Factory plan artifacts. Preserve runtime-only source material under `.ai-factory/state/<change-id>/` and QA evidence under `.ai-factory/qa/<change-id>/`.

## Integration Points

- Legacy source: .ai-factory/plans/05-api-resource-gateway-for-demo-resource/context.md

## Alternatives Considered

- Preserve raw context only as runtime notes. Rejected when the context contains design-relevant implementation guidance.

## Risks

- Migrated context may include raw notes that need manual refinement before implementation.
