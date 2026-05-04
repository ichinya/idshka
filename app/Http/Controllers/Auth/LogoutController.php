<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\SafeLogContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        Log::info('[auth.logout] started', SafeLogContext::from([
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]));

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('[auth.logout] completed', SafeLogContext::from());

        if (! $request->expectsJson()) {
            return redirect('/');
        }

        return response()->json([
            'status' => 'logged_out',
        ]);
    }
}
