<?php

namespace App\Domain\Sites\Actions;

use App\Domain\Sites\Contracts\VerifiedSiteLookup;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Events\SiteModeEnabled;
use App\Domain\Sites\Models\Site;
use App\Domain\Sites\Models\SiteMode;
use Illuminate\Support\Facades\Log;

final class EnableSiteModeAction
{
    public function __construct(
        private readonly VerifiedSiteLookup $verifiedSiteLookup,
    ) {
    }

    public function handle(Site $site, SiteModeType $mode): SiteMode
    {
        Log::info('[site.mode.enable] started', [
            'site_id' => $site->id,
            'mode' => $mode->value,
            'verification_status' => $site->verification_status,
        ]);

        // Centralized fail-closed production-eligibility check for downstream credentials/modes.
        $site = $this->verifiedSiteLookup->requireVerified($site->id);

        $siteMode = SiteMode::query()->firstOrCreate(
            [
                'site_id' => $site->id,
                'mode' => $mode->value,
            ],
            [
                'enabled_at' => now(),
            ],
        );

        SiteModeEnabled::dispatch($site, $mode);

        Log::info('[site.mode.enable] completed', [
            'site_id' => $site->id,
            'mode' => $mode->value,
            'site_mode_id' => $siteMode->id,
        ]);

        return $siteMode;
    }
}
