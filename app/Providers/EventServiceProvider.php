<?php

namespace App\Providers;

use App\Domain\Audit\Listeners\RecordIdentityAuditEvent;
use App\Domain\Audit\Listeners\RecordSiteAuditEvent;
use App\Domain\Identity\Events\PasswordLoginSucceeded;
use App\Domain\Identity\Events\SocialAccountLinked;
use App\Domain\Identity\Events\SocialAccountUnlinked;
use App\Domain\Identity\Events\SocialLoginSucceeded;
use App\Domain\Sites\Events\SiteConnected;
use App\Domain\Sites\Events\SiteModeEnabled;
use App\Domain\Sites\Events\SiteVerificationCompleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, string>>
     */
    protected $listen = [
        SocialiteWasCalled::class => [
            'SocialiteProviders\\VKontakte\\VKontakteExtendSocialite@handle',
            'SocialiteProviders\\Yandex\\YandexExtendSocialite@handle',
        ],
        PasswordLoginSucceeded::class => [
            RecordIdentityAuditEvent::class,
        ],
        SocialLoginSucceeded::class => [
            RecordIdentityAuditEvent::class,
        ],
        SocialAccountLinked::class => [
            RecordIdentityAuditEvent::class,
        ],
        SocialAccountUnlinked::class => [
            RecordIdentityAuditEvent::class,
        ],
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
