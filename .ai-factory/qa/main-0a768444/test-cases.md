## Test Cases: Portal Token and Client Management

---

### TC-001: Владелец открывает портал

**Priority:** High
**Type:** Positive

**Предусловие:**

Owner A авторизован в браузерной сессии.

**Шаги:**

1. Открыть `/portal`.
2. Проверить видимые секции dashboard.

**Ожидаемый результат:**

Страница открывается успешно и показывает секции сайтов, API tokens, web clients и audit. На странице видны только данные Owner A.

**Тестовые данные:**

```text
Owner A: owner-a@example.test
URL: /portal
```

---

### TC-002: Гость не может открыть портал

**Priority:** High
**Type:** Negative

**Предусловие:**

В браузере нет активной authenticated session.

**Шаги:**

1. Очистить cookies/session.
2. Открыть `/portal`.

**Ожидаемый результат:**

Приложение не показывает dashboard портала. Пользователь перенаправляется на authentication flow или получает ответ, требующий авторизацию.

**Тестовые данные:**

```text
URL: /portal
```

---

### TC-003: Владелец создает сайт и видит инструкции верификации

**Priority:** High
**Type:** Positive

**Предусловие:**

Owner A авторизован и еще не имеет сайта `example.test`.

**Шаги:**

1. Открыть `/portal`.
2. В блоке `New site` ввести domain `https://Example App.ru/path?query=1`.
3. Ввести display name `Example App`.
4. Отправить форму.
5. Проверить карточку созданного сайта.

**Ожидаемый результат:**

Dashboard показывает normalized domain `example.test`, DNS TXT host `_idshka.example.test`, значение с префиксом `idshka-site-verification=`, и file URL `https://example.test/.well-known/idshka-site-verification.txt`.

**Тестовые данные:**

```text
Domain: https://Example App.ru/path?query=1
Display name: Example App
```

---

### TC-004: Неверифицированный сайт не получает production modes

**Priority:** High
**Type:** Negative

**Предусловие:**

У Owner A есть новый сайт со статусом verification `pending`.

**Шаги:**

1. Открыть `/portal`.
2. В карточке pending site нажать `API resource`.
3. Повторить действие для `Web client`.

**Ожидаемый результат:**

Оба запроса отклоняются. Сайт остается unverified, режимы `api_resource` и `web_client` не добавляются.

**Тестовые данные:**

```text
Site: pending example.test
Modes: api_resource, web_client
```

---

### TC-005: Верифицированный владелец включает оба режима сайта

**Priority:** High
**Type:** Positive

**Предусловие:**

У Owner A есть verified site `example.test`.

**Шаги:**

1. Открыть `/portal`.
2. В карточке verified site нажать `API resource`.
3. Убедиться, что страница показывает `API resource enabled`.
4. Нажать `Web client`.
5. Убедиться, что страница показывает `Web client enabled`.

**Ожидаемый результат:**

Карточка сайта показывает оба режима: `api_resource` и `web_client`. Повторное выполнение того же действия не создает duplicate visible mode entries.

**Тестовые данные:**

```text
Site: verified example.test
Modes: api_resource, web_client
```

---

### TC-006: Владелец выпускает API token и видит raw token один раз

**Priority:** High
**Type:** Positive

**Предусловие:**

У Owner A есть verified `example.test` с включенным режимом `api_resource`, и в системе есть active signing key.

**Шаги:**

1. Открыть `/portal`.
2. В секции `API tokens` выбрать `example.test`.
3. Ввести scopes `orders.read`.
4. Ввести permissions `orders.read`.
5. Нажать `Issue token`.
6. Скопировать или отметить отображенный raw bearer token.
7. Снова открыть `/portal` в той же сессии.

**Ожидаемый результат:**

Сразу после создания страница показывает raw JWT-like bearer token и metadata токена. При повторном открытии dashboard raw token больше не виден; остаются только metadata: audience, `jti`, expiry и revoke state.

**Тестовые данные:**

```text
Site: verified example.test with api_resource
Scopes: orders.read
Permissions: orders.read
```

---

### TC-007: Выпуск API token отклоняется для неeligible site

**Priority:** High
**Type:** Negative

**Предусловие:**

У Owner A есть один pending site и один verified site без режима `api_resource`.

**Шаги:**

1. Открыть `/portal`.
2. Попробовать выпустить API token для pending site.
3. Попробовать выпустить API token для verified site без режима `api_resource`.

**Ожидаемый результат:**

Оба запроса завершаются user-visible error. Raw token не отображается, active token не появляется в списке API tokens.

**Тестовые данные:**

```text
Pending site: pending.example.test
Verified site without api_resource: web-only.example.test
Scopes: orders.read
Permissions: orders.read
```

---

### TC-008: Revoke API token требует явного подтверждения

**Priority:** High
**Type:** Negative / Positive

**Предусловие:**

У Owner A есть active API token в списке `/portal`.

**Шаги:**

