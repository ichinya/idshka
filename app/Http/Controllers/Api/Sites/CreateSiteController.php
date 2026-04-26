<?php

namespace App\Http\Controllers\Api\Sites;

use App\Domain\Sites\Actions\CreateSiteAction;
use App\Domain\Sites\Exceptions\SiteDomainConflictException;
use App\Domain\Sites\Models\SiteVerification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateSiteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class CreateSiteController extends Controller
{
    public function __invoke(CreateSiteRequest $request, CreateSiteAction $action): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse($request, 401, 'authentication_required', 'Authentication required.');
        }

        Log::info('[api.site.create] started', [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        try {
            $site = $action->handle(
                ownerUserId: (int) $user->getAuthIdentifier(),
                domain: (string) $request->string('domain'),
                displayName: $request->filled('display_name') ? (string) $request->string('display_name') : null,
            );
        } catch (InvalidArgumentException) {
            return $this->errorResponse($request, 422, 'invalid_domain', 'Domain must contain a valid host name.');
        } catch (SiteDomainConflictException $exception) {
            return $this->errorResponse($request, 409, $exception->getMessage(), 'Domain cannot be attached to this owner.');
        }

        /** @var SiteVerification $dns */
        $dns = $site->verifications()->where('method', 'dns_txt')->latest('id')->firstOrFail();
        /** @var SiteVerification $file */
        $file = $site->verifications()->where('method', 'file')->latest('id')->firstOrFail();

        return response()->json([
            'site_id' => $site->id,
            'domain' => $site->normalized_domain,
            'verified' => $site->isVerified(),
            'verification' => [
                'dns_txt_name' => '_idshka.'.$site->normalized_domain,
                'dns_txt_value' => 'idshka-site-verification='.$dns->token,
                'file_url' => sprintf('https://%s/.well-known/idshka-site-verification.txt', $site->normalized_domain),
                'file_body' => $file->token,
                'expires_at' => $dns->expires_at?->toISOString(),
            ],
        ], 201);
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
