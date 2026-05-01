# Архитектура: Modular Monolith

## Обзор
`idshka.ru` строится как Laravel-first модульный монолит: один деплой, одна основная база данных и строгие границы предметных модулей внутри приложения. Такой подход сохраняет скорость разработки и простоту эксплуатации, но не превращает identity provider, issuer и control plane в набор слабо связанных контроллеров.

Модульный монолит здесь выбран осознанно: в проекте есть несколько доменов с разными правилами (`Identity`, `Sites`, `Issuer`, `OidcClients`, `ApiResources`, `Audit`), но им нужна согласованная транзакционная модель, единая авторизация Laravel и простой локальный запуск через Docker Compose. Микросервисы на текущем этапе добавили бы сетевые контракты, распределенные транзакции и операционную сложность раньше, чем появится реальная потребность в независимом масштабировании.

## Обоснование выбора
- **Тип проекта:** identity provider, OAuth/OIDC-like issuer и control plane для подключенных сайтов.
- **Технический стек:** PHP 8.5, Laravel 13, PostgreSQL, Redis, Blade/Vite/Tailwind, OpenResty/Nginx gateway examples.
- **Ключевой фактор:** security-sensitive бизнес-правила требуют явных доменных границ, но продукту выгодны один deploy unit, одна база и Laravel ecosystem.

## Структура директорий
Структура расширяет стандартный Laravel layout, не заменяя его:

```text
app/
├── Contracts/
│   └── Auth/                  # Общие protocol contracts: claims, scopes, permissions, headers
├── Domain/
│   ├── ApiResources/          # Проверка доступа API-resource сайтов
│   ├── Audit/                 # Аудит доменных событий
│   ├── Identity/              # Пользовательская идентичность и Socialite account linking
│   ├── Issuer/                # JWT/JWKS, authorization codes, revoke, PKCE, token validation
│   ├── OidcClients/           # OIDC/web-client registry и redirect URI rules
│   └── Sites/                 # Site registry, verification, modes
├── Http/
│   ├── Controllers/           # Тонкий транспортный слой
│   ├── Middleware/            # Request id, security headers, probe protection, throttling
│   └── Requests/              # HTTP validation и deterministic error shape
├── Models/                    # Shared Laravel models, когда модель не живет внутри домена
├── Policies/                  # Laravel authorization policies
└── Providers/                 # Composition root, DI, events, framework wiring

config/                        # Laravel config, включая issuer/services/logging/rate limits
database/
├── migrations/                # Единая relational schema
├── factories/
└── seeders/
routes/
├── web.php                    # Browser auth и Socialite endpoints
├── api.php                    # Owner/user API endpoints
├── oauth.php                  # OAuth/OIDC-like endpoints
└── probes.php                 # Health/readiness probes
docs/                          # Human-facing contracts and guides
openspec/
├── specs/                     # Source of truth для external behavior requirements
└── changes/                   # Active and archived implementation changes
examples/                      # Reference consumers/adapters outside the core product
infra/                         # Docker/OpenResty/runtime integration assets
tests/
├── Feature/                   # Full flow tests
└── Unit/                      # Domain/service tests
```

## Правила зависимостей
Разрешено:

- `routes/*` и `app/Http/Controllers/*` вызывают доменные `Actions`/`Services`, FormRequest-классы валидируют вход, policies проверяют доступ.
- Домены внутри `app/Domain/*` могут использовать Laravel primitives (`DB`, `Log`, `config`, events), Eloquent models своего домена и общие contracts/DTO.
- `app/Contracts/*` содержит стабильные protocol-level constants и value definitions, которые могут использовать разные домены.
- `Audit` слушает доменные события через Laravel events/listeners, не встраивая аудит вручную в каждый controller.
- `docs/` и `openspec/specs/` обновляются вместе с кодом при изменении внешнего flow, claims, headers, scopes, permissions или error shape.

Запрещено:

- Не размещать бизнес-логику Issuer/Sites/Identity напрямую в controllers, routes, Blade views или middleware.
- Не делать циклические доменные зависимости: если двум доменам нужен общий язык, использовать contract, DTO, event или явный service boundary.
- Не использовать Laravel Socialite как token issuer или JWKS/authorization server. Socialite отвечает только за login/linking через внешних провайдеров.
- Не добавлять отдельный Node/Fastify/Next backend для core auth/control-plane без отдельного архитектурного решения.
- Не смешивать внешний consumer `apishka.ru` с core-продуктом `idshka.ru`; consumer-код живет в `examples/`, `infra/` или docs.

