[← Previous Page](README.md) · [Back to README](../README.md) · [Next Page →](API_FLOWS.md)

# Laravel Modules

Ниже — не только целевая карта модулей, но и текущий статус по репозиторию. Сейчас реально materialized прежде всего `Sites` и часть `Audit`; остальные домены пока существуют как skeleton boundaries и documentation-first contracts.

## Сводка по модулям

| Module | Status | Что есть сейчас |
|--------|--------|-----------------|
| `app/Domain/Identity` | implemented slice | session auth, Socialite redirect/callback, social account linking/unlinking |
| `app/Domain/Sites` | implemented slice | create/verify/mode enablement, policy checks, verifiers |
| `app/Domain/ApiResources` | skeleton | будущие audience/scopes/permissions |
| `app/Domain/OidcClients` | skeleton | будущие web clients и redirect URI |
| `app/Domain/Issuer` | skeleton | будущие token/JWKS/PKCE flows |
| `app/Domain/Audit` | partial | listeners для site registry и identity audit events |
| `app/Contracts/Auth` | skeleton | будущие claims, headers и signed context |

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

## Модули, которые ещё впереди

### `app/Domain/ApiResources`

Назначение:
- audience;
- scopes;
- permissions;
- policy registry.

### `app/Domain/OidcClients`

Назначение:
- web clients;
- client id / client secret;
- redirect URI;
- client secret rotation.

### `app/Domain/Issuer`

Назначение:
- authorization code;
- PKCE;
- token issue;
- JWKS;
- revoke / introspection.

Ожидаемые классы:
- `TokenIssuer`
- `JwksService`
- `SigningKeyService`
- `AuthorizationCodeService`
- `PkceService`
- `RevocationService`

### `app/Contracts/Auth`

Назначение:
- claims schema;
- gateway headers;
- signed context envelope;
- scopes / permissions constants.

## HTTP entry points по состоянию репозитория

- `routes/api.php` — рабочие endpoints только для site registry.
- `routes/web.php` — рабочие auth/social endpoints (`register`, `login`, `logout`, `/auth/{provider}/...`).
- `routes/oauth.php` — placeholder для issuer / provider endpoints.

## See Also

- [API Flows](API_FLOWS.md) — какие запросы уже можно вызвать сейчас
- [Gateway Contract](GATEWAY_CONTRACT.md) — как должен выглядеть API-only boundary после issuer phase
- [Socialite](SOCIALITE.md) — будущий login flow и его ограничения
