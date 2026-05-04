<?php

namespace App\Domain\OidcClients\Services;

use App\Domain\OidcClients\DTO\ResolvedOidcClient;
use App\Domain\OidcClients\Exceptions\OidcClientException;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Models\SiteMode;
use App\Support\SafeLogContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class OidcClientResolver
{
    public function resolveForAuthorize(string $clientId, string $redirectUri): ResolvedOidcClient
    {
        Log::debug('[oidc.client.resolve_authorize] started', SafeLogContext::from([
            'client_id' => $clientId,
            'redirect_uri_hash' => hash('sha256', $redirectUri),
        ]));

        $client = $this->requireActiveClient($clientId);

        return $this->resolveActiveClientRedirect($client, $redirectUri, 'resolve_authorize');
    }

    public function resolveForTokenExchange(string $clientId, string $clientSecret, string $redirectUri): ResolvedOidcClient
    {
        Log::debug('[oidc.client.resolve_token] started', SafeLogContext::from([
            'client_id' => $clientId,
        ]));

        $client = $this->requireActiveClient($clientId);

        if (! $this->verifyClientSecret($client, $clientSecret)) {
            throw OidcClientException::unauthorized('invalid_client', 'Invalid client credentials.');
        }

        return $this->resolveActiveClientRedirect($client, $redirectUri, 'resolve_token');
    }

    private function resolveActiveClientRedirect(OidcClient $client, string $redirectUri, string $logContext): ResolvedOidcClient
    {
        $site = $client->site;
        $logPrefix = '[oidc.client.'.$logContext.']';

        if (! $site->isVerified()) {
            Log::warning($logPrefix.' unverified_site', SafeLogContext::from([
                'client_id' => $client->client_id,
                'site_id' => $site->id,
            ]));

            throw OidcClientException::forbidden('unverified_site', 'unverified_site');
        }

        $hasWebClientMode = SiteMode::query()
            ->where('site_id', $site->id)
            ->where('mode', SiteModeType::WebClient->value)
            ->exists();

        if (! $hasWebClientMode) {
            Log::warning($logPrefix.' web_client_mode_required', SafeLogContext::from([
                'client_id' => $client->client_id,
                'site_id' => $site->id,
            ]));

            throw OidcClientException::forbidden('web_client_mode_required', 'web_client_mode_required');
        }

        $redirect = $this->requireExactRedirectUri($client, $redirectUri);

        Log::debug($logPrefix.' completed', SafeLogContext::from([
            'client_id' => $client->client_id,
            'site_id' => $site->id,
            'redirect_uri_id' => $redirect->id,
        ]));

        return new ResolvedOidcClient($client, $site, $redirect);
    }

    public function verifyClientSecret(OidcClient $client, string $clientSecret): bool
    {
        $isValid = Hash::check($clientSecret, $client->client_secret_hash);

        if (! $isValid) {
            Log::warning('[oidc.client.secret] invalid_secret', SafeLogContext::from([
                'client_id' => $client->client_id,
                'site_id' => $client->site_id,
            ]));
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
            Log::warning('[oidc.client.resolve] invalid_client', SafeLogContext::from([
                'client_id' => $clientId,
                'is_revoked' => $client?->isRevoked(),
            ]));

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
            Log::warning('[oidc.client.redirect_uri] mismatch', SafeLogContext::from([
                'client_id' => $client->client_id,
                'site_id' => $client->site_id,
                'redirect_uri_hash' => $redirectUriHash,
            ]));

            throw OidcClientException::validation('redirect_uri_mismatch', 'redirect_uri_mismatch');
        }

        return $redirect;
    }
}
