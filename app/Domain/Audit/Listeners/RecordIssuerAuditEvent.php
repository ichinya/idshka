<?php

namespace App\Domain\Audit\Listeners;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Issuer\Events\UserApiTokenIssued;
use App\Domain\Issuer\Events\UserApiTokenRevoked;
use Illuminate\Support\Facades\Log;

final class RecordIssuerAuditEvent
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
    ) {}

    public function handle(UserApiTokenIssued|UserApiTokenRevoked $event): void
    {
        if ($event instanceof UserApiTokenIssued) {
            $this->auditRecorder->record(
                category: 'issuer',
                action: 'issuer.user_api_token_issued',
                userId: $event->userId,
                siteId: $event->siteId,
                summary: 'User API token issued',
                metadata: [
                    'aud' => $event->audience,
                    'jti' => $event->jti,
                    'kid' => $event->kid,
                    'expires_at' => $event->expiresAt?->toISOString(),
                ],
            );

            Log::info('[audit.issuer] user api token issued', [
                'user_id' => $event->userId,
                'site_id' => $event->siteId,
                'aud' => $event->audience,
                'jti' => $event->jti,
                'kid' => $event->kid,
                'expires_at' => $event->expiresAt?->toISOString(),
            ]);

            return;
        }

        $this->auditRecorder->record(
            category: 'issuer',
            action: 'issuer.user_api_token_revoked',
            userId: $event->userId,
            siteId: $event->siteId,
            summary: 'User API token revoked',
            metadata: [
                'api_token_id' => $event->apiTokenId,
                'aud' => $event->audience,
                'jti' => $event->jti,
                'revoked_at' => $event->revokedAt->toISOString(),
                'expires_at' => $event->expiresAt?->toISOString(),
            ],
        );

        Log::info('[audit.issuer] user api token revoked', [
            'api_token_id' => $event->apiTokenId,
            'user_id' => $event->userId,
            'site_id' => $event->siteId,
            'aud' => $event->audience,
            'jti' => $event->jti,
            'revoked_at' => $event->revokedAt->toISOString(),
            'expires_at' => $event->expiresAt?->toISOString(),
        ]);
    }
}
