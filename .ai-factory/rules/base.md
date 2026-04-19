# Project Rules: Base

> Базовые правила проекта на текущем этапе. Репозиторий пока spec-first: основной источник истины здесь — docs и `.ai-factory` артефакты, а не готовый Laravel код.

## Coding Standards

### Language & Style
- **Primary implementation language:** PHP для Laravel modular monolith; вспомогательные артефакты ведутся в Markdown, gateway reference ожидается на OpenResty/Nginx + Lua.
- **Style guide:** для PHP-кода держаться Laravel conventions и PSR-12; текст артефактов писать на русском, а классы, роуты, claims, scopes, headers и другие protocol terms оставлять на английском.
- **Formatting:** в репозитории пока нет зафиксированного formatter tool, поэтому стиль должен оставаться framework-native и консистентным с соседними файлами.

### Naming Conventions
- **Functions/Methods:** `camelCase`, с явным глаголом по доменной операции, например `createSite`, `verifyDomain`, `issueToken`.
- **Classes/Types:** `PascalCase` с понятной ролью из текущих docs, например `*Controller`, `*Action`, `*Service`, `*Checker`, `*Listener`.
- **Files:** один PHP class на файл с именем класса; Markdown-файлы именовать явно и в стиле существующих документов (`API_FLOWS.md`, `GATEWAY_CONTRACT.md`).
- **Constants:** PHP constants — `UPPER_SNAKE_CASE`; стандартизованные protocol keys не переименовывать (`iss`, `aud`, `jti`, `X-Idshka-*`).

## Architecture Constraints

### Module Boundaries
- Доменные правила и security-sensitive logic держать в `app/Domain/*`; HTTP controllers и routes должны оставаться тонкими координаторами.
- Внешние consumers (`apishka.ru`, gateway examples, callback adapters) не смешивать с core-продуктом `idshka.ru`; для них допустимы только examples/adapters/docs, а не отдельный бизнес-backend в этом репозитории.
- Любой межсервисный контракт сначала фиксировать в docs и Laravel contracts/value objects: JWT claims, gateway headers, signed context, error body, redirect/verification flow.

### Dependency Rules
- Проект остаётся Laravel-first: не добавлять отдельный Node/Fastify/Next backend или параллельный auth stack без явного архитектурного решения.
- Laravel Socialite использовать только для входа пользователя через внешних провайдеров; issuer, JWKS, authorization code, token, revoke и introspection остаются отдельными Laravel domains.

## Error Handling

### Required Patterns
- Auth и gateway логика работает по fail-closed: при любой неопределённости по подписи, `iss`, `aud`, `exp`, `nbf`, `jti`, `state`, `nonce`, `PKCE`, redirect URI или источнику `X-Idshka-*` запрос отклоняется.
- Ошибки API и gateway должны быть детерминированными: `401` для проблем аутентификации, `403` для недостатка прав, с телом, где есть как минимум `error`, `message`, `request_id`.

### Logging Requirements
- **Log level for errors:** `error`.
- **Log level for warnings:** `warning`.
- **Required context in logs:** всегда `request_id`; при наличии доменного контекста добавлять `user_id`, `site_id`, `aud`, `jti`, provider/client identifiers.
- Никогда не логировать raw JWT, `client_secret`, authorization code, provider access/refresh tokens, private keys и другие секреты.

## Comments & Documentation

### When Comments Are Required
- Короткий комментарий обязателен там, где без него неочевиден security-critical flow: подпись/проверка токена, ротация ключей, PKCE/state/nonce checks, sanitization `X-Idshka-*`, signed context calculation.
- Если задача меняет внешний flow или контракт, в том же изменении обновлять релевантные docs в `docs/` и `.ai-factory/`.

### Documentation Standards
- Пока проект остаётся spec-first, docs и AI Factory artifacts должны обновляться раньше или одновременно с кодом, если меняются сценарии, контракты или архитектурные границы.
- Имена файлов, классов, эндпоинтов, claims, scopes, permissions и headers сохранять на английском даже в русскоязычных артефактах.

## Testing Requirements

### Minimum Coverage
- Числовой coverage target в репозитории пока не зафиксирован.
- Любое изменение auth-flow, token issuance, site verification, gateway contract или security policy должно иметь test, smoke scenario, curl proof или другой явный evidence.

### Required Test Types
- Feature/integration tests для login redirect/callback, token issuance, revoke, domain verification и owner/user HTTP flows.
- Unit tests для доменных сервисов: claims building, PKCE, signing keys, header/context mapping, permission/policy evaluation.
- Smoke tests или curl-проверки для gateway examples и межсервисных контрактов.

## Prohibited Patterns

### Never Do
- Не доверять публичным `X-Idshka-*` headers от клиента и не переносить ответственность за первичную auth-проверку на upstream, если по сценарию нужен gateway boundary.
- Не использовать Socialite как issuer/token server и не прятать protocol contracts в ad hoc controller logic без docs, contracts и tests.
- Не допускать implicit privilege через fallback roles, отсутствующие claims или permissive defaults.
