<?php

namespace App\Domain\Sites\Events;

use App\Domain\Sites\Enums\SiteVerificationMethod;
use App\Domain\Sites\Models\Site;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SiteVerificationCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Site $site,
        public readonly SiteVerificationMethod $method,
        public readonly bool $success,
        public readonly ?string $errorCode = null,
    ) {}
}
