<?php

namespace App\Domain\OidcClients\Events;

use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OidcRedirectUriAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OidcClient $client,
        public readonly OidcRedirectUri $redirectUri,
    ) {}
}
