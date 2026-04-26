<?php

namespace App\Domain\Issuer\DTO;

use Carbon\CarbonImmutable;

final readonly class IssuedWebLoginTokens
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public string $rawIdToken,
        public string $rawAccessToken,
        public string $idTokenJti,
        public string $accessTokenJti,
        public string $kid,
        public array $scopes,
        public CarbonImmutable $idTokenExpiresAt,
        public CarbonImmutable $accessTokenExpiresAt,
    ) {}
}
