<?php

return [
    'enabled' => (bool) env('SITE_ANALYTICS_ENABLED', true),

    'ignored_path_prefixes' => [
        env('ADMIN_PATH', 'admin'),
        'api',
        'dashboard',
        'livewire',
        'stripe',
        'auth/google',
        'ott',
        '.well-known',
        'storage',
        'build',
        'images',
        'favicon',
    ],

    'bot_user_agent_keywords' => [
        'bot',
        'crawl',
        'spider',
        'slurp',
        'bingpreview',
        'facebookexternalhit',
        'headless',
        'monitor',
        'curl',
        'wget',
        'python-requests',
    ],
];
