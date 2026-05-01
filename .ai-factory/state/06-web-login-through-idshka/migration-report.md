# Legacy Plan Migration: 06-web-login-through-idshka

## Summary

Migrated from legacy `.ai-factory/plans` artifacts to OpenSpec-native artifacts.

## Source artifacts

- .ai-factory/plans/06-web-login-through-idshka.md
- .ai-factory/plans/06-web-login-through-idshka/task.md
- .ai-factory/plans/06-web-login-through-idshka/context.md
- .ai-factory/plans/06-web-login-through-idshka/rules.md
- .ai-factory/plans/06-web-login-through-idshka/verify.md
- .ai-factory/plans/06-web-login-through-idshka/status.yaml

## Generated OpenSpec artifacts

- openspec/changes/06-web-login-through-idshka/proposal.md
- openspec/changes/06-web-login-through-idshka/tasks.md
- openspec/changes/06-web-login-through-idshka/design.md

## Runtime artifacts

- .ai-factory/state/06-web-login-through-idshka/legacy-context.md
- .ai-factory/state/06-web-login-through-idshka/legacy-rules.md
- .ai-factory/state/06-web-login-through-idshka/legacy-status.yaml
- .ai-factory/qa/06-web-login-through-idshka/legacy-verify.md

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
- Run /aif-improve 06-web-login-through-idshka after migration to refine proposal, design, tasks, and specs.
- Author or refine OpenSpec delta specs manually if validation requires them.
