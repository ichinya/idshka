<?php

namespace App\Domain\OidcClients\Actions;

use App\Domain\OidcClients\Events\OidcRedirectUriAdded;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class AddOidcRedirectUriAction
{
    public function handle(int $ownerUserId, OidcClient $client, string $redirectUri): OidcRedirectUri
    {
        Log::info('[oidc.redirect_uri.add] started', [
            'owner_user_id' => $ownerUserId,
            'oidc_client_id' => $client->id,
            'client_id' => $client->client_id,
            'redirect_uri_hash' => hash('sha256', $redirectUri),
        ]);

        if ($client->owner_user_id !== $ownerUserId) {
            throw new AuthorizationException('You do not own this client.');
        }

        if ($client->isRevoked()) {
            throw new InvalidArgumentException('Cannot add redirect URI to a revoked client.');
        }

        if (str_contains($redirectUri, '*')) {
            throw new InvalidArgumentException('Redirect URI wildcards are not supported.');
        }

        /** @var OidcRedirectUri $redirect */
        $redirect = OidcRedirectUri::query()->firstOrCreate(
            [
                'oidc_client_id' => $client->id,
                'redirect_uri_hash' => hash('sha256', $redirectUri),
            ],
            [
                'redirect_uri' => $redirectUri,
            ],
        );

        if ($redirect->wasRecentlyCreated) {
            OidcRedirectUriAdded::dispatch($client, $redirect);
        }

        Log::info('[oidc.redirect_uri.add] completed', [
            'owner_user_id' => $ownerUserId,
            'oidc_client_id' => $client->id,
            'redirect_uri_id' => $redirect->id,
            'created' => $redirect->wasRecentlyCreated,
        ]);

        return $redirect;
    }
}
