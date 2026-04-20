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

        Log::info('[FIX:probe-surface] returning sanitized readiness payload', [
            'request_id' => $requestId,
            'status' => $payload['status'],
            'components' => array_keys($checks),
        ]);

        Log::info('[readiness.check] completed', $payload);

        return response()
            ->json($payload, $isReady ? 200 : 503)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->select('select 1');

            return [
                'status' => 'ok',
            ];
        } catch (Throwable $exception) {
            Log::warning('[FIX:probe-surface] database readiness failed', [
                'connection' => DB::getDefaultConnection(),
                'exception_class' => $exception::class,
            ]);

            Log::error('[readiness.check.database] failed', [
                'connection' => DB::getDefaultConnection(),
                'exception_class' => $exception::class,
            ]);

            return [
                'status' => 'failed',
            ];
        }
    }

    private function checkRedis(): array
    {
        if (! $this->shouldCheckRedis()) {
            return [
                'status' => 'skipped',
            ];
        }

        try {
            Redis::connection()->ping();

            return [
                'status' => 'ok',
            ];
        } catch (Throwable $exception) {
            Log::warning('[FIX:probe-surface] redis readiness failed', [
                'connection' => 'default',
                'exception_class' => $exception::class,
            ]);

            Log::error('[readiness.check.redis] failed', [
                'connection' => 'default',
                'exception_class' => $exception::class,
            ]);

            return [
                'status' => 'failed',
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
