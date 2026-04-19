# Portal Area Rules

## Scope
`idska-portal`, self-service UI, token creation/revoke UX, site onboarding UI.

## Правила
- Создание токена всегда требует label, audience и expiry.
- Raw token показывается один раз и только сразу после выпуска.
- После перезагрузки страницы raw token недоступен, остаётся только metadata.
- Для revoke UI должен показывать подтверждение и последствия.
- Scope selector должен быть понятным: технические permissions + human-readable descriptions.
- Все dangerous actions (revoke all, rotate key, broad scope) визуально выделяются.

## UX нормы
- Пользователь видит: label, audience, created_at, expires_at, last_used_at, status.
- Для каждой consumer-аудитории есть готовый snippet вызова API.
- Ошибки form submission показывают machine code и понятное объяснение.

## Запрещено
- Показывать raw token повторно.
- Прятать scope/permission expansion за "умными" дефолтами.
- Делать destructive action без explicit confirm.
