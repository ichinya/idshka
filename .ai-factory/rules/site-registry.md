# Area: site_registry

## Scope
- Подключение доменов.
- Верификация владения.
- Режимы сайта: `api_resource`, `web_client`.
- Site-level настройки scopes, redirect URI и gateway.

## Rules
- Домен нормализуется и хранится без scheme/path.
- Для верификации домена используются DNS TXT и file challenge как MVP.
- Один verified domain может принадлежать только одному active owner/team.
- Site mode нельзя включить без verified domain.
- `api_resource` требует audience identifier.
- `web_client` требует минимум один exact redirect URI.
- Удаление сайта должно revoke/disable связанные tokens/clients или требовать явного подтверждения.

## Forbidden
- Нельзя принимать wildcard redirect URI в MVP.
- Нельзя включать API audience без owner confirmation.
- Нельзя переиспользовать verification token между доменами.
