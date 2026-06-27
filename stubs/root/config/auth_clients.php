<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Product client auth configuration
    |--------------------------------------------------------------------------
    |
    | Keep client IDs stable because issued sessions and refresh tokens are
    | scoped to client_id + product_code + device_id.
    |
    */

    'clients' => [
        'web_starter' => [
            'name' => 'Starter Web App',
            'product_code' => 'starter',
            'token_name' => 'web:starter',
            'redirect' => env('APP_URL', 'http://localhost').'/auth/google/callback',
            'confidential' => false,
        ],
        'chrome_starter' => [
            'name' => 'Starter Browser Extension',
            'product_code' => 'starter',
            'token_name' => 'ext:starter',
            'redirect' => env('PRODUCT_KIT_CHROME_REDIRECT_URI', 'https://example.chromiumapp.org/callback'),
            'confidential' => false,
        ],
        'app_mobile' => [
            'name' => 'Starter Mobile App',
            'product_code' => 'starter',
            'token_name' => 'app:mobile',
            'redirect' => env('PRODUCT_KIT_MOBILE_REDIRECT_URI', 'starter://callback'),
            'confidential' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Login and permission mapping (legacy bridge)
    |--------------------------------------------------------------------------
    */
    'valid_clients' => [
        'web' => 'web:starter',
        'web_starter' => 'web:starter',
        'chrome_starter' => 'ext:starter',
        'app_mobile' => 'app:mobile',
    ],

    'client_scopes' => [
        'web:starter' => ['api'],
        'ext:starter' => ['api'],
        'app:mobile' => ['api'],
    ],

    'whitelists' => [
        'uri_schemes' => ['vscode', 'cursor', 'windsurf', 'zed'],
        'https_domains' => [
            'github.dev',
            'vscode.dev',
            'chromiumapp.org',
            'google.com',
        ],
    ],
];
