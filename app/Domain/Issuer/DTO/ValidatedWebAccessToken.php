<?php

namespace App\Domain\Issuer\DTO;

final readonly class ValidatedWebAccessToken
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public int $userId,
        public string $siteId,
        public string $clientId,
        public array $scopes,
        public string $jti,
    ) {}
}
