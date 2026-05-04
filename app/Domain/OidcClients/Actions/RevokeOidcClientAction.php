<?php

namespace App\Domain\OidcClients\Actions;

use App\Domain\OidcClients\Events\OidcClientRevoked;
use App\Domain\OidcClients\Models\OidcClient;
use App\Support\SafeLogContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

final class RevokeOidcClientAction
{
    public function handle(int $ownerUserId, OidcClient $client): OidcClient
    {
        Log::info('[oidc.client.revoke] started', SafeLogContext::from([
            'owner_user_id' => $ownerUserId,
            'oidc_client_id' => $client->id,
            'client_id' => $client->client_id,
        ]));

        if ($client->owner_user_id !== $ownerUserId) {
            throw new AuthorizationException('You do not own this client.');
        }

        if ($client->revoked_at === null) {
            $client->forceFill(['revoked_at' => now()])->save();
            OidcClientRevoked::dispatch($client->refresh());
        }

        Log::info('[oidc.client.revoke] completed', SafeLogContext::from([
            'owner_user_id' => $ownerUserId,
            'oidc_client_id' => $client->id,
            'client_id' => $client->client_id,
            'revoked_at' => $client->revoked_at?->toISOString(),
        ]));

        return $client->refresh();
    }
}
