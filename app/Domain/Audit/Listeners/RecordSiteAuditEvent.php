<?php

namespace App\Domain\Audit\Listeners;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Sites\Events\SiteConnected;
use App\Domain\Sites\Events\SiteModeEnabled;
use App\Domain\Sites\Events\SiteVerificationCompleted;
use Illuminate\Support\Facades\Log;

final class RecordSiteAuditEvent
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
    ) {}

    public function handle(SiteConnected|SiteVerificationCompleted|SiteModeEnabled $event): void
    {
        if ($event instanceof SiteConnected) {
            $this->auditRecorder->record(
                category: 'site',
                action: 'site.connected',
                userId: $event->site->owner_user_id,
                siteId: $event->site->id,
                summary: 'Site connected',
                metadata: [
                    'domain' => $event->site->normalized_domain,
                ],
            );

            Log::info('[audit.site_registry] site connected', [
                'site_id' => $event->site->id,
                'owner_user_id' => $event->site->owner_user_id,
            ]);

            return;
        }

        if ($event instanceof SiteVerificationCompleted) {
            $this->auditRecorder->record(
                category: 'site',
                action: 'site.verification_completed',
                userId: $event->site->owner_user_id,
                siteId: $event->site->id,
                summary: 'Site verification checked',
                metadata: [
                    'method' => $event->method->value,
                    'success' => $event->success,
                    'error_code' => $event->errorCode,
                ],
            );

            Log::info('[audit.site_registry] site verification completed', [
                'site_id' => $event->site->id,
                'owner_user_id' => $event->site->owner_user_id,
                'method' => $event->method->value,
                'success' => $event->success,
                'error_code' => $event->errorCode,
            ]);

            return;
        }

        $this->auditRecorder->record(
            category: 'site',
            action: 'site.mode_enabled',
            userId: $event->site->owner_user_id,
            siteId: $event->site->id,
            summary: 'Site mode enabled',
            metadata: [
                'mode' => $event->mode->value,
            ],
        );

        Log::info('[audit.site_registry] site mode enabled', [
            'site_id' => $event->site->id,
            'owner_user_id' => $event->site->owner_user_id,
            'mode' => $event->mode->value,
        ]);
    }
}
