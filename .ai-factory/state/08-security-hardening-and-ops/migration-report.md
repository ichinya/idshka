# Legacy Plan Migration: 08-security-hardening-and-ops

## Summary

Migrated from legacy `.ai-factory/plans` artifacts to OpenSpec-native artifacts.

## Source artifacts

- .ai-factory/plans/08-security-hardening-and-ops.md
- .ai-factory/plans/08-security-hardening-and-ops/task.md
- .ai-factory/plans/08-security-hardening-and-ops/context.md
- .ai-factory/plans/08-security-hardening-and-ops/rules.md
- .ai-factory/plans/08-security-hardening-and-ops/verify.md
- .ai-factory/plans/08-security-hardening-and-ops/status.yaml

## Generated OpenSpec artifacts

- openspec/changes/08-security-hardening-and-ops/proposal.md
- openspec/changes/08-security-hardening-and-ops/tasks.md
- openspec/changes/08-security-hardening-and-ops/design.md

## Runtime artifacts

- .ai-factory/state/08-security-hardening-and-ops/legacy-context.md
- .ai-factory/state/08-security-hardening-and-ops/legacy-rules.md
- .ai-factory/state/08-security-hardening-and-ops/legacy-status.yaml
- .ai-factory/qa/08-security-hardening-and-ops/legacy-verify.md

## Validation

OpenSpec validation: FAIL

## Diagnostics

Warnings:
- legacy-rules-preserved: Legacy rules were preserved as runtime notes. Regenerate OpenSpec-derived rules after migration.
- manual-spec-authoring-needed: No clear behavioral requirements were extracted; write or refine delta specs manually.

Errors:
- openspec-validation-failed: OpenSpec validation failed after migration.

## Manual follow-ups

- Review generated OpenSpec artifacts before implementation.
- Run /aif-improve 08-security-hardening-and-ops after migration to refine proposal, design, tasks, and specs.
- Author or refine OpenSpec delta specs manually if validation requires them.
