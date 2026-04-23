<?php

namespace App\Domain\Sites\Services;

use App\Domain\Sites\Contracts\VerifiedSiteLookup;
use App\Domain\Sites\Exceptions\UnverifiedSiteException;
use App\Domain\Sites\Models\Site;
use Illuminate\Support\Facades\Log;

final class EloquentVerifiedSiteLookup implements VerifiedSiteLookup
{
    public function requireVerified(string $siteId): Site
    {
        Log::debug('[site.lookup.verified] started', ['site_id' => $siteId]);

        $site = Site::query()->findOrFail($siteId);

        if (! $site->isVerified()) {
            Log::warning('[site.lookup.verified] rejected_unverified_site', [
                'site_id' => $siteId,
                'verification_status' => $site->verification_status,
            ]);

            throw new UnverifiedSiteException('unverified_site_cannot_receive_production_credentials');
        }

        Log::debug('[site.lookup.verified] completed', ['site_id' => $siteId]);

        return $site;
    }
}
