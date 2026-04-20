<?php

use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\ReadinessCheckController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request): JsonResponse {
    $requestId = $request->attributes->get('request_id');

    Log::info('[FIX:probe-surface] returning stateless foundation metadata', [
        'request_id' => $requestId,
        'path' => $request->path(),
    ]);

    return response()
        ->json([
            'service' => config('app.name'),
            'status' => 'foundation-ready',
            'request_id' => $requestId,
            'routes' => [
                'health' => url('/health'),
                'readiness' => url('/ready'),
                'up' => url('/up'),
            ],
        ])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
        ->header('Pragma', 'no-cache');
});

Route::get('/health', HealthCheckController::class)->name('foundation.health');
Route::get('/ready', ReadinessCheckController::class)->name('foundation.ready');
