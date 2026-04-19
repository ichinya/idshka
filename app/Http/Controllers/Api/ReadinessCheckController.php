<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class ReadinessCheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $requestId = $request->attributes->get('request_id');

        Log::info('[readiness.check] started', [
            'request_id' => $requestId,
            'path' => $request->path(),
        ]);

        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $isReady = collect($checks)
            ->every(fn (array $check): bool => in_array($check['status'], ['ok', 'skipped'], true));

        $payload = [
            'service' => config('app.name'),
            'status' => $isReady ? 'ok' : 'degraded',
            'request_id' => $requestId,
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ];

        Log::info('[readiness.check] completed', $payload);

        return response()->json($payload, $isReady ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->select('select 1');

            return [
                'status' => 'ok',
                'connection' => DB::getDefaultConnection(),
            ];
        } catch (Throwable $exception) {
            Log::error('[readiness.check.database] failed', [
                'connection' => DB::getDefaultConnection(),
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'connection' => DB::getDefaultConnection(),
            ];
        }
    }

    private function checkRedis(): array
    {
        if (! $this->shouldCheckRedis()) {
            return [
                'status' => 'skipped',
                'connection' => 'default',
                'reason' => 'redis is not an active runtime dependency for the current environment',
            ];
        }

        try {
            $response = Redis::connection()->ping();

            return [
                'status' => 'ok',
                'connection' => 'default',
                'response' => is_scalar($response) ? (string) $response : 'PONG',
            ];
        } catch (Throwable $exception) {
            Log::error('[readiness.check.redis] failed', [
                'connection' => 'default',
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'connection' => 'default',
            ];
        }
    }

    private function shouldCheckRedis(): bool
    {
        return collect([
            config('cache.default'),
            config('session.driver'),
            config('queue.default'),
        ])->contains('redis');
    }
}
