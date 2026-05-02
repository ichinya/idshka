<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local loopback site registration
    |--------------------------------------------------------------------------
    |
    | This is intended only for local Docker/OAuth development where a web
    | client runs on https://localhost with a self-signed certificate.
    |
    */
    'allow_loopback_domains' => env(
        'SITE_REGISTRY_ALLOW_LOOPBACK_DOMAINS',
        env('APP_ENV') === 'local',
    ),
];
