# Architecture: Modular Monolith

## Overview
`idshka.ru` строится как **Laravel modular monolith**: один deployable backend содержит личный кабинет, вход через Socialite, site registry, token issuer, JWKS, OAuth/OIDC-like endpoints и audit. Для этого проекта это базовый паттерн, потому что домен сложный и security-sensitive, но текущий этап разработки и roadmap пока не дают оснований усложнять систему до microservices.

Подключённые сайты вроде `apishka.ru` и их gateway-конфиги остаются внешними consumers. Они могут жить как reference examples в репозитории, но не становятся частью core-домена `idshka.ru`. Репозиторий уже вышел из чистой `spec-first` стадии: в корне есть Laravel foundation, локальный Docker Compose/CI skeleton и базовые operational endpoints. При этом доменные модули пока существуют как skeleton-границы и документация, поэтому `docs/` и `.ai-factory/` по-прежнему остаются источником истины для business contracts следующих фаз.

## Decision Rationale
- **Project type:** identity provider, issuer и control plane для подключённых сайтов с двумя режимами интеграции: `api_resource` и `web_client`.
- **Tech stack:** PHP, Laravel, Socialite, PostgreSQL, Redis, Blade/Vite/Tailwind, OpenResty/Nginx + Lua.
- **Key factor:** домен содержит много auth/security инвариантов, поэтому нужны жёсткие модульные границы и явные контракты, но при этом проекту важнее сохранить простую разработку и одну точку деплоя.
- **Chosen pattern:** `Modular Monolith`.
- **Why not microservices:** пока нет доказанной потребности в независимом деплое, отдельных runtime-командах и отдельной эксплуатации для `Identity`, `Issuer`, `Sites` и `Portal`.
- **Why not pure layered architecture:** для такого домена простые горизонтальные слои слишком легко размывают границы между Socialite login, issuer logic, site registry и gateway contracts.

## Target Folder Structure
Это целевая структура modular monolith. На конец плана `01-laravel-platform-foundation` в коде уже существуют `routes/web.php`, `routes/api.php`, `routes/oauth.php`, контроллеры health/readiness, middleware `AssignRequestId`, Docker/OpenResty infra и placeholder-модули `app/Domain/*`; детальное наполнение `Actions/Services/Models` остаётся задачей следующих фаз.

```text
.ai-factory/
  DESCRIPTION.md
  ARCHITECTURE.md
  ROADMAP.md
  rules/
docs/
  API_FLOWS.md
  GATEWAY_CONTRACT.md
  LARAVEL_MODULES.md
  SOCIALITE.md
app/
  Contracts/
    Auth/
      JwtClaims.php
      GatewayHeaders.php
      SignedContext.php
      Scopes.php
  Domain/
    Identity/
      Actions/
      Events/
      Listeners/
      Models/
      Services/
    Sites/
      Actions/
      Events/
      Models/
      Services/
      Verification/
    ApiResources/
      Actions/
      Models/
      Policies/
      Services/
    OidcClients/
      Actions/
      Models/
      Services/
    Issuer/
      Actions/
      DTO/
      Models/
      Services/
    Audit/
      Actions/
      Events/
      Listeners/
  Http/
    Controllers/
      Auth/
      Portal/
      Api/
      OAuth/
    Middleware/
    Requests/
  Support/
bootstrap/
config/
database/
  migrations/
  seeders/
infra/
  docker/
  openresty/
    apishka/
      nginx.conf
      lua/
resources/
  views/
  js/
  css/
routes/
  web.php
  api.php
  oauth.php
examples/
  apishka-api/
  apishka-web-laravel/
tests/
  Feature/
  Unit/
```

## Dependency Rules
Основная идея: Laravel остаётся единым приложением, но каждый модуль имеет собственную зону ответственности и не лезет во внутренности соседнего модуля без явного public contract.

- ✅ `routes/*` и `App\Http\Controllers\*` только принимают HTTP-запрос, валидируют вход и вызывают `Actions` / `Services`.
- ✅ `App\Domain\Identity`, `Sites`, `ApiResources`, `OidcClients`, `Issuer`, `Audit` владеют своими инвариантами и внутренними моделями.
- ✅ `Issuer` может обращаться к `Sites`, `Identity`, `ApiResources`, `OidcClients` только через их публичные сервисы, contracts или DTO.
- ✅ `Audit` слушает domain events и не встраивает свои правила в каждый use case вручную.
- ✅ `App\Contracts\Auth\*` фиксирует внешний auth-контракт для claims, headers, signed context и scopes.
- ❌ Controllers, routes, Blade/Livewire components не должны содержать issuer logic, permission logic или key-management logic.
- ❌ Один модуль не должен читать/менять внутренние Eloquent-модели другого модуля напрямую, если для этого нет явного public API.
- ❌ Socialite callback не должен выпускать JWT, публиковать JWKS или подменять OAuth/OIDC provider-слой.
- ❌ `examples/` и `infra/` не должны зависеть от внутренних PHP-классов монолита; для них единственный контракт — HTTP/JWKS/docs.

## Layer/Module Communication
### Входные точки
- `routes/web.php` и `Controllers/Auth|Portal` обслуживают portal, login, consent и пользовательские страницы.
- `routes/api.php` и `Controllers/Api` обслуживают owner/user API для сайтов, токенов и клиентских настроек.
- `routes/oauth.php` и `Controllers/OAuth` обслуживают provider endpoints: authorize, token, userinfo, revoke, JWKS.