## Взаимодействие модулей и слоев
- **HTTP entrypoint:** route -> middleware/policy -> FormRequest -> controller -> доменный Action/Service -> response.
- **Синхронные доменные операции:** Actions координируют транзакции и вызывают сервисы внутри своего bounded context.
- **Междоменные side effects:** домен публикует событие, `Audit` или другой listener реагирует без обратной зависимости.
- **Protocol contracts:** claims, scopes, permissions, headers, request/response shapes фиксируются в `app/Contracts/Auth`, OpenSpec specs и docs.
- **Gateway boundary:** OpenResty/Nginx examples проверяют JWT/JWKS, удаляют клиентские `X-Idshka-*` и передают upstream только нормализованный доверенный контекст.

## Ключевые принципы
1. **Laravel-first:** стандартные механизмы Laravel использовать как composition/runtime layer; не дублировать framework через параллельный stack.
2. **Толстый домен, тонкий транспорт:** controllers не принимают security-sensitive решений, а только переводят HTTP в доменный вызов и обратно.
3. **Fail closed:** любые сомнения в подписи, audience, issuer, TTL, redirect URI, PKCE, state/nonce или источнике headers приводят к отказу.
4. **Contracts first:** внешние claims, scopes, gateway headers, OAuth errors и callback flows сначала фиксируются в contracts/specs/docs.
5. **Единая база без распределенных транзакций:** пока домены живут в одном приложении, использовать транзакции Laravel/Eloquent вместо premature service extraction.
6. **Extraction-ready boundaries:** если домен позже понадобится вынести, его публичные Actions/Services, events, DTO и specs уже должны описывать boundary.

## Примеры кода

### Доменная операция инкапсулирует бизнес-правила
```php
namespace App\Domain\Sites\Actions;

use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Events\SiteConnected;
use App\Domain\Sites\Exceptions\SiteDomainConflictException;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Services\DomainNormalizer;
use App\Domain\Sites\Services\SiteIdFactory;
use Illuminate\Support\Facades\DB;

final class CreateSiteAction
{
    public function __construct(
        private readonly DomainNormalizer $domainNormalizer,
        private readonly SiteIdFactory $siteIdFactory,
    ) {}

    public function handle(int $ownerUserId, string $domain, ?string $displayName): Site
    {
        $normalizedDomain = $this->domainNormalizer->normalize($domain);

        if (Site::query()->where('normalized_domain', $normalizedDomain)->where('owner_user_id', $ownerUserId)->exists()) {
            throw new SiteDomainConflictException('site_for_domain_already_exists');
        }

        $site = DB::transaction(function () use ($ownerUserId, $domain, $displayName, $normalizedDomain): Site {
            return Site::query()->create([
                'id' => $this->siteIdFactory->make(),
                'owner_user_id' => $ownerUserId,
                'domain' => $domain,
                'display_name' => $displayName,
                'normalized_domain' => $normalizedDomain,
                'verification_status' => SiteVerificationStatus::Pending->value,
            ]);
        });

        SiteConnected::dispatch($site);

        return $site;
    }
}
```

### Controller остается транспортным адаптером
```php
namespace App\Http\Controllers\Api\Sites;

use App\Domain\Sites\Actions\CreateSiteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateSiteRequest;
use Illuminate\Http\JsonResponse;

final class CreateSiteController extends Controller
{
    public function __invoke(CreateSiteRequest $request, CreateSiteAction $createSite): JsonResponse
    {
        $site = $createSite->handle(
            ownerUserId: (int) $request->user()->id,
            domain: $request->string('domain')->toString(),
            displayName: $request->string('display_name')->toString() ?: null,
        );

        return response()->json([
            'site_id' => $site->id,
            'domain' => $site->domain,
            'verification_status' => $site->verification_status,
        ], 201);
    }
}
```

### Междоменный аудит идет через события
```php
namespace App\Providers;

use App\Domain\Issuer\Events\UserApiTokenIssued;
use App\Domain\Audit\Listeners\RecordIssuerAuditEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserApiTokenIssued::class => [
            RecordIssuerAuditEvent::class,
        ],
    ];
}
```

## Антипаттерны
- Писать OAuth token issuance, PKCE validation или JWKS logic прямо в controller.
- Передавать raw token, `client_secret`, private key или authorization code в logs, exceptions или audit payload.
- Делать permissive defaults для scopes, permissions, roles или missing claims.
- Давать upstream доверять публичным `X-Idshka-*` headers без gateway sanitization.
- Создавать shortcut в другом домене через внутренние модели/сервисы, если есть contract, public Action/Service или domain event.
- Обновлять код внешнего flow без соответствующего изменения `openspec/specs`, docs и tests/evidence.
