<?php

namespace App\Domain\Issuer\DTO;

use App\Domain\Issuer\Models\OAuthAuthorizationCode;
use Carbon\CarbonImmutable;

final readonly class IssuedAuthorizationCode
{
    public function __construct(
        public string $rawCode,
        public OAuthAuthorizationCode $record,
        public CarbonImmutable $expiresAt,
    ) {}
}
