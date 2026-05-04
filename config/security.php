<?php

return [
    'rate_limits' => [
        'auth_login' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_AUTH_LOGIN_PER_MINUTE', 10),
        ],
        'auth_social' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_AUTH_SOCIAL_PER_MINUTE', 30),
        ],
        'site_registry' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_SITE_REGISTRY_PER_MINUTE', 30),
        ],
        'site_verification' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_SITE_VERIFICATION_PER_MINUTE', 20),
        ],
        'oauth_authorize' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_OAUTH_AUTHORIZE_PER_MINUTE', 60),
        ],
        'oauth_token' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_OAUTH_TOKEN_PER_MINUTE', 60),
        ],
        'oauth_userinfo' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_OAUTH_USERINFO_PER_MINUTE', 120),
        ],
        'jwks_public' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_JWKS_PUBLIC_PER_MINUTE', 120),
        ],
        'token_issue' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_TOKEN_ISSUE_PER_MINUTE', 20),
        ],
        'token_revoke' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_TOKEN_REVOKE_PER_MINUTE', 40),
        ],
        'portal_site_write' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_PORTAL_SITE_WRITE_PER_MINUTE', 30),
        ],
        'portal_verification' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_PORTAL_VERIFICATION_PER_MINUTE', 20),
        ],
        'portal_credential_issue' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_PORTAL_CREDENTIAL_ISSUE_PER_MINUTE', 20),
        ],
        'portal_credential_revoke' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_PORTAL_CREDENTIAL_REVOKE_PER_MINUTE', 40),
        ],
        'portal_client_write' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_PORTAL_CLIENT_WRITE_PER_MINUTE', 20),
        ],
        'portal_redirect_uri_write' => [
            'per_minute' => env('SECURITY_RATE_LIMIT_PORTAL_REDIRECT_URI_WRITE_PER_MINUTE', 30),
        ],
    ],
];
