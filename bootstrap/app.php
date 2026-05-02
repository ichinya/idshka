<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\SetSecurityHeaders;
use App\Http\Middleware\ThrottleOAuthAuthorizeRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

PreventRequestsDuringMaintenance::except('/up');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function (): void {
            Route::middleware('api')->group(base_path('routes/probes.php'));
            Route::middleware('api')->group(base_path('routes/oauth.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);
        $middleware->append(SetSecurityHeaders::class);
        $middleware->alias([
            'throttle.oauth-authorize' => ThrottleOAuthAuthorizeRequests::class,
        ]);
        $middleware->prependToPriorityList(AuthenticatesRequests::class, ThrottleOAuthAuthorizeRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('oauth/authorize')) {
                return null;
            }

            Log::warning('[oauth.authorize.authentication] required', [
                'request_id' => $request->attributes->get('request_id'),
                'path' => $request->path(),
                'expects_json' => $request->expectsJson(),
            ]);

            if (! $request->expectsJson()) {
                return redirect()->guest(route('login'));
            }

            return response()->json([
                'error' => 'authentication_required',
                'message' => 'Authentication required.',
                'request_id' => $request->attributes->get('request_id'),
            ], 401);
        });
    })->create();
