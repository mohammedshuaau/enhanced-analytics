# Enhanced Analytics for Statamic

A powerful, efficient analytics solution for Statamic with real-time tracking, caching, and comprehensive dashboard visualization.

## Features

- Real-time page view tracking
- Efficient caching system using Redis
- Comprehensive dashboard with charts and filters
- Device and browser detection
- Geographic location tracking
- Export capabilities
- Configurable data aggregation
- Low-impact tracking system

## Requirements

- PHP 8.1 or higher
- Statamic 5.0 or higher
- Redis (recommended for caching)
- GeoIP2 database (for geographic data)

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

4. Add the scheduled command to your `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('analytics:process')
            ->everyFifteenMinutes();
}
```

## Configuration

The configuration file is located at `config/enhanced-analytics.php`. Here you can customize:

- Cache settings (driver, TTL)
- Processing frequency
- Tracking exclusions (IPs, paths, bots)
- Dashboard refresh interval

## Usage

### Accessing the Dashboard

The analytics dashboard is available in your Statamic control panel under the "Analytics" section.

### Dashboard Features

- Overview statistics (total visits, unique visitors, average time on site)
- Page views over time chart
- Device distribution
- Geographic distribution
- Browser usage statistics
- Top pages
- Custom date range filtering
- Data export capabilities

### Tracking Exclusions

You can exclude specific IPs or paths from being tracked by adding them to the configuration:

```php
'tracking' => [
    'exclude_ips' => [
        '127.0.0.1',
    ],
    'exclude_paths' => [
        'cp/*',
        '_debugbar/*',
    ],
    'exclude_bots' => true,
],
```

### Data Processing

The addon uses a caching system to minimize database load:

1. Page visits are initially stored in cache
2. Every 15 minutes, the cached data is processed and stored in the database
3. The dashboard reads from the processed database data

### Exporting Data

You can export analytics data as CSV from the dashboard. The export includes:

- Page URL
- IP Address
- Country
- City
- Device Type
- Browser
- Platform
- Visit Timestamp

## Performance Considerations

The addon is designed to have minimal impact on your site's performance:

- Page views are cached before being written to the database
- Database operations are batched and run on a schedule
- Aggregated data is pre-calculated for faster dashboard loading
- Dashboard auto-refreshes can be configured or disabled

## Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This addon is licensed under the MIT License. See the [LICENSE](LICENSE.md) file for details.
