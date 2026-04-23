<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\UnlinkSocialAccountAction;
use App\Domain\Identity\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SocialiteUnlinkController extends Controller
{
    public function __invoke(
        Request $request,
        string $provider,
        UnlinkSocialAccountAction $action,
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse($request, 401, 'authentication_required', 'Authentication required.');
        }

        $resolvedProvider = SocialProvider::tryFromRoute($provider);

        if ($resolvedProvider === null) {
            return $this->errorResponse($request, 404, 'unsupported_provider', 'Unsupported social provider.');
        }

        $unlinked = $action->handle($user, $resolvedProvider);

        if (! $unlinked) {
            return $this->errorResponse($request, 404, 'social_account_not_linked', 'Social account is not linked.');
        }

        return response()->json([
            'provider' => $resolvedProvider->value,
            'unlinked' => true,
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
