# Verify: 07-portal-token-and-client-management

Status: WARN, non-blocking.

## Scope

- Mode: OpenSpec-native.
- Change: `openspec/changes/07-portal-token-and-client-management`.
- Canonical artifacts checked: proposal, design, tasks, delta spec, and base specs.
- QA evidence recorded under `.ai-factory/qa/07-portal-token-and-client-management/`.

## Requirement Trace

- Self-service dashboard: PASS. `GET /portal` is registered behind `auth:web`, loads owner-scoped sites, API token metadata, OIDC client metadata, redirect URIs, and audit events.
- Site creation and verification instructions: PASS. Portal site creation delegates to `CreateSiteAction`; the dashboard renders DNS TXT and well-known file instructions.
- Site mode enablement: PASS. Portal mode enablement delegates to `EnableSiteModeAction` and owner-checks the route model before calling domain logic.
- API token lifecycle: PASS. Portal token issue delegates to `IssueUserApiTokenAction`, flashes the raw token once, lists metadata, and requires `confirm=revoke` before revoke.
- OIDC client lifecycle: PASS. New domain actions create, add redirect URIs, and revoke clients; client secrets are hashed at rest and flashed once.
- Redirect URI management: PASS. Redirect URI inputs require HTTPS URLs and reject wildcards; domain actions hash redirect URI values for lookup.
- Durable audit: PASS. `audit_events` migration/model/service and listeners persist non-secret lifecycle metadata; the portal displays recent owner audit events.

## Context Gates

- Architecture: PASS. HTTP concerns stay in `PortalController` and Blade; business logic lives in domain actions/services/listeners under `app/Domain/*`.
- Project rules: PASS. Raw bearer tokens and client secrets are only exposed through flash data immediately after issuance; persisted and logged data are metadata/hash based.
- Roadmap: PASS. The roadmap explicitly calls out plan `07-portal-token-and-client-management` and audit events as the next portal/UI slice.
- Description/stack sync: PASS. The change uses existing Laravel, Blade, Tailwind, Vite, and Composer/NPM tooling; no new dependency or environment contract was introduced.

## Command Evidence

- `openspec validate 07-portal-token-and-client-management --type change --strict --json --no-interactive --no-color`: PASS.
- `openspec status --change 07-portal-token-and-client-management --json --no-color`: WARN. The CLI status command rejects numeric-leading change names, while strict validation accepts this change id.
- `php artisan test`: PASS, 74 tests / 616 assertions.
- `npm run build`: PASS, Vite production build completed.
- `composer validate --no-interaction`: PASS.
- `.\vendor\bin\pint --test app/Domain/Audit app/Domain/OidcClients app/Http/Controllers/Portal app/Providers/EventServiceProvider.php routes/web.php database/migrations/2026_05_01_170000_create_audit_events_table.php tests/Feature/PortalManagementFlowTest.php`: PASS.
- `git diff --check -- <verified scope>`: PASS. Git emitted a non-blocking CRLF normalization notice for `openspec/changes/07-portal-token-and-client-management/proposal.md`.
- `rg` debug-marker scan over verified scope: PASS, no matches.
- `rg` env/config scan over verified scope: PASS. The only match is `config('app.name', 'idshka')` in the Blade title.
- `php artisan route:list --path=portal`: PASS, 9 portal routes registered. An earlier exploratory retry using unsupported `--compact` failed as a command-option mismatch and was not an application failure.

## Warnings

- Generated OpenSpec rules are missing:
  - `.ai-factory/rules/generated/openspec-merged-07-portal-token-and-client-management.md`
  - `.ai-factory/rules/generated/openspec-change-07-portal-token-and-client-management.md`
  - `.ai-factory/rules/generated/openspec-base.md`
- `openspec status` is unavailable for this numeric-leading change id, but `openspec validate --strict` passed and code verification was allowed by configuration.

```aif-gate-result
{
  "schema_version": 1,
  "gate": "verify",
  "status": "warn",
  "blocking": false,
  "blockers": [],
  "affected_files": [
    ".ai-factory/qa/07-portal-token-and-client-management/openspec-validation.json",
    ".ai-factory/qa/07-portal-token-and-client-management/openspec-status.json",
    ".ai-factory/rules/generated/openspec-merged-07-portal-token-and-client-management.md",
    ".ai-factory/rules/generated/openspec-change-07-portal-token-and-client-management.md",
    ".ai-factory/rules/generated/openspec-base.md",
    "openspec/changes/07-portal-token-and-client-management/proposal.md",
    "openspec/changes/07-portal-token-and-client-management/design.md",
    "openspec/changes/07-portal-token-and-client-management/tasks.md",
    "openspec/changes/07-portal-token-and-client-management/specs/portal-token-client-management/spec.md",
    "app/Http/Controllers/Portal/PortalController.php",
    "resources/views/portal/dashboard.blade.php",
    "routes/web.php",
    "app/Domain/OidcClients/Actions/CreateOidcClientAction.php",
    "app/Domain/OidcClients/Actions/AddOidcRedirectUriAction.php",
    "app/Domain/OidcClients/Actions/RevokeOidcClientAction.php",
    "app/Domain/Audit/Models/AuditEvent.php",
    "app/Domain/Audit/Services/AuditRecorder.php",
    "app/Domain/Audit/Listeners/RecordOidcClientAuditEvent.php",
    "app/Providers/EventServiceProvider.php",
    "database/migrations/2026_05_01_170000_create_audit_events_table.php",
    "docs/API_FLOWS.md",
    "tests/Feature/PortalManagementFlowTest.php"
  ],
  "suggested_next": {
    "command": "/aif-commit",
    "reason": "Verification found no blockers; remaining findings are non-blocking OpenSpec metadata/tooling warnings."
  }
}
```
