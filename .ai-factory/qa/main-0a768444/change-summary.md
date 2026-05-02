## Change Summary

**Commits:** 0, анализ текущего рабочего дерева на ветке `main`
**Changed files:** 23 implementation/spec files плюс QA evidence
**Risk level:** 🔴 High

---

### What Changed

Добавлен self-service портал владельца сайта для управления подключенными доменами, проверкой владения, режимами сайта, API-токенами, OIDC web clients, redirect URI, revoke-действиями и audit trail. Изменение закрывает MVP-срез личного кабинета control plane для `idshka.ru`.

---

### Affected Areas

| Component | Change type | Description |
|-----------|-------------|-------------|
| Portal UI | Added | Новый Blade/Tailwind dashboard `/portal` с карточками сайтов, формами создания сайта, проверки домена, включения режимов, выпуска/revoke токенов и управления web clients. |
| Portal HTTP layer | Added | Новый `PortalController` и 9 web routes под `auth:web`; контроллеры переводят browser-формы в существующие domain actions/services. |
| Site management | Changed | Browser flow теперь использует `CreateSiteAction`, `VerifySiteDomainAction`, `EnableSiteModeAction` и показывает DNS TXT / well-known file instructions. |
| API token lifecycle | Changed | Из портала можно выпустить user API token, увидеть raw bearer token только сразу после создания и отозвать токен только при `confirm=revoke`. |
| OIDC client lifecycle | Added | Добавлены domain actions/events/DTO для создания web client, добавления redirect URI и revoke клиента; raw client secret возвращается только при создании, хранится только hash. |
| Audit | Added / Changed | Добавлены `audit_events` table, `AuditEvent`, `AuditRecorder`, OIDC audit listener; существующие identity/site/issuer listeners теперь пишут durable audit metadata. |
| Docs / OpenSpec | Changed | Обновлены `docs/API_FLOWS.md`, OpenSpec tasks и delta spec для portal-token-client-management. |
| QA evidence | Added | Зафиксированы verify evidence и QA artifacts для изменения `07-portal-token-and-client-management`. |

---

### Risks

🔴 **Critical** (must verify):

- Authorization boundary: владелец не должен видеть, менять, отзывать или использовать сайты, токены и clients другого пользователя.
- Secret handling: raw bearer token и raw `client_secret` должны показываться только один раз после создания и не попадать в списки, audit, logs или metadata.
- Credential lifecycle: revoke API token и OIDC client должен требовать явного `confirm=revoke` и не должен срабатывать при пустом/ошибочном подтверждении.
- Eligibility rules: выпуск токена и создание web client должны fail-closed для unverified site, missing mode и foreign site.

🟡 **Medium** (should verify):

- Redirect URI rules: должны приниматься только exact HTTPS URI без wildcard; duplicate URI не должен создавать лишний audit event.
- Audit completeness: site/token/client/redirect/revoke lifecycle events должны появляться в audit table с non-secret metadata.
- UI state after redirects: flash-секреты должны отображаться на первом redirect response и исчезать после обновления/повторного открытия `/portal`.
- Existing API/OAuth flows могут регрессировать из-за новых listeners, events и route additions.

🟢 **Low** (nice to verify):

- Empty-state интерфейс для пользователя без сайтов, токенов, клиентов и audit events.
- Long values в UI: длинный JWT, длинный redirect URI, длинный domain/display name не должны ломать layout.
- Documentation examples в `docs/API_FLOWS.md` совпадают с фактическими portal routes.

---

### Testing Recommendations

**First priority:**

- [ ] Проверить полный happy path владельца: login -> `/portal` -> create site -> verify -> enable `api_resource` и `web_client` -> issue API token -> create web client -> add redirect URI -> see audit.
- [ ] Проверить one-time display: raw API token и raw client secret видны только сразу после создания и исчезают при повторном открытии dashboard.
- [ ] Проверить запреты: чужие site/token/client, unverified site, missing mode, wildcard/non-HTTPS redirect URI, revoke без `confirm=revoke`.
- [ ] Проверить audit trail: события создаются для site, mode, token, OIDC client, redirect URI и revoke без raw secret values.

**Regression:**

- [ ] Проверить existing site registry API и token issuer behavior через прежние публичные flows.
- [ ] Проверить existing OIDC authorize/token/userinfo happy path с client, созданным через портал.
- [ ] Проверить, что Socialite login/link/unlink audit events продолжают писаться и не раскрывают provider tokens.
