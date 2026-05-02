## Test Plan: 07-portal-token-and-client-management

**Date:** 2026-05-01
**Branch / Version:** `main` / current working tree
**Environment:** local или staging с доступом к Laravel app, PostgreSQL и Redis

---

### 1. Testing Goal

Проверить, что владелец сайта может безопасно управлять сайтами, API-токенами, OIDC web clients, redirect URI, revoke-действиями и audit events через `/portal`, а security-sensitive данные не раскрываются за пределами одноразового отображения после создания.

---

### 2. Test Scope

**In Scope** — тестируем:

- Доступ к `/portal` только для authenticated owner.
- Создание сайта и отображение DNS TXT / `/.well-known/` verification instructions.
- Проверка домена и включение режимов `api_resource` и `web_client`.
- Выпуск user API token, одноразовый показ raw bearer token, metadata-only list, revoke с подтверждением.
- Создание OIDC web client, одноразовый показ raw `client_secret`, metadata-only list, revoke с подтверждением.
- Добавление exact HTTPS redirect URI без wildcard.
- Durable audit events в таблице портала и отсутствие raw secrets в audit metadata.
- Owner isolation для чужих sites, tokens и clients.
- Регрессия existing site registry, issuer и OIDC web login flows, которые используют те же domain models/actions.

**Out of Scope** — не тестируем:

- Полный внешний gateway OpenResty flow для `example.test`, кроме проверки, что созданный API token имеет ожидаемый owner/site/audience lifecycle.
- Socialite provider callback от реальных Google/VK/Yandex, кроме проверки, что новые audit listeners не ломают существующие identity events.
- Production deployment, rate limits, observability dashboards и release automation.

---

### 3. Test Types

| Type | Priority | Area |
|------|----------|------|
| Functional | 🔴 High | Portal dashboard, site flow, token flow, OIDC client flow, redirect URI flow, audit table |
| Security | 🔴 High | Owner isolation, one-time secret display, revoke confirmation, fail-closed eligibility |
| Negative | 🟡 Medium | Invalid domain, unverified site, missing mode, bad redirect URI, missing confirmation |
| Edge cases | 🟡 Medium | Duplicate redirect URI, repeated revoke, long URI/domain/display name |
| Regression | 🟡 Medium | Existing site registry, issuer, OIDC authorize/token/userinfo, identity audit events |
| Performance | 🟢 Low | Dashboard with many sites/tokens/clients/audit events |

---

### 4. Test Data

| Category | Data | Purpose |
|----------|------|---------|
| Owner A | `owner-a@example.test` / valid password | Main portal owner |
| Owner B | `owner-b@example.test` / valid password | Foreign-owner isolation |
| Valid site | `https://Example App.ru/path?query=1`, display `Example App` | Domain normalization and portal creation |
| Verified site | `example.test`, status `verified`, modes `api_resource`, `web_client` | Credential creation flows |
| API token input | scopes `orders.read`, permissions `orders.read` | Happy path token issuance |
| Web client input | name `Example Web`, redirect `https://example.test/auth/idshka/callback` | Happy path OIDC client creation |
| Additional redirect | `https://example.test/auth/idshka/tenant/callback` | Redirect URI management |
| Invalid redirect | `http://example.test/callback`, `https://*.example.test/callback` | Security rejection |
| Revoke confirmation | exact value `revoke`; invalid values ``, `yes`, `delete` | Danger action validation |

---

### 5. Preconditions

- [ ] Laravel app is running and `/portal` is reachable over browser session.
- [ ] Database migrations are applied, including `audit_events`.
- [ ] At least one active signing key exists for token issuance.
- [ ] Owner A and Owner B accounts exist and can authenticate with session auth.
- [ ] For live domain verification checks, a controllable domain or deterministic local verification setup is available.
- [ ] Browser session/cookies can be cleared between owner-isolation checks.

---

### 6. Acceptance Criteria

- [ ] All 🔴 high-priority checks pass.
- [ ] Raw API token and raw `client_secret` are visible only immediately after creation and never in later dashboard lists.
- [ ] Foreign-owner access to site/token/client routes returns a not-found or forbidden result and does not reveal object metadata.
- [ ] Revoke requests without exact `confirm=revoke` do not mutate credential state.
- [ ] Unverified or wrongly-moded sites cannot receive production credentials.
- [ ] Audit table shows lifecycle events for the owner and stores only non-secret metadata.
- [ ] Existing site/token/OIDC behavior remains consistent after adding portal and audit listeners.

---

### 7. Plan Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Secret leak in UI/audit/logs | High | Inspect portal after refresh, audit event details, and relevant application logs for raw bearer token/client secret. |
| Owner isolation bypass via route-model binding | High | Exercise direct URLs for Owner B against Owner A resources and verify no mutation or metadata disclosure. |
| Domain action mismatch between API and portal | Medium | Compare browser portal behavior with existing API behavior for site creation, mode enablement and token issue/revoke. |
| Redirect URI validation too permissive | Medium | Test `http`, wildcard, malformed URL, duplicate URL and very long URL. |
| Audit noise or missing events | Medium | Verify exact action names and ensure repeated idempotent operations do not create misleading duplicate events. |

### 8. Checklist

| Check | Priority |
|-------|----------|
| Guest cannot open `/portal`; authenticated owner can. | High |
| Empty dashboard renders without errors for a new owner. | Medium |
| Owner can create a site and see normalized domain plus DNS/file instructions. | High |
| Unverified site cannot enable production mode or issue credentials. | High |
| Verified owned site can enable `api_resource` and `web_client`. | High |
| API token can be issued for verified `api_resource` site and raw token is one-time only. | High |
| API token revoke requires exact `confirm=revoke`. | High |
| OIDC web client can be created for verified `web_client` site and raw secret is one-time only. | High |
| OIDC client revoke requires exact `confirm=revoke`. | High |
| Redirect URI accepts exact HTTPS and rejects wildcard/non-HTTPS. | High |
| Owner B cannot act on Owner A resources. | High |
| Audit table shows non-secret lifecycle events. | High |
| Existing OAuth authorize/token/userinfo flow still works with a portal-created client. | Medium |
| Long token/URI values remain readable and do not break the page. | Low |
