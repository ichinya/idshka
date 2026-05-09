# Rules for this plan

- Do not rewrite issuer internals while doing frontend refactor.
- Do not introduce a SPA framework. Keep Blade + Tailwind unless explicitly requested.
- Do not expose secrets after first display.
- Do not trust user-supplied `X-Idshka-*` headers anywhere.
- Do not mix end-user account controls with developer site controls on the same page.
- Keep route names stable where existing forms depend on them; otherwise add redirects/aliases.
- Keep all forms protected by auth, CSRF and existing rate limits.
- Prefer small controllers over a single portal controller.
- Prefer reusable Blade components over repeated card/table markup.
- Every page must have an empty state.