### Правило вызовов
- Один HTTP use case должен иметь один основной `Action` или `Service`, который собирает сценарий целиком.
- Межмодульные вызовы проходят через contracts, service interfaces, DTO или domain events.
- Побочные эффекты вроде аудита, уведомлений и реакций на security actions запускаются через events/listeners, а не копипастой в каждом controller.

### Внешние интеграции
- Gateway общается с `idshka.ru` только по documented endpoints: `/.well-known/*`, `/oauth/*`, owner/user API.
- `apishka-api` доверяет только gateway boundary, а не публичному клиенту.
- `apishka-web-laravel` общается с `idshka.ru` по Authorization Code + PKCE, а не через внутренние классы монолита.

## Core Flows
### Socialite login на `idshka.ru`
```text
User
  -> GET /auth/{provider}/redirect
  -> external provider
  -> GET /auth/{provider}/callback
  -> Identity module links provider account
  -> Laravel session for portal
```

Здесь Socialite отвечает только за внешний вход пользователя на `idshka.ru`. После callback система создаёт или находит локального пользователя и открывает обычную first-party Laravel session.

### API-only режим для `apishka.ru`
```text
Portal / user API
  -> Issuer issues JWT for aud=apishka.ru
  -> GET /oauth/jwks.json exposes public keys

Client
  -> api.apishka.ru gateway
  -> gateway validates JWT, sanitizes headers, adds trusted context
  -> upstream API reads trusted context only
```

Здесь `idshka.ru` только выпускает токен и публикует JWKS. Upstream не доверяет raw JWT напрямую; доверие появляется только после gateway validation.

### Web-client режим для `apishka.ru`
```text
Browser
  -> apishka.ru/login
  -> redirect to idshka.ru/oauth/authorize
  -> login + consent on idshka.ru
  -> callback on apishka.ru
  -> code exchange at /oauth/token
  -> local session on apishka.ru
```

Здесь `idshka.ru` выступает как provider/issuer. Обязательны strict redirect URI matching, `state`, `nonce` и `PKCE`.

## Key Principles
1. Один продукт и один deployable monolith до тех пор, пока обратное не доказано эксплуатацией.
2. `Contracts first`: claims, headers, scopes, error body и flow boundaries сначала фиксируются в contracts/docs, потом в коде.
3. `Fail closed`: при невалидной подписи, `aud`, `iss`, `exp`, `jti`, `state`, `nonce`, `PKCE` или происхождении headers запрос отклоняется.
4. Socialite решает только внешний login пользователя, а не issuer/provider-функции.
5. Подключённые сайты остаются внешними consumers; в core-монолите хранятся только их настройки, ключи и контракты интеграции.

## Code Examples
### Thin HTTP controller
```php
<?php

namespace App\Http\Controllers\Api;

use App\Domain\Sites\Actions\CreateSiteAction;
use App\Http\Requests\Api\CreateSiteRequest;
use Illuminate\Http\JsonResponse;

final class CreateSiteController
{
    public function __invoke(CreateSiteRequest $request, CreateSiteAction $action): JsonResponse
    {
        $site = $action->handle(
            ownerId: (string) $request->user()->getAuthIdentifier(),
            domain: $request->string('domain')->toString(),
            displayName: $request->string('display_name')->toString(),
        );

        return response()->json([
            'site_id' => $site->id,
            'domain' => $site->domain,
            'verified' => $site->isVerified(),
        ], 201);
    }
}
```

### Cross-module dependency through contracts
```php
<?php

namespace App\Domain\Issuer\Actions;

use App\Domain\ApiResources\Contracts\AudienceResolver;
use App\Domain\Issuer\Services\TokenIssuer;
use App\Domain\Sites\Contracts\VerifiedSiteLookup;

final class IssueUserApiTokenAction
{
    public function __construct(
        private VerifiedSiteLookup $sites,
        private AudienceResolver $audiences,
        private TokenIssuer $issuer,
    ) {
    }

    public function handle(string $userId, string $siteId, array $scopes): IssuedTokenData
    {
        $site = $this->sites->requireVerified($siteId);
        $audience = $this->audiences->forSite($site->id);

        return $this->issuer->issueUserApiToken(
            userId: $userId,
            siteId: $site->id,
            audience: $audience,
            scopes: $scopes,
        );
    }
}
```

Этот паттерн обязателен: `Issuer` получает данные о сайте через public contract, а не через прямой доступ к внутренней модели другого модуля.

## Anti-Patterns
- ❌ Класть JWT issue, JWKS publish или revoke logic внутрь Socialite callback/controller.
- ❌ Позволять controllers или Blade-компонентам напрямую менять токены, ключи, scopes и client secrets.
- ❌ Ходить из одного модуля в таблицы другого модуля напрямую, минуя public service/contract.
- ❌ Доверять входящим `X-Idshka-*` заголовкам от клиента или пускать upstream в интернет без gateway boundary.
- ❌ Делить проект на microservices до стабилизации contracts, flows и первой рабочей monolith-реализации.

## Security Boundaries
- Browser session на `idshka.ru` — first-party Laravel session.
- Socialite provider access tokens не используются как `idshka` API tokens.
- API-only JWT выпускает только `Issuer` внутри `idshka.ru`.
- Gateway всегда sanitizes `X-Idshka-*` и при необходимости добавляет signed context.
- Raw tokens, client secrets, authorization codes, private keys и provider refresh tokens никогда не логируются.