1. Оставить поле revoke confirmation пустым и нажать `Revoke`.
2. Проверить, что token все еще active.
3. Ввести `yes` и нажать `Revoke`.
4. Проверить, что token все еще active.
5. Ввести точное значение `revoke` и нажать `Revoke`.

**Ожидаемый результат:**

Пустое значение и `yes` отклоняются и не revoke-ят token. Точное значение `revoke` отзывает token, в списке отображается revoked state.

**Тестовые данные:**

```text
Invalid confirmations: empty, yes
Valid confirmation: revoke
```

---

### TC-009: Владелец создает OIDC web client и видит secret один раз

**Priority:** High
**Type:** Positive

**Предусловие:**

У Owner A есть verified `example.test` с включенным режимом `web_client`.

**Шаги:**

1. Открыть `/portal`.
2. В секции `Web clients` выбрать `example.test`.
3. Ввести name `Example Web`.
4. Ввести redirect URI `https://example.test/auth/idshka/callback`.
5. Нажать `Create client`.
6. Отметить отображенные `client_id` и `client_secret`.
7. Снова открыть `/portal`.

**Ожидаемый результат:**

Сразу после создания страница показывает client secret. При повторном открытии dashboard raw secret больше не виден; список clients показывает только client id, site, redirect URI и active/revoked state.

**Тестовые данные:**

```text
Client name: Example Web
Redirect URI: https://example.test/auth/idshka/callback
```

---

### TC-010: Создание OIDC client отклоняется для неeligible site

**Priority:** High
**Type:** Negative

**Предусловие:**

У Owner A есть один pending site и один verified site без режима `web_client`.

**Шаги:**

1. Открыть `/portal`.
2. Попробовать создать OIDC client для pending site.
3. Попробовать создать OIDC client для verified site без режима `web_client`.

**Ожидаемый результат:**

Оба запроса завершаются user-visible error. Client id и client secret не отображаются, новый web client не появляется в списке.

**Тестовые данные:**

```text
Pending site: pending.example.test
Verified site without web_client: api-only.example.test
Redirect URI: https://example.test/auth/idshka/callback
```

---

### TC-011: Redirect URI принимает exact HTTPS и отклоняет unsafe values

**Priority:** High
**Type:** Negative / Positive

**Предусловие:**

У Owner A есть active OIDC client.

**Шаги:**

1. Добавить redirect URI `https://example.test/auth/idshka/tenant/callback`.
2. Добавить redirect URI `http://example.test/auth/idshka/callback`.
3. Добавить redirect URI `https://*.example.test/auth/idshka/callback`.
4. Добавить malformed redirect URI `not-a-url`.

**Ожидаемый результат:**

Exact HTTPS URI принимается и появляется под client. `http`, wildcard и malformed values отклоняются и не появляются в списке redirect URI.

**Тестовые данные:**

```text
Valid: https://example.test/auth/idshka/tenant/callback
Invalid: http://example.test/auth/idshka/callback
Invalid: https://*.example.test/auth/idshka/callback
Invalid: not-a-url
```

---

### TC-012: Duplicate redirect URI не создает duplicate visible entries

**Priority:** Medium
**Type:** Edge case

**Предусловие:**

У Owner A есть active OIDC client с redirect URI `https://example.test/auth/idshka/callback`.

**Шаги:**

1. Открыть `/portal`.
2. Повторно добавить тот же redirect URI `https://example.test/auth/idshka/callback`.
3. Проверить список redirect URI у client.
4. Проверить recent audit entries для redirect URI addition.

**Ожидаемый результат:**

Список redirect URI содержит одну visible entry для exact URI. Portal не показывает misleading duplicate audit activity для idempotent duplicate add.

**Тестовые данные:**

```text
Redirect URI: https://example.test/auth/idshka/callback
```

---

### TC-013: Revoke OIDC client требует явного подтверждения

**Priority:** High
**Type:** Negative / Positive

**Предусловие:**

У Owner A есть active OIDC client в списке `/portal`.

**Шаги:**

1. Оставить поле client revoke confirmation пустым и нажать `Revoke`.
2. Проверить, что client остается active.
3. Ввести `delete` и нажать `Revoke`.
4. Проверить, что client остается active.
5. Ввести точное значение `revoke` и нажать `Revoke`.

**Ожидаемый результат:**

Пустое значение и `delete` отклоняются и не revoke-ят client. Точное значение `revoke` отзывает client, в списке отображается revoked state.

**Тестовые данные:**

```text
Invalid confirmations: empty, delete
Valid confirmation: revoke
```

---

### TC-014: Чужой владелец не может управлять ресурсами другого владельца

**Priority:** High
**Type:** Security / Negative

**Предусловие:**

У Owner A есть verified site, active API token и active OIDC client. Owner B - отдельный authenticated user.

**Шаги:**

1. Войти как Owner B.
2. Открыть `/portal` и убедиться, что ресурсы Owner A не отображаются.
3. Отправить direct POST на URL включения mode для сайта Owner A.
4. Отправить direct POST на URL revoke API token Owner A с `confirm=revoke`.
5. Отправить direct POST на URL добавления redirect URI к client Owner A.
6. Отправить direct POST на URL revoke client Owner A с `confirm=revoke`.

