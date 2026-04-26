<?php

namespace App\Domain\OidcClients\Services;

use App\Domain\OidcClients\DTO\ResolvedOidcClient;
use App\Domain\OidcClients\Exceptions\OidcClientException;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Models\SiteMode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class OidcClientResolver
{
    public function resolveForAuthorize(string $clientId, string $redirectUri): ResolvedOidcClient
    {
        Log::debug('[oidc.client.resolve_authorize] started', [
            'client_id' => $clientId,
            'redirect_uri_hash' => hash('sha256', $redirectUri),
        ]);

        $client = $this->requireActiveClient($clientId);
        $site = $client->site;

        if (! $site->isVerified()) {
            Log::warning('[oidc.client.resolve_authorize] unverified_site', [
                'client_id' => $client->client_id,
                'site_id' => $site->id,
            ]);

            throw OidcClientException::forbidden('unverified_site', 'unverified_site');
        }

        $hasWebClientMode = SiteMode::query()
            ->where('site_id', $site->id)
            ->where('mode', SiteModeType::WebClient->value)
            ->exists();

        if (! $hasWebClientMode) {
            Log::warning('[oidc.client.resolve_authorize] web_client_mode_required', [
                'client_id' => $client->client_id,
                'site_id' => $site->id,
            ]);

            throw OidcClientException::forbidden('web_client_mode_required', 'web_client_mode_required');
        }

        $redirect = $this->requireExactRedirectUri($client, $redirectUri);

        Log::debug('[oidc.client.resolve_authorize] completed', [
            'client_id' => $client->client_id,
            'site_id' => $site->id,
            'redirect_uri_id' => $redirect->id,
        ]);

        return new ResolvedOidcClient($client, $site, $redirect);
    }

    public function verifyClientSecret(OidcClient $client, string $clientSecret): bool
    {
        $isValid = Hash::check($clientSecret, $client->client_secret_hash);

        if (! $isValid) {
            Log::warning('[oidc.client.secret] invalid_secret', [
                'client_id' => $client->client_id,
                'site_id' => $client->site_id,
            ]);
        }

        return $isValid;
    }

    private function requireActiveClient(string $clientId): OidcClient
    {
        /** @var OidcClient|null $client */
        $client = OidcClient::query()
            ->with('site')
            ->where('client_id', $clientId)
            ->first();

        if ($client === null || $client->isRevoked()) {
            Log::warning('[oidc.client.resolve] invalid_client', [
                'client_id' => $clientId,
                'is_revoked' => $client?->isRevoked(),
            ]);

            throw OidcClientException::unauthorized('invalid_client', 'invalid_client');
        }

        return $client;
    }

    private function requireExactRedirectUri(OidcClient $client, string $redirectUri): OidcRedirectUri
    {
        $redirectUriHash = hash('sha256', $redirectUri);

        /** @var OidcRedirectUri|null $redirect */
        $redirect = OidcRedirectUri::query()
            ->where('oidc_client_id', $client->id)
            ->where('redirect_uri_hash', $redirectUriHash)
            ->where('redirect_uri', $redirectUri)
            ->first();

        if ($redirect === null) {
            Log::warning('[oidc.client.redirect_uri] mismatch', [
                'client_id' => $client->client_id,
                'site_id' => $client->site_id,
                'redirect_uri_hash' => $redirectUriHash,
            ]);

            throw OidcClientException::validation('redirect_uri_mismatch', 'redirect_uri_mismatch');
        }

        return $redirect;
    }
}
