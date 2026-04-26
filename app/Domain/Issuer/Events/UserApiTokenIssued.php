<?php

namespace App\Domain\Issuer\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserApiTokenIssued
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $siteId,
        public readonly string $audience,
        public readonly string $jti,
        public readonly string $kid,
        public readonly CarbonImmutable $expiresAt,
    ) {}
}
