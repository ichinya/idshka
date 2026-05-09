# idshka.ru Portal Frontend Spec

## Product structure

The portal has three top-level sections.

### 1. Account

For ordinary users of `idshka.ru`.

Pages:

- Overview
- Social accounts
- Sessions/devices
- User tokens
- Connected applications

Primary questions this section answers:

- Who am I in idshka?
- Which social accounts are linked?
- Where am I logged in?
- Which personal tokens did I create?
- Which apps/sites have access to my identity?

### 2. Developer

For website owners who connect their own sites, e.g. `example.test`.

Pages:

- Developer overview
- Sites
- Create site
- Site overview
- Domain verification
- Credentials
- Redirect URIs
- API-only gateway guide
- Web Login guide

Primary questions this section answers:

- How do I connect my site?
- Is my domain verified?
- Which mode does my site use: API-only, Web Login, or both?
- Which credentials and redirect URIs are configured?
- How does my API gateway validate idshka tokens?

### 3. Audit

For visibility and security.

Pages:

- Audit list
- Audit event detail

Primary questions this section answers:

- What happened?
- Who did it?
- From where?
- Which site/token/social provider was affected?
- Was it a security-sensitive event?

## Visual language

Use a modern SaaS dashboard style:

- Sidebar navigation.
- Topbar with current user and environment.
- Cards for summaries.
- Tables for tokens/sites/audit.
- Badges for status.
- Callouts for warnings and setup steps.
- Code blocks with copy buttons for integration snippets.

## Suggested dashboard cards

Account overview:

- Linked providers count.
- Active sessions count.
- Active user tokens count.
- Recent account events.

Developer overview:

- Connected sites count.
- Verified domains count.
- Active credentials count.
- Recent developer events.

Audit overview:

- Recent security events.
- Recent token events.
- Recent site configuration events.
