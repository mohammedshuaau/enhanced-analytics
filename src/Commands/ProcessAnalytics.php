<?php

namespace Mohammedshuaau\EnhancedAnalytics\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Mohammedshuaau\EnhancedAnalytics\Cache\CacheManager;
use Illuminate\Cache\Lock;
use Illuminate\Support\Facades\Log;

class ProcessAnalytics extends Command
{
    protected $signature = 'analytics:process';
    protected $description = 'Process cached analytics data and store it in the database';

    protected $cache;

    public function __construct(CacheManager $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    public function handle()
    {
        $this->info('Processing analytics data...');

        // Acquire a lock to prevent multiple processes from running simultaneously
        $lock = Cache::lock('enhanced-analytics:processing', config('enhanced-analytics.processing.lock_timeout'));

        try {
            if (!$lock->get()) {
                $this->warn('Another process is already running. Skipping...');
                return;
            }

            $keys = $this->cache->getAllKeys();

            if (empty($keys)) {
                $this->info('No analytics data to process.');
                return;
            }

            $processedCount = 0;
            $chunkSize = config('enhanced-analytics.processing.chunk_size', 1000);
            $errors = [];

            foreach ($keys as $key) {
                try {
                    $visits = $this->cache->get($key);

                    if (empty($visits)) {
                        $this->cache->delete($key);
                        continue;
                    }

                    // Process in chunks to avoid memory issues
                    foreach (array_chunk($visits, $chunkSize) as $chunk) {
                        DB::transaction(function () use ($chunk) {
                            $this->processVisits($chunk);
                        });

                        $processedCount += count($chunk);
                    }

                    // Delete processed data
                    $this->cache->delete($key);
                } catch (\Exception $e) {
                    $errors[] = "Failed to process key {$key}: {$e->getMessage()}";
                    report($e);
                }
            }

            // Clean up any old data
            $this->cache->cleanup();

            $this->info("Processed {$processedCount} analytics records.");

            if (!empty($errors)) {
                $this->error('Some errors occurred during processing:');
                foreach ($errors as $error) {
                    $this->error("- {$error}");
                }
            }
        } catch (\Exception $e) {
            $this->error("Fatal error during processing: {$e->getMessage()}");
            report($e);
        } finally {
            // Always release the lock
            $lock->release();
        }
    }

    protected function processVisits(array $visits)
    {
        try {
            $now = Carbon::now();

            // Prepare bulk insert data
            $records = array_map(function ($visit) use ($now) {
                if (!is_array($visit)) {
                    Log::error('Enhanced Analytics: Invalid visit data', [
                        'visit' => $visit,
                        'type' => gettype($visit)
                    ]);
                    return null;
                }

                // Ensure user_id is either null or a numeric value
                $userId = isset($visit['user_id']) && is_numeric($visit['user_id']) ? $visit['user_id'] : null;

                // Format the visited_at datetime
                $visitedAt = isset($visit['visited_at']) ? Carbon::parse($visit['visited_at'])->format('Y-m-d H:i:s') : $now->format('Y-m-d H:i:s');

                return array_merge($visit, [
                    'created_at' => $now,
                    'updated_at' => $now,
                    'user_id' => $userId,
                    'visited_at' => $visitedAt
                ]);
            }, $visits);

            // Filter out any null records
            $records = array_filter($records);

            if (empty($records)) {
                Log::warning('Enhanced Analytics: No valid records to process');
                return;
            }

            // Bulk insert
            DB::table('enhanced_analytics_page_views')->insert($records);

            // Update aggregates
            $this->updateAggregates($visits);
        } catch (\Exception $e) {
            Log::error('Enhanced Analytics: Error processing visits', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function updateAggregates(array $visits)
    {
        $dimensions = [
            'country_code',
            'device_type',
            'browser',
            'platform'
        ];

        foreach ($visits as $visit) {
            if (!is_array($visit)) {
                Log::warning('Enhanced Analytics: Invalid visit data in aggregates', [
                    'visit' => $visit,
                    'type' => gettype($visit)
                ]);
                continue;
            }

            $date = Carbon::parse($visit['visited_at'])->toDateString();

            foreach ($dimensions as $dimension) {
                if (!isset($visit[$dimension]) || empty($visit[$dimension])) {
                    continue;
                }

                $value = $visit[$dimension];

                $this->updateAggregate('daily', $date, $dimension, $value, $visit);
            }
        }
    }

    protected function updateAggregate($type, $date, $dimension, $value, array $visit)
    {
        try {
            DB::table('enhanced_analytics_aggregates')
                ->updateOrInsert(
                    [
                        'type' => $type,
                        'date' => $date,
                        'dimension' => $dimension,
                        'dimension_value' => $value,
                    ],
                    [
                        'total_visits' => DB::raw('COALESCE(total_visits, 0) + 1'),
                        'unique_visitors' => DB::raw('COALESCE(unique_visitors, 0) + ' . ($visit['is_new_visitor'] ? '1' : '0')),
                        'unique_page_views' => DB::raw('COALESCE(unique_page_views, 0) + ' . ($visit['is_new_page_visit'] ? '1' : '0')),
                        'returning_visitors' => DB::raw('COALESCE(returning_visitors, 0) + ' . (!$visit['is_new_visitor'] ? '1' : '0')),
                        'updated_at' => Carbon::now(),
                    ]
                );

        } catch (\Exception $e) {
            Log::error('Enhanced Analytics: Error updating aggregate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
