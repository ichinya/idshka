# Design: backfill-plans-01-05-specs

## Context

Plans 01 through 05 were implemented and verified through the legacy AI Factory companion plan workflow. Their implementation summaries exist under `.ai-factory/specs/<plan-id>/spec.md`. They were also migrated into `openspec/changes/archive/2026-05-01-<plan-id>/`, but those archive entries contain only proposal, design, and task history. They do not contain OpenSpec delta specs, so `openspec/specs` remains empty.

## Approach

Create one OpenSpec-native backfill change that introduces base requirements for the completed system areas. This avoids reopening or duplicating the already archived plan-specific changes and keeps the backfill history explicit.

Each completed legacy plan maps to one OpenSpec capability:

- Plan 01 -> `platform-foundation`
- Plan 02 -> `identity-auth`
- Plan 03 -> `site-registry`
- Plan 04 -> `token-issuer`
- Plan 05 -> `api-resource-gateway`

## Artifact Model

This change only writes OpenSpec artifacts:

- `openspec/changes/backfill-plans-01-05-specs/proposal.md`
- `openspec/changes/backfill-plans-01-05-specs/design.md`
- `openspec/changes/backfill-plans-01-05-specs/tasks.md`
- `openspec/changes/backfill-plans-01-05-specs/specs/*/spec.md`

The archive step owns promotion into `openspec/specs/**`. Application source files remain unchanged.

## Validation

Run strict OpenSpec validation before archive:

```powershell
openspec validate backfill-plans-01-05-specs --type change --strict
```

Then archive without `--skip-specs` so OpenSpec writes base specs:

```powershell
openspec archive backfill-plans-01-05-specs --yes
```

## Risks

- The generated requirements are retrospective and summarize already implemented behavior, so they should not add new acceptance criteria beyond plans 01 through 05.
- Future changes should evolve these specs with normal OpenSpec delta changes instead of editing archived plan summaries.
