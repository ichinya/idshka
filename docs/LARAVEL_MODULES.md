# LARAVEL_MODULES

## `app/Domain/Identity`
Назначение:
- пользователи;
- Laravel sessions;
- Socialite external accounts;
- account linking/unlinking.

Ключевые классы:
- `SocialiteRedirectController`
- `SocialiteCallbackController`
- `SocialAccountLinker`
- `IdentityAuditListener`

## `app/Domain/Sites`
Назначение:
- подключённые домены;
- verification;
- site modes.

Ключевые классы:
- `CreateSiteAction`
- `VerifySiteDomainAction`
- `DnsTxtVerificationChecker`
- `WellKnownFileVerificationChecker`

## `app/Domain/ApiResources`
Назначение:
- audience;
- scopes;
- permissions;
- policy registry.

## `app/Domain/OidcClients`
Назначение:
- web clients;
- client_id/client_secret;
- redirect URI;
- client secret rotation.

## `app/Domain/Issuer`
Назначение:
- authorization code;
- PKCE;
- token issue;
- JWKS;
- revoke/introspection.

Ключевые классы:
- `TokenIssuer`
- `JwksService`
- `SigningKeyService`
- `AuthorizationCodeService`
- `PkceService`
- `RevocationService`

## `app/Domain/Audit`
Назначение:
- immutable audit log для auth/security actions.

## `app/Contracts/Auth`
Назначение:
- claims schema;
- gateway headers;
- signed context envelope;
- scopes/permissions constants.
