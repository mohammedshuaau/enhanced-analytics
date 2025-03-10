<?php

namespace Mohammedshuaau\EnhancedAnalytics;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        Commands\ProcessAnalytics::class,
    ];

    protected $routes = [
        'cp' => __DIR__ . '/../routes/cp.php',
        'web' => __DIR__ . '/../routes/web.php',
    ];

    protected $middleware = [
        Middleware\TrackPageVisit::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            Middleware\TrackPageVisit::class,
        ],
    ];

    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        Middleware\TrackPageVisit::class,
    ];

    protected $tags = [
        Tags\ConsentBanner::class,
    ];

    protected $vite = [
        'input' => [
            'resources/js/consent-banner.js',
            'resources/js/enhanced-analytics.js',
            'resources/css/enhanced-analytics.css'
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/enhanced-analytics.php' => config_path('enhanced-analytics.php'),
        ], 'enhanced-analytics-config');

        // Merge configuration early so we can use it
        $this->mergeConfigFrom(
            __DIR__ . '/../config/enhanced-analytics.php', 'enhanced-analytics'
        );

        // Publish views/components
        $this->publishes([
            __DIR__ . '/../resources/views/components/consent-banner.antlers.html' => resource_path('views/vendor/enhanced-analytics/components/consent-banner.antlers.html'),
        ], 'enhanced-analytics-views');

        // Ensure storage directory exists with proper permissions (if using file driver)
        $this->ensureStorageDirectoryExists();

        // Register the nav item
        Nav::extend(function ($nav) {
            $nav->create('Analytics')
                ->section('Tools')
                ->route('enhanced-analytics.index')
                ->icon('charts');
        });

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register scheduled tasks
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $frequency = config('enhanced-analytics.processing.frequency', 15);

            // Generate the cron expression for custom minutes
            $cronExpression = "*/{$frequency} * * * *";

            $schedule->command('analytics:process')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/analytics-scheduler.log'))
                ->cron($cronExpression);
        });
    }

    protected function ensureStorageDirectoryExists()
    {
        try {
            // Only create directory if using file driver
            if (config('enhanced-analytics.cache.driver') === 'file') {
                $path = config('enhanced-analytics.cache.file.path', storage_path('app/enhanced-analytics'));
                $permissions = config('enhanced-analytics.cache.file.permissions.directory', 0755);

                if (!File::exists($path)) {
                    File::makeDirectory($path, $permissions, true);
                }

                // Ensure proper permissions
                if (File::exists($path)) {
                    chmod($path, $permissions);
                }
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
