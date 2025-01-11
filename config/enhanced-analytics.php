<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure how the analytics data should be temporarily stored.
    | Supports both 'file' and 'redis' drivers. File storage is used by default
    | for out-of-the-box functionality, but Redis is recommended for
    | production environments with high traffic.
    |
    */
    'cache' => [
        // Cache driver: 'file' or 'redis'
        'driver' => env('ENHANCED_ANALYTICS_CACHE_DRIVER', 'file'),

        // Common settings
        'ttl' => env('ENHANCED_ANALYTICS_CACHE_TTL', 60 * 60 * 24), // 24 hours

        // Redis-specific settings
        'redis' => [
            'connection' => env('ENHANCED_ANALYTICS_REDIS_CONNECTION', 'default'),
            'prefix' => env('ENHANCED_ANALYTICS_REDIS_PREFIX', 'enhanced_analytics_'),
        ],

        // File-specific settings
        'file' => [
            'path' => env(
                'ENHANCED_ANALYTICS_STORAGE_PATH',
                storage_path('app/enhanced-analytics')
            ),
            'permissions' => [
                'file' => 0644,
                'directory' => 0755
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Schedule
    |--------------------------------------------------------------------------
    |
    | Configure how often the analytics data should be processed and
    | stored in the database.
    |
    */
    'processing' => [
        'frequency' => env('ENHANCED_ANALYTICS_PROCESSING_FREQUENCY', 15), // minutes
        'chunk_size' => env('ENHANCED_ANALYTICS_CHUNK_SIZE', 1000),
        'lock_timeout' => env('ENHANCED_ANALYTICS_LOCK_TIMEOUT', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking Settings
    |--------------------------------------------------------------------------
    |
    | Configure what data should be tracked and any exclusions.
    |
    */
    'tracking' => [
        'exclude_ips' => [],
        'exclude_paths' => [
            'cp/*',
            '_debugbar/*',
        ],
        'exclude_bots' => true,
        'track_authenticated_users' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configure the dashboard appearance and default filters.
    |
    */
    'dashboard' => [
        'default_range' => '7days', // Options: 24hours, 7days, 30days, custom
        'refresh_interval' => 5 * 60, // 5 minutes (in seconds)
    ],
]; 