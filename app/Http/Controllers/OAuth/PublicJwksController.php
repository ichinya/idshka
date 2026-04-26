<?php

namespace App\Http\Controllers\OAuth;

use App\Domain\Issuer\Exceptions\SigningKeyStateException;
use App\Domain\Issuer\Services\JwksService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PublicJwksController extends Controller
{
    public function __invoke(Request $request, JwksService $jwksService): JsonResponse
    {
        try {
            $payload = $jwksService->getPublicJwks();
        } catch (SigningKeyStateException $exception) {
            Log::warning('[oauth.jwks.public] unavailable', [
                'error_code' => $exception->getMessage(),
            ]);

            return response()->json([
                'error' => $exception->getMessage(),
                'message' => 'JWKS is temporarily unavailable.',
                'request_id' => $request->attributes->get('request_id'),
            ], 503);
        }

        $cacheSeconds = max(1, (int) config('issuer.jwks.cache_seconds', 120));

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age='.$cacheSeconds.', must-revalidate')
            ->header('Pragma', 'public');
    }
}
