<?php

namespace App\Domain\OidcClients\Events;

use App\Domain\OidcClients\Models\OidcClient;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OidcClientRevoked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OidcClient $client,
    ) {}
}
