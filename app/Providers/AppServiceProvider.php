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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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

        RateLimiter::for('site-registry', function (Request $request): Limit {
            $key = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

            return Limit::perMinute(30)->by('site-registry:'.$key);
        });

        RateLimiter::for('auth-login', function (Request $request): Limit {
            $email = mb_strtolower((string) $request->input('email', ''));
            $key = hash('sha256', $email.'|'.$request->ip());

            return Limit::perMinute(10)->by('auth-login:'.$key);
        });

        RateLimiter::for('auth-social', function (Request $request): Limit {
            $provider = mb_strtolower((string) $request->route('provider'));
            $key = hash('sha256', $provider.'|'.$request->ip());

            return Limit::perMinute(30)->by('auth-social:'.$key);
        });

        RateLimiter::for('token-issue', function (Request $request): Limit {
            $key = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

            return Limit::perMinute(20)->by('token-issue:'.$key);
        });

        RateLimiter::for('token-revoke', function (Request $request): Limit {
            $key = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

            return Limit::perMinute(40)->by('token-revoke:'.$key);
        });

        RateLimiter::for('jwks-public', function (Request $request): Limit {
            return Limit::perMinute(120)->by('jwks-public:'.$request->ip());
        });
    }
}
