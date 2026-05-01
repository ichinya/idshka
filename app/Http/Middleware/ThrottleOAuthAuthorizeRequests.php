<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleOAuthAuthorizeRequests
{
    private const LIMITER = 'oauth-authorize';

    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $limiter = $this->limiter->limiter(self::LIMITER);

        if ($limiter === null) {
            return $next($request);
        }

        $limit = $limiter($request);

        if ($limit instanceof Response) {
            return $limit;
        }

        $key = md5(self::LIMITER.$limit->key);

        if ($this->limiter->tooManyAttempts($key, $limit->maxAttempts)) {
            return $this->rateLimitedResponse($request, $key, $limit->maxAttempts);
        }

        $this->limiter->hit($key, $limit->decaySeconds);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $limit->maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->limiter->remaining($key, $limit->maxAttempts));

        return $response;
    }

    private function rateLimitedResponse(Request $request, string $key, int $maxAttempts): JsonResponse
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => 'rate_limited',
            'message' => 'Too many requests.',
            'request_id' => $request->attributes->get('request_id'),
        ], 429, [
            'Retry-After' => (string) $retryAfter,
            'X-RateLimit-Limit' => (string) $maxAttempts,
            'X-RateLimit-Remaining' => '0',
        ]);
    }
}
