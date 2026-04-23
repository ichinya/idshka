<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Log::info('[auth.logout] started', [
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('[auth.logout] completed');

        return response()->json([
            'status' => 'logged_out',
        ]);
    }
}
