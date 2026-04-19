# RESEARCH

## Близкие паттерны
Проект похож на упрощённую комбинацию:
- identity provider / token issuer;
- developer portal для подключённых сайтов;
- forward-auth / edge-auth gateway;
- OIDC provider для web login.

## Принятые архитектурные решения

### ADR-001: два режима сайта
Один подключённый домен может быть:
- `api_resource`: принимает bearer tokens через gateway;
- `web_client`: использует idshka.ru для браузерного входа;
- оба режима одновременно.

### ADR-002: локальная проверка JWT на gateway как основной путь
Для API-only режима основной путь — gateway проверяет JWT локально по JWKS. Это снижает latency и не делает `idshka.ru` синхронной зависимостью каждого API-запроса.

### ADR-003: introspection как fallback
Online introspection нужен для hard revoke, инцидентов и opaque tokens в будущем. Он не является обязательным hot path в первом MVP.

### ADR-004: signed upstream context опционален, но поддерживается контрактом
Если gateway и upstream в одной защищённой сети, достаточно sanitized headers. Если граница слабее, gateway добавляет HMAC-подписанный context envelope.

## Открытые вопросы
- Нужен ли MVP сразу с refresh tokens для web-client или достаточно короткой сессии `apishka.ru`?
- Будут ли scopes задаваться владельцем сайта вручную или через шаблоны?
- Требуется ли поддержка wildcard subdomains для redirect URI и gateway origins?
- Какой max TTL разрешить для прямых user API JWT?
