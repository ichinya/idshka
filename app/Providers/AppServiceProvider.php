<?php

namespace App\Providers;

use App\Domain\ApiResources\Contracts\ApiResourceAccessResolver;
use App\Domain\ApiResources\Services\SiteApiResourceAccessResolver;
use App\Domain\Sites\Contracts\DnsTxtRecordLookup;
use App\Domain\Sites\Contracts\VerifiedSiteLookup;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Services\EloquentVerifiedSiteLookup;
use App\Domain\Sites\Services\NativeDnsTxtRecordLookup;
use App\Policies\SitePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, true>
     */
    private array $reportedInvalidRateLimitConfig = [];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ApiResourceAccessResolver::class, SiteApiResourceAccessResolver::class);
        $this->app->bind(DnsTxtRecordLookup::class, NativeDnsTxtRecordLookup::class);
        $this->app->bind(VerifiedSiteLookup::class, EloquentVerifiedSiteLookup::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Site::class, SitePolicy::class);

        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('site-registry', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('site-registry', 'site_registry', 30))
                ->by($this->userOrIpKey($request, 'site-registry'));
        });

        RateLimiter::for('auth-login', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('auth-login', 'auth_login', 10))
                ->by($this->hashedKey('auth-login', [
                    mb_strtolower((string) $request->input('email', '')),
                    (string) $request->ip(),
                ]));
        });

        RateLimiter::for('auth-social', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('auth-social', 'auth_social', 30))
                ->by($this->hashedKey('auth-social', [
                    mb_strtolower((string) $request->route('provider')),
                    (string) $request->ip(),
                ]));
        });

        RateLimiter::for('site-verification', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('site-verification', 'site_verification', 20))
                ->by($this->userOrIpKey($request, 'site-verification'));
        });

        RateLimiter::for('token-issue', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('token-issue', 'token_issue', 20))
                ->by($this->userOrIpKey($request, 'token-issue'));
        });

        RateLimiter::for('token-revoke', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('token-revoke', 'token_revoke', 40))
                ->by($this->userOrIpKey($request, 'token-revoke'));
        });

        RateLimiter::for('jwks-public', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('jwks-public', 'jwks_public', 120))
                ->by('jwks-public:'.$request->ip());
        });

        RateLimiter::for('oauth-authorize', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('oauth-authorize', 'oauth_authorize', 60))
                ->by($this->userOrIpKey($request, 'oauth-authorize'));
        });

        RateLimiter::for('oauth-token', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('oauth-token', 'oauth_token', 60))
                ->by($this->hashedKey('oauth-token', [
                    mb_strtolower((string) $request->input('client_id', '')),
                    (string) $request->ip(),
                ]));
        });

        RateLimiter::for('oauth-userinfo', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('oauth-userinfo', 'oauth_userinfo', 120))
                ->by('oauth-userinfo:'.$request->ip());
        });

        RateLimiter::for('portal-site-write', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('portal-site-write', 'portal_site_write', 30))
                ->by($this->userOrIpKey($request, 'portal-site-write'));
        });

        RateLimiter::for('portal-verification', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('portal-verification', 'portal_verification', 20))
                ->by($this->userOrIpKey($request, 'portal-verification'));
        });

        RateLimiter::for('portal-credential-issue', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('portal-credential-issue', 'portal_credential_issue', 20))
                ->by($this->userOrIpKey($request, 'portal-credential-issue'));
        });

        RateLimiter::for('portal-credential-revoke', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('portal-credential-revoke', 'portal_credential_revoke', 40))
                ->by($this->userOrIpKey($request, 'portal-credential-revoke'));
        });

        RateLimiter::for('portal-client-write', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('portal-client-write', 'portal_client_write', 20))
                ->by($this->userOrIpKey($request, 'portal-client-write'));
        });

        RateLimiter::for('portal-redirect-uri-write', function (Request $request): Limit {
            return Limit::perMinute($this->configuredPerMinute('portal-redirect-uri-write', 'portal_redirect_uri_write', 30))
                ->by($this->userOrIpKey($request, 'portal-redirect-uri-write'));
        });
    }

    private function configuredPerMinute(string $limiter, string $configKey, int $default): int
    {
        $value = config("security.rate_limits.{$configKey}.per_minute", $default);
        $perMinute = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if (is_int($perMinute)) {
            return $perMinute;
        }

        if (! isset($this->reportedInvalidRateLimitConfig[$limiter])) {
            Log::warning('[rate_limit.config] invalid per-minute limit; using default', [
                'limiter' => $limiter,
                'config_key' => "security.rate_limits.{$configKey}.per_minute",
                'configured_type' => get_debug_type($value),
                'default_per_minute' => $default,
            ]);

            $this->reportedInvalidRateLimitConfig[$limiter] = true;
        }

        return $default;
    }

    private function userOrIpKey(Request $request, string $prefix): string
    {
        $identifier = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

        return $prefix.':'.$identifier;
    }

    /**
     * @param  list<string>  $parts
     */
    private function hashedKey(string $prefix, array $parts): string
    {
        return $prefix.':'.hash('sha256', implode('|', $parts));
    }
}
