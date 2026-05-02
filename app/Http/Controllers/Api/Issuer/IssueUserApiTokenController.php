<?php

namespace App\Http\Controllers\Api\Issuer;

use App\Domain\ApiResources\Exceptions\ApiResourceEligibilityException;
use App\Domain\Issuer\Actions\IssueUserApiTokenAction;
use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Sites\Exceptions\UnverifiedSiteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IssueUserApiTokenRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

final class IssueUserApiTokenController extends Controller
{
    public function __invoke(IssueUserApiTokenRequest $request, IssueUserApiTokenAction $action): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse($request, 401, 'authentication_required', 'Authentication required.');
        }

        $siteId = (string) $request->string('site_id');
        $scopes = $this->normalizeValues($request->input('scopes', []));
        $permissions = $this->normalizeValues($request->input('permissions', []));
        $doesNotExpire = $request->boolean('does_not_expire');
        $expiresAt = $doesNotExpire || ! $request->filled('expires_at')
            ? null
            : CarbonImmutable::parse((string) $request->input('expires_at'))->setTimezone('UTC');

        Log::info('[api.issuer.issue_user_api_token] started', [
            'user_id' => $user->getAuthIdentifier(),
            'site_id' => $siteId,
            'scopes_count' => count($scopes),
            'permissions_count' => count($permissions),
            'custom_expires_at' => $expiresAt?->toISOString(),
            'does_not_expire' => $doesNotExpire,
        ]);

        try {
            $issuedToken = $action->handle(
                userId: (int) $user->getAuthIdentifier(),
                siteId: $siteId,
                requestedScopes: $scopes,
                requestedPermissions: $permissions,
                expiresAt: $expiresAt,
                doesNotExpire: $doesNotExpire,
            );
        } catch (ApiResourceEligibilityException $exception) {
            return $this->errorResponse(
                $request,
                $exception->httpStatus(),
                $exception->errorCode(),
                $exception->getMessage(),
            );
        } catch (UnverifiedSiteException) {
            return $this->errorResponse(
                $request,
                403,
                'unverified_site_cannot_receive_production_credentials',
                'Site must be verified first.',
            );
        } catch (SigningKeyStateException $exception) {
            Log::error('[api.issuer.issue_user_api_token] signing_key_failure', [
                'user_id' => $user->getAuthIdentifier(),
                'site_id' => $siteId,
                'error_code' => $exception->getMessage(),
            ]);

            return $this->errorResponse(
                $request,
                503,
                $exception->getMessage(),
                'Signing key is not ready for token issuance.',
            );
        } catch (Throwable $exception) {
            Log::error('[api.issuer.issue_user_api_token] unexpected_failure', [
                'user_id' => $user->getAuthIdentifier(),
                'site_id' => $siteId,
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ]);

            return $this->errorResponse($request, 500, 'token_issue_failed', 'Token issuance failed.');
        }

        return response()->json([
            'token' => $issuedToken->rawToken,
            'token_id' => $issuedToken->tokenId,
            'token_type' => 'Bearer',
            'site_id' => $siteId,
            'aud' => $issuedToken->audience,
            'scope' => $issuedToken->scopes,
            'permissions' => $issuedToken->permissions,
            'jti' => $issuedToken->jti,
            'kid' => $issuedToken->kid,
            'expires_at' => $issuedToken->expiresAt?->toISOString(),
        ], 201);
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

        return array_values(array_unique($normalized));
    }

    private function errorResponse(Request $request, int $status, string $error, string $message): JsonResponse
    {
        return response()->json([
            'error' => $error,
            'message' => $message,
            'request_id' => $request->attributes->get('request_id'),
        ], $status);
    }
}
