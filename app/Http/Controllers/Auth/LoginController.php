<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Events\PasswordLoginSucceeded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\SafeLogContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $email = mb_strtolower((string) $request->string('email'));
        $emailHash = hash('sha256', $email);

        Log::info('[auth.login] started', SafeLogContext::from([
            'email_hash' => $emailHash,
        ]));

        $authenticated = Auth::attempt([
            'email' => $email,
            'password' => (string) $request->string('password'),
        ], $request->boolean('remember'));

        if (! $authenticated) {
            Log::warning('[auth.login] credentials rejected', SafeLogContext::from([
                'email_hash' => $emailHash,
            ]));

            if (! $request->expectsJson()) {
                return back()
                    ->withErrors(['email' => 'Provided credentials are invalid.'])
                    ->onlyInput('email');
            }

            return $this->errorResponse($request, 422, 'invalid_credentials', 'Provided credentials are invalid.');
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user === null) {
            Log::error('[auth.login] authenticated user missing after login', SafeLogContext::from([
                'email_hash' => $emailHash,
            ]));

            return $this->errorResponse($request, 500, 'authentication_context_missing', 'Unable to complete login.');
        }

        PasswordLoginSucceeded::dispatch($user, false);

        Log::info('[auth.login] completed', SafeLogContext::from([
            'user_id' => $user->getAuthIdentifier(),
            'email_hash' => $emailHash,
        ]));

        if (! $request->expectsJson()) {
            return redirect()->intended(route('portal.dashboard'));
        }

        return response()->json([
            'user_id' => $user->getAuthIdentifier(),
            'name' => $user->name,
            'email' => $user->email,
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
