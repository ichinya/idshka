# 02-site-registry-and-modes

## Цель
Реализовать подключение домена apishka.ru, верификацию владения и включение режимов api_resource/web_client.

## Area
site_registry, portal

## Что должно появиться
- sites schema
- site_verifications schema
- site_modes schema
- POST /v1/sites
- POST /v1/sites/{id}/verify
- API resource settings
- OIDC client settings skeleton

## Зависимости
`01-platform-foundation`

## Acceptance criteria
- verified domain получает site_id
- режим api_resource нельзя включить без verified domain
- режим web_client требует exact redirect_uri

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.
