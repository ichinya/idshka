<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Events\PasswordLoginSucceeded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class RegisterUserController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse|RedirectResponse
    {
        $email = mb_strtolower((string) $request->string('email'));
        $emailHash = hash('sha256', $email);

        Log::info('[auth.register] started', [
            'email_hash' => $emailHash,
        ]);

        $user = User::query()->create([
            'name' => (string) $request->string('name'),
            'email' => $email,
            'password' => (string) $request->string('password'),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        PasswordLoginSucceeded::dispatch($user, true);

        Log::info('[auth.register] completed', [
            'user_id' => $user->id,
            'email_hash' => $emailHash,
        ]);

        if (! $request->expectsJson()) {
            return redirect()->route('portal.dashboard');
        }

        return response()->json([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ], 201);
    }
}
