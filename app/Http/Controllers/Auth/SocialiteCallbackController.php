<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\HandleSocialCallbackAction;
use App\Domain\Identity\Enums\SocialProvider;
use App\Domain\Identity\Exceptions\SocialIdentityConflictException;
use App\Http\Controllers\Controller;
use App\Support\SafeLogContext;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

final class SocialiteCallbackController extends Controller
{
    private const SESSION_INTENT_KEY = 'identity.socialite.intent';

    public function __invoke(
        Request $request,
        string $provider,
        HandleSocialCallbackAction $action,
    ): JsonResponse {
        $resolvedProvider = SocialProvider::tryFromRoute($provider);

        if ($resolvedProvider === null) {
            return $this->errorResponse($request, 404, 'unsupported_provider', 'Unsupported social provider.');
        }

        if ($request->filled('error')) {
            Log::warning('[auth.social.callback] provider denied authentication', SafeLogContext::from([
                'provider' => $resolvedProvider->value,
                'provider_error' => $request->query('error'),
            ]));

            return $this->errorResponse($request, 401, 'social_auth_denied', 'Social provider denied authentication.');
        }

        $intent = $request->session()->pull(self::SESSION_INTENT_KEY);

        if (! is_array($intent) || ($intent['provider'] ?? null) !== $resolvedProvider->value) {
            Log::warning('[auth.social.callback] missing or mismatched session intent', SafeLogContext::from([
                'provider' => $resolvedProvider->value,
                'session_intent_provider' => is_array($intent) ? ($intent['provider'] ?? null) : null,
            ]));

            return $this->errorResponse($request, 419, 'social_state_mismatch', 'Social auth session has expired.');
        }

        Log::info('[auth.social.callback] started', SafeLogContext::from([
            'provider' => $resolvedProvider->value,
            'intent' => $intent['intent'] ?? null,
        ]));

        $linkingUser = $this->resolveLinkingUser($request, $intent);

        if (($intent['intent'] ?? null) === 'link' && $linkingUser === null) {
            Log::warning('[auth.social.callback] linking authentication context missing', SafeLogContext::from([
                'provider' => $resolvedProvider->value,
                'intent_user_id' => is_numeric($intent['user_id'] ?? null) ? (int) $intent['user_id'] : null,
                'authenticated_user_id' => $request->user()?->getAuthIdentifier(),
            ]));

            return $this->errorResponse($request, 401, 'linking_auth_required', 'Linking requires an active authenticated session.');
        }

        try {
            $socialiteUser = Socialite::driver($resolvedProvider->driver())->user();
        } catch (Throwable $exception) {
            Log::warning('[auth.social.callback] provider callback failed', SafeLogContext::from([
                'provider' => $resolvedProvider->value,
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
            ]));

            return $this->errorResponse($request, 401, 'provider_auth_failed', 'Unable to authenticate with social provider.');
        }

        try {
            $result = $action->handle($resolvedProvider, $socialiteUser, $linkingUser);
        } catch (SocialIdentityConflictException $exception) {
            Log::warning('[auth.social.callback] social identity conflict', SafeLogContext::from([
                'provider' => $resolvedProvider->value,
                'error_code' => $exception->getMessage(),
                'linking_user_id' => $linkingUser?->id,
            ]));

            return $this->errorResponse($request, 409, $exception->getMessage(), 'Cannot attach social account with current context.');
        } catch (InvalidArgumentException $exception) {
            Log::warning('[auth.social.callback] provider profile invalid', SafeLogContext::from([
                'provider' => $resolvedProvider->value,
                'error_code' => $exception->getMessage(),
            ]));

            return $this->errorResponse($request, 422, 'invalid_provider_payload', 'Provider returned invalid user profile data.');
        }

        if (! $result->linkingFlow) {
            Auth::login($result->user);
            $request->session()->regenerate();
        }

        Log::info('[auth.social.callback] completed', SafeLogContext::from([
            'provider' => $resolvedProvider->value,
            'user_id' => $result->user->id,
            'linking_flow' => $result->linkingFlow,
            'linked' => $result->linked,
            'created_user' => $result->createdUser,
        ]));

        return response()->json([
            'provider' => $resolvedProvider->value,
            'user_id' => $result->user->id,
            'linking_flow' => $result->linkingFlow,
            'linked' => $result->linked,
            'created_user' => $result->createdUser,
        ]);
    }

    /**
     * @param  array<string, mixed>  $intent
     */
    private function resolveLinkingUser(Request $request, array $intent): ?User
    {
        if (($intent['intent'] ?? null) !== 'link') {
            return null;
        }

        $userId = is_numeric($intent['user_id'] ?? null) ? (int) $intent['user_id'] : 0;

        if ($userId < 1) {
            return null;
        }

        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            return null;
        }

        if ((int) $authenticatedUser->getAuthIdentifier() !== $userId) {
            return null;
        }

        return $authenticatedUser;
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
