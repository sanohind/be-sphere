<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | For OIDC, we need to allow cross-origin requests from client applications
    |
    */

    'paths' => [
        'api/*',
        'oauth/*',              // ✅ OIDC endpoints
        '.well-known/*',        // ✅ OIDC discovery
        'sanctum/csrf-cookie'
    ],

    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),

    'allowed_origins' => env('APP_ENV') === 'production' 
        ? explode(',', env('CORS_ALLOWED_ORIGINS', ''))
        : ['*'], // Allow all in development

    'allowed_origins_patterns' => [],

    'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With,Accept')),

    'exposed_headers' => [
        'Authorization',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
    ],

    'max_age' => 86400, // 24 hours (cache preflight requests)

    'supports_credentials' => filter_var(env('CORS_ALLOW_CREDENTIALS', true), FILTER_VALIDATE_BOOLEAN),
];
