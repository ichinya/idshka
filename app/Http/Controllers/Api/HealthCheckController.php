<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class HealthCheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $requestId = $request->attributes->get('request_id');

        Log::info('[health.check] started', [
            'request_id' => $requestId,
            'path' => $request->path(),
        ]);

        $payload = [
            'service' => config('app.name'),
            'status' => 'ok',
            'request_id' => $requestId,
            'timestamp' => now()->toISOString(),
        ];

        Log::info('[health.check] completed', $payload);

        return response()->json($payload);
    }
}
