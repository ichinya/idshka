<?php

namespace App\Domain\OidcClients\DTO;

use App\Domain\OidcClients\Models\OidcClient;

final readonly class IssuedOidcClient
{
    public function __construct(
        public OidcClient $client,
        public string $rawClientSecret,
    ) {}
}
