<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AuditRecorder
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $category,
        string $action,
        ?int $userId,
        ?string $siteId,
        string $summary,
        array $metadata = [],
    ): AuditEvent {
        Log::debug('[audit.record] started', [
            'category' => $category,
            'action' => $action,
            'user_id' => $userId,
            'site_id' => $siteId,
            'metadata_keys' => array_keys($metadata),
        ]);

        /** @var AuditEvent $event */
        $event = AuditEvent::query()->create([
            'user_id' => $userId,
            'site_id' => $siteId,
            'category' => $category,
            'action' => $action,
            'summary' => $summary,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);

        Log::debug('[audit.record] completed', [
            'audit_event_id' => $event->id,
            'category' => $category,
            'action' => $action,
        ]);

        return $event;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function tryRecord(
        string $category,
        string $action,
        ?int $userId,
        ?string $siteId,
        string $summary,
        array $metadata = [],
    ): ?AuditEvent {
        try {
            return $this->record($category, $action, $userId, $siteId, $summary, $metadata);
        } catch (Throwable $exception) {
            Log::warning('[audit.record] failed', [
                'category' => $category,
                'action' => $action,
                'user_id' => $userId,
                'site_id' => $siteId,
                'metadata_keys' => array_keys($metadata),
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
