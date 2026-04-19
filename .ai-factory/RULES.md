# RULES

## Иерархия правил
1. План-локальные правила: `.ai-factory/plans/<plan-id>/rules.md`
2. Area rules из `config.yaml -> rules.*`
3. Base rules: `.ai-factory/rules/base.md`
4. Global axioms: этот файл

Если правила конфликтуют, приоритет выше у более локального уровня.

## Глобальные аксиомы проекта
1. **Auth только через edge.** Upstream не принимает решения на основе публичного JWT напрямую.
2. **Fail closed.** Если gateway не уверен в подписи, audience, expiry или источнике заголовков — запрос отклоняется.
3. **Contracts first.** Любое изменение claims, scopes, headers или error body сначала фиксируется в `packages/contracts`.
4. **No implicit privilege.** Ни роль, ни отсутствие поля не должны молча расширять права.
5. **Sanitize before trust.** Все `X-Idska-*` от клиента удаляются, новые заголовки создаёт только gateway.
6. **Audit by default.** Выдача токена, revoke, ошибка auth и ключевая смена policy попадают в аудит.
7. **Version the surface.** API и контракты имеют явную версию; breaking changes — только через план и changelog.
8. **Minimize secrets exposure.** Приватные ключи, raw tokens и служебные секреты не пишутся в логи и не попадают в UI повторно.
9. **Short-lived over clever revoke.** Для прямых JWT всегда предпочитать короткий TTL вместо сложной магии.
10. **Deterministic errors.** `401` — проблема аутентификации, `403` — токен валиден, но прав недостаточно.

## Карта area rules
- `issuer` — выпуск токенов, ключи, аудит, site registry
- `gateway` — OpenResty/Nginx, локальная валидация, header injection
- `api` — upstream contract, permission checks, error semantics
- `portal` — личный кабинет, UX и self-service flows
- `security` — криптография, revoke, rate limits, угрозы
- `ops` — observability, deployment, env, rollback

## Что считается завершением задачи
Задача считается завершённой только если:
- код/конфиг внесён;
- контракты и документация обновлены при необходимости;
- есть evidence: curl, лог, тест, screenshot или краткое proof note;
- roadmap/plan status может быть обновлён без додумывания.
