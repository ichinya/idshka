# Context

`idshka.ru` is a Laravel-based identity and token issuer service.

Users of `idshka.ru` have two different mental models:

1. They are ordinary users who register/login, link social accounts and manage their own identity/session/tokens.
2. They are web developers who connect their own sites, such as `apishka.ru`, and configure either API-only token access or Web Login through `idshka.ru`.

There is also a third horizontal area: audit. Audit must not be hidden inside a generic dashboard because it is needed for security review, debugging and developer confidence.

The current portal is functionally useful but too concentrated in one place. It should become a clear product surface.

## Existing backend assumptions

- Keep Laravel session-based web auth for portal pages.
- Keep Socialite controllers/actions for future provider login/linking.
- Keep OAuth issuer endpoints and token issuer services as backend services.
- Keep audit model/events and surface them through a better UI.
- Do not change token format or gateway contract unless explicitly required.

## Product naming

- Main service: `idshka.ru`.
- Example connected site: `apishka.ru`.
- Connected sites may be API-only, Web Login, or both.
