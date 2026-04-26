<?php

namespace App\Domain\ApiResources\Services;

use App\Domain\ApiResources\Contracts\ApiResourceAccessResolver;
use App\Domain\ApiResources\DTO\ResolvedApiResourceAccess;
use App\Domain\ApiResources\Exceptions\ApiResourceEligibilityException;
use App\Domain\Sites\Contracts\VerifiedSiteLookup;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Models\SiteMode;
use Illuminate\Support\Facades\Log;

final class SiteApiResourceAccessResolver implements ApiResourceAccessResolver
{
    public function __construct(
        private readonly VerifiedSiteLookup $verifiedSiteLookup,
    ) {}

    public function resolveForUser(
        int $userId,
        string $siteId,
        array $requestedScopes,
        array $requestedPermissions,
    ): ResolvedApiResourceAccess {
        Log::debug('[api_resource.resolve_access] started', [
            'user_id' => $userId,
            'site_id' => $siteId,
            'requested_scopes_count' => count($requestedScopes),
            'requested_permissions_count' => count($requestedPermissions),
        ]);

        $site = $this->verifiedSiteLookup->requireVerified($siteId);

        if ($site->owner_user_id !== $userId) {
            Log::warning('[api_resource.resolve_access] owner_mismatch', [
                'user_id' => $userId,
                'site_id' => $site->id,
                'site_owner_user_id' => $site->owner_user_id,
            ]);

            throw ApiResourceEligibilityException::forbidden(
                'site_owner_mismatch',
                'You do not own this site.',
            );
        }

        $hasApiMode = SiteMode::query()
            ->where('site_id', $site->id)
            ->where('mode', SiteModeType::ApiResource->value)
            ->exists();

        if (! $hasApiMode) {
            Log::warning('[api_resource.resolve_access] api_mode_missing', [
                'user_id' => $userId,
                'site_id' => $site->id,
            ]);

            throw ApiResourceEligibilityException::forbidden(
                'api_resource_mode_required',
                'Site must enable api_resource mode first.',
            );
        }

        $allowedScopes = $this->normalizeValues(config('issuer.api_resources.allowed_scopes', []));
        $allowedPermissions = $this->normalizeValues(config('issuer.api_resources.allowed_permissions', []));

        $normalizedScopes = $this->normalizeValues($requestedScopes);
        $normalizedPermissions = $this->normalizeValues($requestedPermissions);

        $invalidScopes = array_values(array_diff($normalizedScopes, $allowedScopes));

        if ($invalidScopes !== []) {
            Log::warning('[api_resource.resolve_access] invalid_scopes', [
                'user_id' => $userId,
                'site_id' => $site->id,
                'invalid_scope_count' => count($invalidScopes),
            ]);

            throw ApiResourceEligibilityException::validation(
                'invalid_scope',
                'Requested scopes are not allowed for this site.',
            );
        }

        $invalidPermissions = array_values(array_diff($normalizedPermissions, $allowedPermissions));

        if ($invalidPermissions !== []) {
            Log::warning('[api_resource.resolve_access] invalid_permissions', [
                'user_id' => $userId,
                'site_id' => $site->id,
                'invalid_permission_count' => count($invalidPermissions),
            ]);

            throw ApiResourceEligibilityException::validation(
                'invalid_permissions',
                'Requested permissions are not allowed for this site.',
            );
        }

        Log::debug('[api_resource.resolve_access] completed', [
            'user_id' => $userId,
            'site_id' => $site->id,
            'audience' => $site->normalized_domain,
            'scopes_count' => count($normalizedScopes),
            'permissions_count' => count($normalizedPermissions),
        ]);

        return new ResolvedApiResourceAccess(
            siteId: $site->id,
            audience: $site->normalized_domain,
            scopes: $normalizedScopes,
            permissions: $normalizedPermissions,
        );
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function normalizeValues(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $trimmed = trim((string) $value);

            if ($trimmed === '') {
                continue;
            }

            $normalized[] = $trimmed;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }
}
