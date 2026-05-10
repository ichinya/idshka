<?php

namespace App\Http\Middleware;

use App\Domain\OidcClients\Models\OidcRedirectUri;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetSecurityHeaders
{
    private const JSON_CONTENT_SECURITY_POLICY =
        "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'";

    private const DOCUMENT_CONTENT_SECURITY_POLICY =
        "default-src 'none'; base-uri 'self'; form-action %s; frame-ancestors 'none'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; font-src 'self' data: https:; script-src 'self'; connect-src 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $contentType = (string) $response->headers->get('Content-Type', '');

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicyFor($request, $contentType));
        }

        // `isSecure()` will honor forwarded proto only when Laravel trusts the proxy chain.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicyFor(Request $request, string $contentType): string
    {
        if (str_starts_with($contentType, 'application/json')) {
            return self::JSON_CONTENT_SECURITY_POLICY;
        }

        return sprintf(self::DOCUMENT_CONTENT_SECURITY_POLICY, $this->documentFormActionFor($request));
    }

    private function documentFormActionFor(Request $request): string
    {
        $sources = ["'self'"];
        $oauthRedirectOrigin = $this->registeredOAuthRedirectOriginForLogin($request);

        if ($oauthRedirectOrigin !== null) {
            $sources[] = $oauthRedirectOrigin;
        }

        return implode(' ', array_unique($sources));
    }

    private function registeredOAuthRedirectOriginForLogin(Request $request): ?string
    {
        if (! $request->routeIs('login', 'register') || ! $request->hasSession()) {
            return null;
        }

        $intendedUrl = $request->session()->get('url.intended');

        if (! is_string($intendedUrl)) {
            return null;
        }

        $intendedParts = parse_url($intendedUrl);

        if (($intendedParts['path'] ?? null) !== '/oauth/authorize') {
            return null;
        }

        $query = [];
        parse_str((string) ($intendedParts['query'] ?? ''), $query);

        $clientId = $query['client_id'] ?? null;
        $redirectUri = $query['redirect_uri'] ?? null;

        if (! is_string($clientId) || ! is_string($redirectUri)) {
            return null;
        }

        if (! $this->isRegisteredRedirectUri($clientId, $redirectUri)) {
            return null;
        }

        return $this->originFor($redirectUri);
    }

    private function isRegisteredRedirectUri(string $clientId, string $redirectUri): bool
    {
        return OidcRedirectUri::query()
            ->where('redirect_uri_hash', hash('sha256', $redirectUri))
            ->where('redirect_uri', $redirectUri)
            ->whereHas('client', function ($query) use ($clientId): void {
                $query
                    ->where('client_id', $clientId)
                    ->whereNull('revoked_at');
            })
            ->exists();
    }

    private function originFor(string $url): ?string
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;

        if (! is_string($scheme) || ! is_string($host)) {
            return null;
        }

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $origin = $scheme.'://'.$host;
        $port = $parts['port'] ?? null;

        if (is_int($port)) {
            $origin .= ':'.$port;
        }

        return $origin;
    }
}
