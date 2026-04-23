<?php

namespace App\Domain\Sites\Events;

use App\Domain\Sites\Models\Site;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SiteConnected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Site $site,
    ) {
    }
}
