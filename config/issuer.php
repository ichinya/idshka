<?php

use App\Contracts\Auth\Permissions;
use App\Contracts\Auth\Scopes;

return [
    'issuer' => env('ISSUER_IDENTIFIER', rtrim((string) env('APP_URL', 'http://localhost'), '/')),
    'user_api_token_ttl_seconds' => (int) env('ISSUER_USER_API_TOKEN_TTL_SECONDS', 900),
    'allowed_algs' => array_values(array_filter(array_map(
        static fn (string $algorithm): string => trim($algorithm),
        explode(',', (string) env('ISSUER_ALLOWED_ALGS', 'RS256')),
    ))),
    'keys' => [
        'rsa_bits' => (int) env('ISSUER_RSA_BITS', 2048),
    ],
    'jwks' => [
        'cache_seconds' => (int) env('ISSUER_JWKS_CACHE_SECONDS', 120),
    ],
    'revocation' => [
        'redis_denylist_enabled' => filter_var(
            env('ISSUER_REDIS_DENYLIST_ENABLED', true),
            FILTER_VALIDATE_BOOL,
        ),
        'cache_prefix' => env('ISSUER_REDIS_DENYLIST_PREFIX', 'issuer:denylist:jti:'),
    ],
    'api_resources' => [
        'allowed_scopes' => Scopes::all(),
        'allowed_permissions' => Permissions::all(),
    ],
];
