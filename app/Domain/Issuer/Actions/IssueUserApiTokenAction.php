<?php

namespace App\Domain\Issuer\Actions;

use App\Domain\ApiResources\Contracts\ApiResourceAccessResolver;
use App\Domain\Issuer\DTO\IssuedUserApiToken;
use App\Domain\Issuer\Events\UserApiTokenIssued;
use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Services\TokenIssuer;
use Illuminate\Support\Facades\Log;

final class IssueUserApiTokenAction
{
    public function __construct(
        private readonly ApiResourceAccessResolver $apiResourceAccessResolver,
        private readonly TokenIssuer $tokenIssuer,
    ) {}

    /**
     * @param  list<string>  $requestedScopes
     * @param  list<string>  $requestedPermissions
     */
    public function handle(
        int $userId,
        string $siteId,
        array $requestedScopes,
        array $requestedPermissions,
    ): IssuedUserApiToken {
        Log::info('[issuer.issue_user_api_token] started', [
            'user_id' => $userId,
            'site_id' => $siteId,
            'requested_scopes_count' => count($requestedScopes),
            'requested_permissions_count' => count($requestedPermissions),
        ]);

        $access = $this->apiResourceAccessResolver->resolveForUser(
            userId: $userId,
            siteId: $siteId,
            requestedScopes: $requestedScopes,
            requestedPermissions: $requestedPermissions,
        );

        $issuedToken = $this->tokenIssuer->issueUserApiToken(
            userId: $userId,
            siteId: $access->siteId,
            audience: $access->audience,
            scopes: $access->scopes,
            permissions: $access->permissions,
        );

        ApiToken::query()->create([
            'user_id' => $userId,
            'site_id' => $access->siteId,
            'signing_key_id' => $issuedToken->signingKeyId,
            'audience' => $access->audience,
            'jti' => $issuedToken->jti,
            'token_hash' => hash('sha256', $issuedToken->rawToken),
            'scopes' => $issuedToken->scopes,
            'permissions' => $issuedToken->permissions,
            'issued_at' => $issuedToken->issuedAt->toDateTimeString(),
            'expires_at' => $issuedToken->expiresAt->toDateTimeString(),
            'revoked_at' => null,
        ]);

        UserApiTokenIssued::dispatch(
            userId: $userId,
            siteId: $access->siteId,
            audience: $access->audience,
            jti: $issuedToken->jti,
            kid: $issuedToken->kid,
            expiresAt: $issuedToken->expiresAt,
        );

        Log::info('[issuer.issue_user_api_token] completed', [
            'user_id' => $userId,
            'site_id' => $access->siteId,
            'audience' => $access->audience,
            'jti' => $issuedToken->jti,
            'kid' => $issuedToken->kid,
        ]);

        return $issuedToken;
    }
}
