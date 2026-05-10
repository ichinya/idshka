<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\SetSecurityHeaders;
use App\Http\Middleware\ThrottleOAuthAuthorizeRequests;
use App\Support\SafeLogContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
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
        $middleware->redirectUsersTo(fn (): string => route('portal.dashboard'));
        $middleware->prependToPriorityList(AuthenticatesRequests::class, ThrottleOAuthAuthorizeRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            $requestId = $request->attributes->get('request_id');
            $headers = $exception->getHeaders();

            Log::warning('[rate_limit] exceeded', SafeLogContext::from([
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $request->path(),
                'route_name' => $request->route()?->getName(),
                'expects_json' => $request->expectsJson(),
                'retry_after' => isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null,
            ]));

            if ($request->expectsJson() || $request->is('api/*') || $request->is('oauth/*')) {
                return response()->json([
                    'error' => 'rate_limited',
                    'message' => 'Too many requests.',
                    'request_id' => $requestId,
                ], 429, $headers + [
                    'X-Request-Id' => (string) $requestId,
                ]);
            }

            if ($request->is('portal/*')) {
                return redirect()
                    ->back()
                    ->withErrors(['rate_limit' => 'Too many requests.'])
                    ->with('request_id', $requestId)
                    ->withHeaders($headers + [
                        'X-Request-Id' => (string) $requestId,
                    ]);
            }

            return null;
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('oauth/authorize')) {
                return null;
            }

            Log::warning('[oauth.authorize.authentication] required', SafeLogContext::from([
                'request_id' => $request->attributes->get('request_id'),
                'path' => $request->path(),
                'expects_json' => $request->expectsJson(),
            ]));

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
