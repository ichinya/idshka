<?php

namespace App\Http\Controllers\Api\Sites;

use App\Domain\Sites\Actions\VerifySiteDomainAction;
use App\Domain\Sites\Enums\SiteVerificationMethod;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerifySiteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class VerifySiteController extends Controller
{
    public function __invoke(Site $site, VerifySiteRequest $request, VerifySiteDomainAction $action): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse($request, 401, 'authentication_required', 'Authentication required.');
        }

        if ((int) $user->getAuthIdentifier() !== $site->owner_user_id) {
            return $this->errorResponse($request, 403, 'forbidden', 'You do not own this site.');
        }

        $method = SiteVerificationMethod::from((string) $request->string('method'));

        Log::info('[api.site.verify] started', [
            'site_id' => $site->id,
            'user_id' => $user->getAuthIdentifier(),
            'method' => $method->value,
        ]);

        $verification = $action->handle($site, $method);

        return response()->json([
            'site_id' => $site->id,
            'method' => $method->value,
            'status' => $verification->status,
            'verified' => $site->refresh()->isVerified(),
            'error_code' => $verification->last_error,
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
