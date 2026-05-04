<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Support\SafeLogContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

final class SocialiteRedirectController extends Controller
{
    private const SESSION_INTENT_KEY = 'identity.socialite.intent';

    public function __invoke(Request $request, string $provider): JsonResponse|RedirectResponse
    {
        $resolvedProvider = SocialProvider::tryFromRoute($provider);

        if ($resolvedProvider === null) {
            return $this->errorResponse($request, 404, 'unsupported_provider', 'Unsupported social provider.');
        }

        Log::info('[auth.social.redirect] started', SafeLogContext::from([
            'provider' => $resolvedProvider->value,
            'intent' => 'login',
        ]));

        $request->session()->put(self::SESSION_INTENT_KEY, [
            'intent' => 'login',
            'provider' => $resolvedProvider->value,
            'created_at' => now()->toIso8601String(),
        ]);

        try {
            return Socialite::driver($resolvedProvider->driver())
                ->scopes($resolvedProvider->scopes())
                ->redirect();
        } catch (Throwable $exception) {
            Log::error('[auth.social.redirect] provider redirect failed', SafeLogContext::from([
                'provider' => $resolvedProvider->value,
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ]));

            return $this->errorResponse($request, 503, 'provider_unavailable', 'Social provider is unavailable.');
        }
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