**Ожидаемый результат:**

Owner B не видит и не изменяет ресурсы Owner A. Ответы не раскрывают полезную metadata об объектах Owner A. Site, token и client Owner A остаются без изменений.

**Тестовые данные:**

```text
Owner A site: example.test
Owner B: owner-b@example.test
Confirmation: revoke
Foreign redirect attempt: https://evil.example.test/callback
```

---

### TC-015: Audit table записывает lifecycle events без secrets

**Priority:** High
**Type:** Security / Positive

**Предусловие:**

Owner A выполнил site creation, mode enablement, API token issue, OIDC client creation и redirect URI addition.

**Шаги:**

1. Открыть `/portal`.
2. Проверить таблицу `Audit`.
3. Проверить детали event через доступный database/admin view.
4. Найти в event metadata raw bearer token и raw client secret values, сохраненные при создании.

**Ожидаемый результат:**

Portal показывает actions `site.connected`, `site.mode_enabled`, `issuer.user_api_token_issued`, `oidc.client_created` и `oidc.redirect_uri_added`. Metadata содержит только identifiers, hashes или timestamps; raw bearer token и raw client secret отсутствуют.

**Тестовые данные:**

```text
Expected actions:
site.connected
site.mode_enabled
issuer.user_api_token_issued
oidc.client_created
oidc.redirect_uri_added
Forbidden values:
raw JWT from TC-006
raw client_secret from TC-009
```

---

### TC-016: Revoked OIDC client не принимает новый redirect URI

**Priority:** Medium
**Type:** Negative

**Предусловие:**

У Owner A есть revoked OIDC client.

**Шаги:**

1. Открыть `/portal`.
2. В revoked client отправить новый redirect URI `https://example.test/auth/idshka/after-revoke`.

**Ожидаемый результат:**

Запрос отклоняется. Новый redirect URI не добавляется к client.

**Тестовые данные:**

```text
Revoked client: Example Web
Redirect URI: https://example.test/auth/idshka/after-revoke
```

---

### TC-017: Existing OIDC web login работает с portal-created client

**Priority:** Medium
**Type:** Regression

**Предусловие:**

У Owner A есть active OIDC client, созданный через portal, с redirect URI `https://example.test/auth/idshka/callback`. Test user может пройти first-party login.

**Шаги:**

1. Запустить authorize flow с portal-created `client_id` и registered redirect URI.
2. Пройти first-party login, если он потребуется.
3. Убедиться, что произошел redirect на `https://example.test/auth/idshka/callback`.
4. Обменять полученный authorization code, используя one-time client secret из момента создания client.
5. Запросить userinfo с полученным web access token.

**Ожидаемый результат:**

Authorize возвращает code на exact registered redirect URI, token exchange проходит с valid client credentials, userinfo возвращает claims, разрешенные granted scopes.

**Тестовые данные:**

```text
Client id: portal-created client_id from TC-009
Client secret: one-time secret from TC-009
Redirect URI: https://example.test/auth/idshka/callback
Scopes: openid profile email
```

---

### TC-018: Dashboard остается usable с длинными значениями

**Priority:** Low
**Type:** Edge case

**Предусловие:**

У Owner A есть хотя бы один API token и один OIDC client.

**Шаги:**

1. Добавить redirect URI, близкий к configured maximum length, используя HTTPS и без wildcard.
2. Выпустить API token с несколькими allowed scopes и permissions.
3. Открыть `/portal` на desktop width.
4. Открыть `/portal` на narrow mobile-sized viewport.

**Ожидаемый результат:**

Long token metadata, `jti`, redirect URI и audit rows остаются читаемыми. Они не перекрывают соседние controls и не делают revoke forms unusable.

**Тестовые данные:**

```text
Long redirect URI example:
https://example.test/auth/idshka/callback/tenant/very-long-path-segment-001/very-long-path-segment-002
Scopes: orders.read profile.read
Permissions: orders.read invoices.read
```

## Test Data (based on test design techniques)

### Positive

* Owner A: `owner-a@example.test`
* Site domain: `https://Example App.ru/path?query=1`
* Normalized domain: `example.test`
* API scopes: `orders.read`
* API permissions: `orders.read`
* Web client name: `Example Web`
* Primary redirect URI: `https://example.test/auth/idshka/callback`
* Additional redirect URI: `https://example.test/auth/idshka/tenant/callback`
* Revoke confirmation: `revoke`

### Negative

* Owner B: `owner-b@example.test`
* Non-HTTPS redirect URI: `http://example.test/auth/idshka/callback`
* Wildcard redirect URI: `https://*.example.test/auth/idshka/callback`
* Malformed redirect URI: `not-a-url`
* Invalid revoke confirmations: empty value, `yes`, `delete`
* Ineligible sites: pending site, verified site without `api_resource`, verified site without `web_client`
