<?php

use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\ReadinessCheckController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request): JsonResponse {
    Log::info('[foundation.root] returning foundation metadata', [
        'request_id' => $request->attributes->get('request_id'),
        'method' => $request->method(),
        'path' => $request->path(),
    ]);

    return response()->json([
        'service' => config('app.name'),
        'status' => 'foundation-ready',
        'request_id' => $request->attributes->get('request_id'),
        'routes' => [
            'health' => url('/health'),
            'readiness' => url('/ready'),
            'up' => url('/up'),
        ],
    ]);
});

Route::get('/health', HealthCheckController::class)->name('foundation.health');
Route::get('/ready', ReadinessCheckController::class)->name('foundation.ready');
