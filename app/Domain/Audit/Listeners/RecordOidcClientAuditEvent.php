<?php

namespace App\Domain\Audit\Listeners;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\OidcClients\Events\OidcClientCreated;
use App\Domain\OidcClients\Events\OidcClientRevoked;
use App\Domain\OidcClients\Events\OidcRedirectUriAdded;
use Illuminate\Support\Facades\Log;

final class RecordOidcClientAuditEvent
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
    ) {}

    public function handle(OidcClientCreated|OidcClientRevoked|OidcRedirectUriAdded $event): void
    {
        if ($event instanceof OidcClientCreated) {
            $this->auditRecorder->record(
                category: 'oidc',
                action: 'oidc.client_created',
                userId: $event->client->owner_user_id,
                siteId: $event->client->site_id,
                summary: 'OIDC client created',
                metadata: [
                    'client_id' => $event->client->client_id,
                    'oidc_client_id' => $event->client->id,
                ],
            );

            Log::info('[audit.oidc] client created', [
                'oidc_client_id' => $event->client->id,
                'client_id' => $event->client->client_id,
                'site_id' => $event->client->site_id,
                'owner_user_id' => $event->client->owner_user_id,
            ]);

            return;
        }

        if ($event instanceof OidcRedirectUriAdded) {
            $this->auditRecorder->record(
                category: 'oidc',
                action: 'oidc.redirect_uri_added',
                userId: $event->client->owner_user_id,
                siteId: $event->client->site_id,
                summary: 'OIDC redirect URI added',
                metadata: [
                    'client_id' => $event->client->client_id,
                    'oidc_client_id' => $event->client->id,
                    'redirect_uri_hash' => $event->redirectUri->redirect_uri_hash,
                ],
            );

            Log::info('[audit.oidc] redirect uri added', [
                'oidc_client_id' => $event->client->id,
                'client_id' => $event->client->client_id,
                'site_id' => $event->client->site_id,
                'redirect_uri_id' => $event->redirectUri->id,
                'redirect_uri_hash' => $event->redirectUri->redirect_uri_hash,
            ]);

            return;
        }

        $this->auditRecorder->record(
            category: 'oidc',
            action: 'oidc.client_revoked',
            userId: $event->client->owner_user_id,
            siteId: $event->client->site_id,
            summary: 'OIDC client revoked',
            metadata: [
                'client_id' => $event->client->client_id,
                'oidc_client_id' => $event->client->id,
                'revoked_at' => $event->client->revoked_at?->toISOString(),
            ],
        );

        Log::info('[audit.oidc] client revoked', [
            'oidc_client_id' => $event->client->id,
            'client_id' => $event->client->client_id,
            'site_id' => $event->client->site_id,
            'owner_user_id' => $event->client->owner_user_id,
        ]);
    }
}
