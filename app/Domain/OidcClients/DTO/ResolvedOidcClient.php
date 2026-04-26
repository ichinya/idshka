<?php

namespace App\Domain\OidcClients\DTO;

use App\Domain\OidcClients\Models\OidcClient;
use App\Domain\OidcClients\Models\OidcRedirectUri;
use App\Domain\Sites\Models\Site;

final readonly class ResolvedOidcClient
{
    public function __construct(
        public OidcClient $client,
        public Site $site,
        public OidcRedirectUri $redirectUri,
    ) {}
}
