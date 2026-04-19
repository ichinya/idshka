# Area: socialite

- Socialite используется для входа пользователя на `idshka.ru` через внешних провайдеров.
- Каждый provider callback должен проверять `state` и обрабатывать отказ пользователя.
- Таблица `social_accounts` хранит `provider`, `provider_user_id`, `user_id`, email/name/avatar snapshots.
- Access/refresh tokens внешнего провайдера не логировать. Хранить только если действительно нужен дальнейший API-доступ, и только зашифрованно.
- Account linking требует активной session пользователя.
- Если email совпадает, не объединять аккаунты молча без политики подтверждения.
- VK/Yandex оформлять как provider adapters; не размазывать provider-specific код по controllers.
- Socialite не использовать для выпуска токенов `idshka.ru` — issuer отдельный domain.
