# SECURITY

## Основные угрозы
- Подделка `X-Idshka-*` заголовков клиентом.
- JWT replay в течение TTL.
- Audience confusion: токен для одного сайта используется на другом.
- Подмена redirect URI в web-client режиме.
- Утечка client_secret или private signing key.
- Логирование raw tokens.
- Неправильная key rotation.

## Базовые меры
- Удалять входящие `X-Idshka-*` на gateway.
- Проверять `iss`, `aud`, `exp`, `nbf`, `kid`, `alg`, подпись.
- Использовать strict redirect URI matching.
- Требовать state, nonce, PKCE для web login.
- Хранить client secrets только в hash/encrypted form по назначению.
- Private keys хранить вне репозитория, в secret store/env volume.
- Raw token показывать только один раз.
- Добавить audit events для всех auth/security изменений.
- Fail closed при недоступности критичного auth state.
