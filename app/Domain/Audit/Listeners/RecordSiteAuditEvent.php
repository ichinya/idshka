<?php

namespace App\Domain\Audit\Listeners;

use App\Domain\Sites\Events\SiteConnected;
use App\Domain\Sites\Events\SiteModeEnabled;
use App\Domain\Sites\Events\SiteVerificationCompleted;
use Illuminate\Support\Facades\Log;

final class RecordSiteAuditEvent
{
    public function handle(SiteConnected|SiteVerificationCompleted|SiteModeEnabled $event): void
    {
        if ($event instanceof SiteConnected) {
            Log::info('[audit.site_registry] site connected', [
                'site_id' => $event->site->id,
                'owner_user_id' => $event->site->owner_user_id,
            ]);

            return;
        }

        if ($event instanceof SiteVerificationCompleted) {
            Log::info('[audit.site_registry] site verification completed', [
                'site_id' => $event->site->id,
                'owner_user_id' => $event->site->owner_user_id,
                'method' => $event->method->value,
                'success' => $event->success,
                'error_code' => $event->errorCode,
            ]);

            return;
        }

        Log::info('[audit.site_registry] site mode enabled', [
            'site_id' => $event->site->id,
            'owner_user_id' => $event->site->owner_user_id,
            'mode' => $event->mode->value,
        ]);
    }
}
