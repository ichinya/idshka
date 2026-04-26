[← Previous Page](README.md) · [Back to README](../README.md) · [Next Page →](API_FLOWS.md)

# Laravel Modules

Ниже — не только целевая карта модулей, но и текущий статус по репозиторию. Сейчас реально materialized прежде всего `Sites` и часть `Audit`; остальные домены пока существуют как skeleton boundaries и documentation-first contracts.

## Сводка по модулям

| Module | Status | Что есть сейчас |
|--------|--------|-----------------|
| `app/Domain/Identity` | implemented slice | session auth, Socialite redirect/callback, social account linking/unlinking |
| `app/Domain/Sites` | implemented slice | create/verify/mode enablement, policy checks, verifiers |
| `app/Domain/ApiResources` | implemented minimal slice | audience/scopes/permissions resolver для user API token |
| `app/Domain/OidcClients` | skeleton | будущие web clients и redirect URI |
| `app/Domain/Issuer` | implemented slice | user API token issue/revoke, signing keys, JWKS |
| `app/Domain/Audit` | partial | listeners для site registry, identity и issuer audit events |
| `app/Contracts/Auth` | implemented slice | JWT claims/headers, scopes, permissions |

## Реально реализованные части

### `app/Domain/Sites`

Назначение:
- подключённые домены;
- verification;
- site modes.

Ключевые классы:
- `CreateSiteAction`
- `VerifySiteDomainAction`
- `EnableSiteModeAction`
- `DnsTxtVerificationChecker`
- `WellKnownFileVerificationChecker`
- `EloquentVerifiedSiteLookup`

### `app/Domain/Audit`

Назначение:
- immutable audit log для auth/security actions;
- текущая реализация уже слушает site registry events.

Ключевые классы:
- `RecordSiteAuditEvent`
- `RecordIdentityAuditEvent`

## Модули и следующие расширения

### `app/Domain/ApiResources`

Назначение:
- audience;
- scopes;
- permissions;
- policy registry.

Текущий slice:
- `ApiResourceAccessResolver`
- `SiteApiResourceAccessResolver`
- strict allow-list validation для scopes/permissions

### `app/Domain/OidcClients`

Назначение:
- web clients;
- client id / client secret;
- redirect URI;
- client secret rotation.

### `app/Domain/Issuer`

Назначение:
- user API token issue;
- JWKS;
- revoke;
- future authorization code / PKCE / introspection.

Текущие классы:
- `TokenIssuer`
- `JwksService`
- `SigningKeyService`
- `RevocationService`

Будущие классы для plan `06`:
- `AuthorizationCodeService`
- `PkceService`

### `app/Contracts/Auth`

Назначение:
- claims schema;
- gateway headers;
- signed context envelope;
- scopes / permissions constants.

## HTTP entry points по состоянию репозитория

- `routes/api.php` — рабочие endpoints для site registry и user API token issue/revoke.
- `routes/web.php` — рабочие auth/social endpoints (`register`, `login`, `logout`, `/auth/{provider}/...`).
- `routes/oauth.php` — рабочий public JWKS endpoint; provider authorize/token/userinfo endpoints остаются будущей фазой.

## See Also

- [API Flows](API_FLOWS.md) — какие запросы уже можно вызвать сейчас
- [Gateway Contract](GATEWAY_CONTRACT.md) — как должен выглядеть API-only boundary после issuer phase
- [Socialite](SOCIALITE.md) — будущий login flow и его ограничения

## Update: Issuer module implemented slice (2026-04-23)

Новые классы и артефакты:

- Contracts: `JwtClaims`, `JwtHeaders`, `Scopes`, `Permissions`
- Persistence: `signing_keys`, `api_tokens`, `revoked_jti`
- Services: `SigningKeyService`, `JwksService`, `TokenIssuer`, `RevocationService`
- Action: `IssueUserApiTokenAction`
- API: `IssueUserApiTokenController`, `RevokeUserApiTokenController`
- OAuth: `PublicJwksController`
- Events/Audit: `UserApiTokenIssued`, `UserApiTokenRevoked`, `RecordIssuerAuditEvent`

Модуль `app/Domain/Issuer` больше не skeleton: реализован user API token issue/revoke + JWKS publication slice.
