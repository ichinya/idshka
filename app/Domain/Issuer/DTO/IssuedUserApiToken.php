<?php

namespace App\Domain\Issuer\DTO;

use Carbon\CarbonImmutable;

final readonly class IssuedUserApiToken
{
    /**
     * @param  list<string>  $scopes
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $rawToken,
        public string $jti,
        public string $kid,
        public string $audience,
        public array $scopes,
        public array $permissions,
        public CarbonImmutable $issuedAt,
        public CarbonImmutable $notBefore,
        public CarbonImmutable $expiresAt,
        public int $signingKeyId,
    ) {}
}
