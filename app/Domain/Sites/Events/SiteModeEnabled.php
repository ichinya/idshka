<?php

namespace App\Domain\Sites\Events;

use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Models\Site;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SiteModeEnabled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Site $site,
        public readonly SiteModeType $mode,
    ) {
    }
}
