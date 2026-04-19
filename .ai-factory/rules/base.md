# Area: base

## Общие правила разработки
- Сначала обновляй contracts, потом реализацию.
- Любой публичный endpoint должен иметь schema validation.
- Любой auth/security endpoint должен иметь audit event.
- Ошибки возвращать в едином формате из `packages/contracts`.
- Не смешивать API-only и web-client flow в одном обработчике без явного mode check.
- Не добавлять новую внешнюю зависимость без причины в plan evidence.
- Не логировать raw secrets.
- Все timestamps хранить в UTC.
- Все ids должны быть стабильными и пригодными для audit/correlation.

## Naming
- Сервис называется `idshka.ru`.
- Пример подключённого сайта: `apishka.ru`.
- Headers имеют префикс `X-Idshka-*`.
- Internal modules используют `idshka`, не старое ошибочное написание.

## Definition of done
- Есть код/конфиг.
- Есть тест или smoke evidence.
- Есть обновление docs/contracts, если менялась внешняя поверхность.
- Плановый статус можно обновить.
