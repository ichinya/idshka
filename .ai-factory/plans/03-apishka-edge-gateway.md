# 03-apishka-edge-gateway

## Цель
Поставить перед `apishka-api` обязательный gateway, который сам проверяет токен
и прокидывает в upstream только доверенный auth-context.

## Что должно появиться
- OpenResty config для `api.apishka.ru`
- Lua middleware для JWT validation
- JWKS cache
- header sanitation/injection
- единый error contract `401/403`
- internal-only trust boundary до upstream

## Зависимости
- `01-platform-foundation`
- `02-issuer-jwt-core`

## Acceptance criteria
- валидный токен приводит к корректным `X-Idska-*` в upstream;
- просроченный, поддельный или audience-mismatch токен блокируется;
- входящие от клиента `X-Idska-*` никогда не попадают в upstream.

## Связь с roadmap
Поддерживает Milestone M2.
