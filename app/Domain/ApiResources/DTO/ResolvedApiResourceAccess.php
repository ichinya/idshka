<?php

namespace App\Domain\ApiResources\DTO;

final readonly class ResolvedApiResourceAccess
{
    /**
     * @param  list<string>  $scopes
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $siteId,
        public string $audience,
        public array $scopes,
        public array $permissions,
    ) {}
}
