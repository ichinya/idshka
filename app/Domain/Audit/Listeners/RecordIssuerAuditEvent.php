<?php

namespace App\Domain\Audit\Listeners;

use App\Domain\Issuer\Events\UserApiTokenIssued;
use App\Domain\Issuer\Events\UserApiTokenRevoked;
use Illuminate\Support\Facades\Log;

final class RecordIssuerAuditEvent
{
    public function handle(UserApiTokenIssued|UserApiTokenRevoked $event): void
    {
        if ($event instanceof UserApiTokenIssued) {
            Log::info('[audit.issuer] user api token issued', [
                'user_id' => $event->userId,
                'site_id' => $event->siteId,
                'aud' => $event->audience,
                'jti' => $event->jti,
                'kid' => $event->kid,
                'expires_at' => $event->expiresAt->toISOString(),
            ]);

            return;
        }

        Log::info('[audit.issuer] user api token revoked', [
            'api_token_id' => $event->apiTokenId,
            'user_id' => $event->userId,
            'site_id' => $event->siteId,
            'aud' => $event->audience,
            'jti' => $event->jti,
            'revoked_at' => $event->revokedAt->toISOString(),
            'expires_at' => $event->expiresAt->toISOString(),
        ]);
    }
}
