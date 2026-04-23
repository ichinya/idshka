<?php

namespace App\Providers;

use App\Domain\Audit\Listeners\RecordSiteAuditEvent;
use App\Domain\Sites\Events\SiteConnected;
use App\Domain\Sites\Events\SiteModeEnabled;
use App\Domain\Sites\Events\SiteVerificationCompleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        SiteConnected::class => [
            RecordSiteAuditEvent::class,
        ],
        SiteVerificationCompleted::class => [
            RecordSiteAuditEvent::class,
        ],
        SiteModeEnabled::class => [
            RecordSiteAuditEvent::class,
        ],
    ];
}
