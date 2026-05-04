<?php

namespace App\Http\Controllers\Api\Issuer;

use App\Domain\Issuer\Exceptions\IssuerFlowException;
use App\Domain\Issuer\Models\ApiToken;
use App\Domain\Issuer\Services\RevocationService;
use App\Http\Controllers\Controller;
use App\Support\SafeLogContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RevokeUserApiTokenController extends Controller
{
    public function __invoke(Request $request, string $id, RevocationService $revocationService): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse($request, 401, 'authentication_required', 'Authentication required.');
        }

        $userId = (int) $user->getAuthIdentifier();

        /** @var ApiToken|null $apiToken */
        $apiToken = ApiToken::query()
            ->whereKey($id)
            ->where('user_id', $userId)
            ->first();

        if ($apiToken === null) {
            Log::warning('[FIX:issuer-revoke-owner-scope] token revoke target not found for authenticated user', SafeLogContext::from([
                'user_id' => $userId,
                'api_token_id' => $id,
            ]));

            return $this->errorResponse($request, 404, 'token_not_found', 'Token not found.');
        }

        try {
            $revokedToken = $revocationService->revokeForUser($userId, $apiToken);
        } catch (IssuerFlowException $exception) {
            return $this->errorResponse(
                $request,
                $exception->httpStatus(),
                $exception->errorCode(),
                $exception->getMessage(),
            );
        } catch (Throwable $exception) {
            Log::error('[api.issuer.revoke_user_api_token] unexpected_failure', SafeLogContext::from([
                'api_token_id' => $apiToken->id,
                'user_id' => $user->getAuthIdentifier(),
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ]));

            return $this->errorResponse($request, 500, 'token_revoke_failed', 'Token revoke failed.');
        }

        return response()->json([
            'token_id' => $revokedToken->id,
            'jti' => $revokedToken->jti,
            'revoked_at' => $revokedToken->revoked_at?->toISOString(),
        ]);
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
