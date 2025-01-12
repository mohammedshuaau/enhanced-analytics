<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure how analytics data is temporarily stored before processing.
    | The file driver is used by default for simplicity and reliability.
    |
    */
    'cache' => [
        'driver' => env('ENHANCED_ANALYTICS_CACHE_DRIVER', 'file'),

        // File driver specific settings
        'file' => [
            'path' => storage_path('app/enhanced-analytics'),
            'permissions' => [
                'file' => 0644,
                'directory' => 0755
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Geolocation Settings
    |--------------------------------------------------------------------------
    |
    | Configure settings for IP geolocation service.
    | The free tier of ip-api.com has a rate limit of 45 requests per minute.
    |
    */
    'geolocation' => [
        'cache_duration' => 60 * 24, // Cache IP geolocation data for 24 hours
        'rate_limit' => 45, // Requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Settings
    |--------------------------------------------------------------------------
    |
    | Configure how often analytics data is processed and how many records
    | are processed at once.
    |
    */
    'processing' => [
        'frequency' => 15, // minutes
        'chunk_size' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking Settings
    |--------------------------------------------------------------------------
    |
    | Configure which requests should be tracked.
    |
    */
    'tracking' => [
        'exclude_ips' => [
            '127.0.0.1',
        ],
        'exclude_paths' => [
            'cp/*',
            'api/*',
        ],
        'exclude_bots' => true,
        'track_authenticated_users' => true,
        'consent' => [
            'enabled' => false,
            'banner' => [
                'title' => 'Privacy Notice',
                'description' => 'We use analytics to understand how you use our website and improve your experience.',
                'accept_button' => 'Accept',
                'decline_button' => 'Decline',
                'settings_button' => 'Customize',
                'position' => 'bottom', // options: bottom, top, center
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configure the analytics dashboard behavior.
    |
    */
    'dashboard' => [
        'default_date_range' => '7days',
        'refresh_interval' => 300, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Misc Settings
    |--------------------------------------------------------------------------
    |
    |
    */
    'enable_debugging' => false
];
