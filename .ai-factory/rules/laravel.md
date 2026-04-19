# Area: laravel

- Основной продукт — Laravel modular monolith.
- Новую доменную логику класть в `app/Domain/<Context>`.
- HTTP controllers должны быть тонкими: validation -> service/action -> response.
- Использовать FormRequest для сложной валидации.
- Migrations должны содержать индексы для `user_id`, `site_id`, `client_id`, `jti`, `kid`, `audience`.
- Secrets хранить зашифрованно или хэшировать, если raw значение больше не нужно.
- Использовать Laravel events/listeners для audit trail.
- Feature tests обязательны для публичных auth/token endpoints.
- Не смешивать portal session-auth и bearer token-auth без явных guards/middleware.
