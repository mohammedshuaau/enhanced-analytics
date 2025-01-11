# Enhanced Analytics for Statamic

A powerful, efficient analytics solution for Statamic with real-time tracking, caching, and comprehensive dashboard visualization.

## Features

- Real-time page view tracking
- Geographic location tracking using IP-API.com (free, no registration required)
- Device and browser detection
- Unique visitor tracking
- Efficient file-based caching system
- Comprehensive dashboard with charts and filters
- Export capabilities
- Configurable data aggregation
- Low-impact tracking system

## Requirements

- PHP 8.1 or higher
- Statamic 5.0 or higher

## Installation

1. Install the package via Composer:
```bash
composer require mohammedshuaau/enhanced-analytics
```

2. Publish the configuration file:
```bash
php artisan vendor:publish --tag=enhanced-analytics-config
```

3. Run the migrations:
```bash
php artisan migrate
```

4. Add the following to your `app/Console/Kernel.php` in the `schedule` method:
```php
$schedule->command('analytics:process')->everyFifteenMinutes();
```

## Configuration

The configuration file is located at `config/enhanced-analytics.php`. You can customize:

- Cache settings (file driver by default)
- IP Geolocation settings (caching duration and rate limits)
- Processing frequency and chunk size
- Tracking exclusions (IPs, paths, bots)
- Dashboard refresh interval

## Usage

1. Access the analytics dashboard from the Statamic control panel under Tools > Analytics.
2. View real-time statistics including:
   - Total visits and unique visitors
   - Geographic distribution of visitors
   - Device and browser usage
   - Page view trends
   - Top visited pages
3. Export data for further analysis

## How it Works

1. The middleware tracks page visits and stores them in the cache
2. A scheduled command processes the cached data every 15 minutes
3. The dashboard displays processed data with various visualizations
4. Data can be filtered by date range and exported as needed

## Performance

The addon uses an efficient caching system to minimize database load. Visit data is temporarily stored in files and processed in batches, ensuring minimal impact on your site's performance.

### Geolocation

The addon uses the free IP-API.com service for geolocation data. To ensure optimal performance and respect rate limits:
- IP geolocation data is cached for 24 hours by default
- Rate limiting is set to 45 requests per minute (free tier limit)
- Local and private IP addresses are automatically skipped

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
