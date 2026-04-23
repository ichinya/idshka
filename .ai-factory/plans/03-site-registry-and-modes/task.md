# Task

1. T1: Миграции `sites`/`site_verifications`/`site_modes` с уникальностью и индексами.
2. T2: Domain слой Sites (нормализация домена, статусы verify, режимы сайта).
3. T3: `POST /api/v1/sites` (валидация, авторизация, создание challenge).
4. T4: `POST /api/v1/sites/{site}/verify` (методы `dns_txt` и `file`, TTL/status transitions).
5. T5: DNS TXT checker service.
6. T6: `/.well-known` file checker service.
7. T7: Endpoints включения `api_resource` и `web_client` только для verified сайта.
8. T8: Fail-closed guard: unverified site не получает production credentials.
9. T9: Синхронизация API контракта в `docs/API_FLOWS.md`.
10. T10: Feature tests для happy-path и security gates.

## Dependencies
- `T3` depends on `T1`, `T2`
- `T4` depends on `T1`, `T2`, `T3`
- `T5` depends on `T4`
- `T6` depends on `T4`
- `T7` depends on `T4`, `T5`, `T6`
- `T8` depends on `T7`
- `T9` depends on `T3`, `T4`, `T7`, `T8`
- `T10` depends on `T3`, `T4`, `T5`, `T6`, `T7`, `T8`
