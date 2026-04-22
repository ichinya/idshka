<?php

namespace App\Http\Controllers\Api\Sites;

use App\Domain\Sites\Actions\EnableSiteModeAction;
use App\Domain\Sites\Enums\SiteModeType;
use App\Domain\Sites\Exceptions\UnverifiedSiteException;
use App\Domain\Sites\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class EnableSiteModeController extends Controller
{
    public function __invoke(Request $request, Site $site, string $mode, EnableSiteModeAction $action): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse($request, 401, 'authentication_required', 'Authentication required.');
        }

        if ((int) $user->getAuthIdentifier() !== $site->owner_user_id) {
            return $this->errorResponse($request, 403, 'forbidden', 'You do not own this site.');
        }

        $modeEnum = match ($mode) {
            SiteModeType::ApiResource->value => SiteModeType::ApiResource,
            SiteModeType::WebClient->value => SiteModeType::WebClient,
            default => null,
        };

        if ($modeEnum === null) {
            return $this->errorResponse($request, 422, 'unsupported_mode', 'Unsupported site mode.');
        }

        Log::info('[api.site.mode.enable] started', [
            'site_id' => $site->id,
            'mode' => $modeEnum->value,
            'user_id' => $user->getAuthIdentifier(),
        ]);

        try {
            $siteMode = $action->handle($site, $modeEnum);
        } catch (UnverifiedSiteException $exception) {
            return $this->errorResponse($request, 403, $exception->getMessage(), 'Site must be verified first.');
        }

        return response()->json([
            'site_id' => $site->id,
            'mode' => $siteMode->mode,
            'enabled_at' => $siteMode->enabled_at?->toISOString(),
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
