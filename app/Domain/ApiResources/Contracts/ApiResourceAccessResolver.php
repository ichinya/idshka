<?php

namespace App\Domain\ApiResources\Contracts;

use App\Domain\ApiResources\DTO\ResolvedApiResourceAccess;

interface ApiResourceAccessResolver
{
    /**
     * @param  list<string>  $requestedScopes
     * @param  list<string>  $requestedPermissions
     */
    public function resolveForUser(
        int $userId,
        string $siteId,
        array $requestedScopes,
        array $requestedPermissions,
    ): ResolvedApiResourceAccess;
}
