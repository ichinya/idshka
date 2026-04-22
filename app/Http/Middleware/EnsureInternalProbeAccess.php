<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

final class EnsureInternalProbeAccess
{
    private const INTERNAL_IP_RANGES = [
        '127.0.0.1',
        '::1',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        'fc00::/7',
        'fe80::/10',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->attributes->get('request_id');
        $clientIp = $this->resolveClientIp($request);

        if ($clientIp === null || ! IpUtils::checkIp($clientIp, self::INTERNAL_IP_RANGES)) {
            Log::warning('[FIX:ready-access] rejecting non-internal readiness request', [
                'request_id' => $requestId,
                'path' => $request->path(),
                'client_ip' => $clientIp,
                'transport_ip' => $request->server('REMOTE_ADDR'),
                'forwarded_for' => $request->headers->get('X-Forwarded-For'),
            ]);

            abort(403);
        }

        Log::info('[FIX:ready-access] allowing internal readiness request', [
            'request_id' => $requestId,
            'path' => $request->path(),
            'client_ip' => $clientIp,
            'transport_ip' => $request->server('REMOTE_ADDR'),
        ]);

        return $next($request);
    }

    private function resolveClientIp(Request $request): ?string
    {
        // When readiness is reached through an internal proxy, prefer the original caller IP.
        $forwardedFor = trim((string) $request->headers->get('X-Forwarded-For', ''));

        if ($forwardedFor !== '') {
            foreach (explode(',', $forwardedFor) as $candidate) {
                $candidate = trim($candidate);

                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        $clientIp = $request->ip();

        return is_string($clientIp) && $clientIp !== '' ? $clientIp : null;
    }
}
