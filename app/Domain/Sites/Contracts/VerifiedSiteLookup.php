<?php

namespace App\Domain\Sites\Contracts;

use App\Domain\Sites\Models\Site;

interface VerifiedSiteLookup
{
    public function requireVerified(string $siteId): Site;
}
