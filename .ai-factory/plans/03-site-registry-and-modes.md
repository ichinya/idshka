# 03-site-registry-and-modes

## Цель
Сделать подключение сайтов, верификацию домена и явное включение режимов `api_resource`/`web_client`
с fail-closed поведением для невалидных или не подтвержденных доменов.

## Area
`site_registry`, `laravel`, `portal`

## Зависимости
- `02-user-auth-socialite`

## Refined Checklist
- [x] **T1. Миграции и инварианты домена Sites**
  - Добавить migrations для `sites`, `site_verifications`, `site_modes`.
  - Зафиксировать инварианты:
    - `sites.owner_user_id + normalized_domain` unique;
    - verified domain не может быть привязан к двум владельцам;
    - verification challenge хранит `token`, `method`, `expires_at`, `status`.
  - Добавить индексы по `normalized_domain`, `site_id`, `status`, `expires_at`.

- [x] **T2. Site модели и слой нормализации домена**
  - Добавить модели/enum/value objects в `App\Domain\Sites\*`:
    - нормализация домена (lowercase + punycode + удаление scheme/path/query);
    - статус верификации (`pending`, `verified`, `failed`, `expired`);
    - режимы сайта (`api_resource`, `web_client`) через explicit allow-list.
  - Подготовить сервис выдачи verification instructions для DNS TXT и `/.well-known/`.

- [x] **T3. POST /api/v1/sites (create site + challenge)**
  - Добавить route и контроллер для `POST /api/v1/sites`.
  - Добавить request validation (`domain`, `display_name`) + owner authorization.
  - Возвращать shape из `docs/API_FLOWS.md`:
    - `site_id`, `domain`, `verified=false`, `verification.dns_txt_*`, `verification.file_*`.
  - Логировать событие connect без утечки секретов challenge.
  - Зависит от: `T1`, `T2`.

- [x] **T4. POST /api/v1/sites/{site}/verify (dns_txt/file)**
  - Добавить route, request, controller + owner policy.
  - Поддержать методы `dns_txt` и `file` с одинаковым контрактом ошибки.
  - Проверить TTL challenge и корректно переводить status (`verified`/`failed`/`expired`).
  - Писать audit/event о результате verify.
  - Зависит от: `T1`, `T2`, `T3`.

- [x] **T5. Проверка DNS TXT**
  - Реализовать DNS checker service с timeout/retry policy и явным error mapping.
  - Сравнивать только ожидаемое значение `idshka-site-verification=<token>`.
  - При сетевых ошибках возвращать deterministic domain error (без raw stack).
  - Зависит от: `T4`.

- [x] **T6. Проверка `/.well-known` файла**
  - Реализовать HTTP checker service для
    `https://<domain>/.well-known/idshka-site-verification.txt`.
  - Проверять status code, content-type tolerance и exact body token match.
  - Блокировать redirect на посторонние host/scheme.
  - Зависит от: `T4`.

- [x] **T7. Endpoints включения режимов сайта**
  - Добавить endpoints включения `api_resource` и `web_client`.
  - Разрешать включение только для `verified` сайта.
  - Повторное включение того же режима должно быть idempotent.
  - Подготовить межмодульный контракт (lookup verified site) для планов `04` и `06`.
  - Зависит от: `T4`, `T5`, `T6`.

- [x] **T8. Fail-closed guard для production credentials**
  - Добавить централизованную проверку: unverified site не может получать production credentials.
  - Зафиксировать правило в domain service/contract, чтобы следующий issuer/web-client flow
    не обходил ограничение через контроллеры.
  - Зависит от: `T7`.

- [x] **T9. API/contract документация**
  - Обновить `docs/API_FLOWS.md` по фактическим request/response/error payload.
  - При изменении публичного контракта синхронизировать релевантные sections в docs.
  - Зависит от: `T3`, `T4`, `T7`, `T8`.

- [x] **T10. Feature tests (контракт + security)**
  - Добавить feature tests для:
    - create site;
    - verify via dns/file;
    - mode enable после verify;
    - запрет production credentials для unverified site;
    - запрет cross-owner access к чужому site.
  - Покрыть deterministic HTTP коды (`401`/`403`/`422`) по правилам проекта.
  - Зависит от: `T3`, `T4`, `T5`, `T6`, `T7`, `T8`.

## Acceptance criteria
- [x] можно создать site `example.test` и получить verification instructions (dns/file)
- [x] DNS/file verification меняет status на `verified`
- [x] можно включить `api_resource` и `web_client` только после верификации
- [x] неверифицированный домен не получает production credentials
- [x] verified domain не может быть зарегистрирован вторым владельцем без transfer flow
- [x] API контракты и tests подтверждают fail-closed поведение

## Связь с roadmap
См. `.ai-factory/ROADMAP.md`.

