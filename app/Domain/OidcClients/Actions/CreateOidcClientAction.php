<?php

namespace App\Domain\OidcClients\Actions;

use App\Domain\OidcClients\DTO\IssuedOidcClient;
use App\Domain\OidcClients\Events\OidcClientCreated;
use App\Domain\OidcClients\Events\OidcRedirectUriAdded;
use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Enums\SiteVerificationStatus;
use App\Domain\Sites\Models\Site;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CreateOidcClientAction
{
    public function handle(int $ownerUserId, string $siteId, string $name, string $redirectUri): IssuedOidcClient
    {
        Log::info('[oidc.client.create] started', [
            'owner_user_id' => $ownerUserId,
            'site_id' => $siteId,
            'redirect_uri_hash' => hash('sha256', $redirectUri),
        ]);

        $site = $this->requireOwnedWebClientSite($ownerUserId, $siteId);
        $this->assertRedirectUri($redirectUri);

        $clientId = 'client_'.strtolower((string) Str::ulid());
        $rawSecret = 'secret_'.Str::random(48);

        [$client, $redirect] = DB::transaction(function () use ($site, $ownerUserId, $name, $clientId, $rawSecret, $redirectUri): array {
            /** @var OidcClient $client */
            $client = OidcClient::query()->create([
                'site_id' => $site->id,
                'owner_user_id' => $ownerUserId,
                'client_id' => $clientId,
                'client_secret_hash' => Hash::make($rawSecret),
                'name' => $name,
                'revoked_at' => null,
            ]);

            /** @var OidcRedirectUri $redirect */
            $redirect = OidcRedirectUri::query()->create([
                'oidc_client_id' => $client->id,
                'redirect_uri' => $redirectUri,
                'redirect_uri_hash' => hash('sha256', $redirectUri),
            ]);

            return [$client->refresh(), $redirect];
        });

        OidcClientCreated::dispatch($client);
        OidcRedirectUriAdded::dispatch($client, $redirect);

        Log::info('[oidc.client.create] completed', [
            'owner_user_id' => $ownerUserId,
            'site_id' => $site->id,
            'oidc_client_id' => $client->id,
            'client_id' => $client->client_id,
        ]);

        return new IssuedOidcClient($client, $rawSecret);
    }

    private function requireOwnedWebClientSite(int $ownerUserId, string $siteId): Site
    {
        /** @var Site|null $site */
        $site = Site::query()
            ->with('modes')
            ->whereKey($siteId)
            ->where('owner_user_id', $ownerUserId)
            ->first();

        if ($site === null) {
            throw new AuthorizationException('You do not own this site.');
        }

        if ($site->verification_status !== SiteVerificationStatus::Verified->value) {
            throw new InvalidArgumentException('Site must be verified first.');
        }

        $hasWebClientMode = $site->modes->contains(
            fn ($mode): bool => $mode->mode === SiteModeType::WebClient->value,
        );

        if (! $hasWebClientMode) {
            throw new InvalidArgumentException('Web client mode must be enabled first.');
        }

        return $site;
    }

    private function assertRedirectUri(string $redirectUri): void
    {
        if (str_contains($redirectUri, '*')) {
            throw new InvalidArgumentException('Redirect URI wildcards are not supported.');
        }
    }
}
