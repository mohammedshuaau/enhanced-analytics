# Enhanced Analytics for Statamic

A powerful analytics addon for Statamic that provides detailed insights into your website's traffic and user behavior.

## Features

### Real-Time Analytics
- Page view tracking with automatic data processing
- Unique visitor identification and session management
- Configurable processing frequency (default: every 15 minutes)
- Automatic data aggregation for efficient querying

### Visitor Insights
- Total visits and unique visitors
- New vs returning visitors
- Session duration and pages per session
- Bounce rate and exit rate analysis
- User flow tracking (entry pages, engaged pages, exit pages)

### Geographic Data
- IP-based geolocation using ip-api.com
- Country and city-level tracking
- Built-in rate limiting (45 requests/minute)
- Automatic caching of geolocation data
- Configurable cache duration

### Technical Insights
- Device type tracking (desktop, mobile, tablet)
- Browser and platform detection
- Referrer URL tracking
- User agent analysis

### Performance Features
- Efficient data caching system
- Support for both file caching
- Automatic cleanup of old data
- Chunk-based processing to prevent memory issues
- Lock system to prevent concurrent processing

### Dashboard Features
- Clean, modern interface with dark mode support
- Polled data refresh
- Customizable date ranges (24h, 7d, 30d, custom)
- Comparative metrics with previous periods
- Export functionality for detailed analysis
- Interactive charts and visualizations

### Privacy & Security
- No external service dependencies
- Complete data ownership
- Configurable IP address exclusions
- Bot filtering
- Authenticated user tracking options

## Installation

1. Install the package via Composer:
```bash
composer require mohammedshuaau/enhanced-analytics
```

2. Publish the configuration:
```bash
php artisan vendor:publish --tag=enhanced-analytics-config
```

3. Run the migrations:
```bash
php artisan migrate
```

## Configuration

The addon can be configured via the `config/enhanced-analytics.php` file:

```php
return [
    'cache' => [
        'driver' => 'file', // Options: 'file', 'redis'
        'file' => [
            'path' => storage_path('app/enhanced-analytics'),
            'permissions' => [
                'file' => 0644,
                'directory' => 0755
            ]
        ]
    ],

    'geolocation' => [
        'cache_duration' => 1440, // 24 hours
        'rate_limit' => 45 // requests per minute
    ],

    'processing' => [
        'frequency' => 15, // minutes
        'chunk_size' => 1000,
        'lock_timeout' => 60
    ],

    'tracking' => [
        'exclude_paths' => [
            'cp/*',
            'api/*'
        ],
        'exclude_ips' => [],
        'exclude_bots' => true,
        'track_authenticated_users' => true
    ]
];
```

## Usage

Once installed, the addon will automatically start tracking page visits. Access the analytics dashboard via the Control Panel under Tools > Analytics.

### Automatic Processing

The addon automatically processes analytics data via Scheduler. You might want to run the scheduler and the addon will handle the rest. You can always set the minutes the command should execute.

### Manual Processing

You can manually process analytics data using the command:
```bash
php artisan analytics:process
```

### Data Export

Export detailed analytics data directly from the dashboard in CSV format for further analysis.

## Performance Considerations

- The addon uses efficient caching and processes data in chunks to maintain performance
- Geolocation data is cached to respect API rate limits
- Database queries are optimized using aggregates for faster dashboard loading
- Automatic cleanup of old cache data

### Upcoming Features

- Support for Redis Driver


## Contributing

Contributions are always welcome!

### Development Guidelines

- Follow PSR-12 coding standards
- Add appropriate comments and documentation
- Update the README.md with details of significant changes
- Add/update tests as needed
- Ensure all tests pass before submitting PR

### Testing

Run the test suite:
```bash
vendor/bin/phpunit
```

## Support

If you discover any security-related issues, please use the issue tracker to report them.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
