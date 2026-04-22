<?php

namespace App\Providers;

use App\Domain\Sites\Contracts\DnsTxtRecordLookup;
use App\Domain\Sites\Contracts\VerifiedSiteLookup;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Services\EloquentVerifiedSiteLookup;
use App\Domain\Sites\Services\NativeDnsTxtRecordLookup;
use App\Policies\SitePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DnsTxtRecordLookup::class, NativeDnsTxtRecordLookup::class);
        $this->app->bind(VerifiedSiteLookup::class, EloquentVerifiedSiteLookup::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Site::class, SitePolicy::class);
    }
}
