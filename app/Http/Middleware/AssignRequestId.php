<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolveRequestId($request);

        $request->attributes->set('request_id', $requestId);
        $request->headers->set('X-Request-Id', $requestId);

        Log::shareContext([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        Log::info('[request.lifecycle] request started', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        Log::info('[request.lifecycle] request completed', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
        ]);

        return $response;
    }

    private function resolveRequestId(Request $request): string
    {
        $incoming = trim((string) $request->headers->get('X-Request-Id', ''));

        if ($incoming === '') {
            return (string) Str::uuid();
        }

        $sanitized = preg_replace('/[^A-Za-z0-9._:-]/', '', $incoming) ?? '';
        $sanitized = Str::limit($sanitized, 80, '');

        return $sanitized !== '' ? $sanitized : (string) Str::uuid();
    }
}
