<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Product Usage Clients Configuration
    |--------------------------------------------------------------------------
    |
    | Define all supported product usage clients here. Each client has its own
    | event table, event types, and configuration options.
    |
    */

    'clients' => [
        'starter' => [
            'name' => 'Starter Product',
            'table' => 'product_usage_events_starter',
            'daily_table' => 'product_usage_daily_starter',
            'track_tokens' => true,
            'event_types' => [
                'chat_send',
                'prompt_use',
                'screenshot',
                'image_generate',
                'translate',
                'app_open',
                'app_close',
                'error',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Event Types
    |--------------------------------------------------------------------------
    |
    | These event types are available for all clients.
    |
    */

    'default_event_types' => [
        'app_open',
        'app_close',
        'error',
        'upgrade_click',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum events per minute per user to prevent abuse.
    |
    */

    'rate_limit' => [
        'events_per_minute' => 60,
    ],
];
