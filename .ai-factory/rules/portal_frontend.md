# Area rules: portal_frontend

## Scope

Applies to Blade views, Tailwind classes, portal web controllers, portal route organization and UI copy.

## Rules

- Portal must be split into Account, Developer and Audit workspaces.
- Account workspace is for the person and their identity.
- Developer workspace is for connected sites and integration setup.
- Audit workspace is for event history and security visibility.
- Use Blade components for repeated UI blocks: cards, badges, empty states, code snippets, copy buttons.
- Keep forms simple and server-rendered unless a real interactive need exists.
- Alpine.js may be used only for lightweight interactions.
- No Vue/React/Next introduction in this plan.
- Every destructive action needs confirmation UI.
- Every secret/token page must explain that secrets are shown once.
- Never render raw JSON/HTML from audit metadata without escaping.
