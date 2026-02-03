<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OAuth2 Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for League OAuth2 Server implementation
    |
    */

    // ✅ Encryption key for tokens
    'encryption_key' => env('OAUTH_ENCRYPTION_KEY', 'XkHeSdTfAwO7v+6rLNBrWlT4aOzusyTusgde8eV6LPw='),

    // ✅ Token lifetimes (in seconds)
    'access_token_ttl' => env('OAUTH_ACCESS_TOKEN_TTL', 3600), // 60 minutes
    'refresh_token_ttl' => env('OAUTH_REFRESH_TOKEN_TTL', 2592000), // 30 days
    'auth_code_ttl' => env('OAUTH_AUTH_CODE_TTL', 600), // 10 minutes

    // ✅ Private and public keys path
    'private_key' => storage_path('oauth/private.key'),
    'public_key' => storage_path('oauth/public.key'),
    'passphrase' => env('OAUTH_PRIVATE_KEY_PASSPHRASE', null),

    // ✅ PKCE (Proof Key for Code Exchange)
    'require_code_challenge_for_public_clients' => true,

    // ✅ Registered OAuth clients
    'clients' => [
        [
            'id' => 'scope-client',
            'name' => 'SCOPE Application',
            'secret' => env('SCOPE_CLIENT_SECRET'),
            'redirect' => env('SCOPE_CALLBACK_URL', 'http://localhost:5173/#/callback'),
            'is_confidential' => false, // SPA = public client
            'scopes' => ['openid', 'profile', 'email', 'roles', 'departments'],
        ],
        [
            'id' => 'ams-client',
            'name' => 'AMS Application',
            'secret' => env('AMS_CLIENT_SECRET'),
            'redirect' => env('AMS_CALLBACK_URL', 'http://localhost:5174/#/callback'),
            'is_confidential' => false, // SPA = public client
            'scopes' => ['openid', 'profile', 'email', 'roles', 'departments'],
        ],
    ],

    // ✅ Available scopes
    'scopes' => [
        'openid' => 'OpenID Connect',
        'profile' => 'User profile information',
        'email' => 'User email address',
        'roles' => 'User roles and permissions',
        'departments' => 'User department information',
    ],

    // ✅ Default scopes
    'default_scopes' => ['openid', 'profile', 'email'],

    // ✅ OIDC Claims mapping
    'claims' => [
        'profile' => ['name', 'username', 'picture'],
        'email' => ['email', 'email_verified'],
        'roles' => ['role', 'role_level'],
        'departments' => ['department_id', 'department_name'],
    ],
];
