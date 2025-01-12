<?php

namespace Mohammedshuaau\EnhancedAnalytics\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use RuntimeException;
use Illuminate\Support\Facades\Log;

class CacheManager
{
    protected $driver;
    protected $config;
    protected $defaultConfig = [
        'driver' => 'file',
        'file' => [
            'path' => null, // Will be set in constructor
            'permissions' => [
                'file' => 0644,
                'directory' => 0755
            ]
        ],
        'redis' => [
            'connection' => 'default',
            'prefix' => 'enhanced_analytics_'
        ],
        'ttl' => 86400 // 24 hours
    ];

    public function __construct()
    {
        // Set default storage path
        $this->defaultConfig['file']['path'] = storage_path('app/enhanced-analytics');

        // Merge default config with user config
        $this->config = array_merge(
            $this->defaultConfig,
            config('enhanced-analytics.cache') ?? []
        );

        // Ensure nested config arrays exist
        $this->config['file'] = array_merge(
            $this->defaultConfig['file'],
            $this->config['file'] ?? []
        );
        $this->config['redis'] = array_merge(
            $this->defaultConfig['redis'],
            $this->config['redis'] ?? []
        );

        $this->driver = $this->config['driver'];

        if ($this->driver === 'file') {
            $this->ensureStorageDirectory();
        }
    }

    public function store(string $key, array $data): void
    {
        if ($this->driver === 'redis') {
            $this->storeRedis($key, $data);
        } else {
            $this->storeFile($key, $data);
        }
    }

    public function get(string $key): array
    {
        if ($this->driver === 'redis') {
            return $this->getRedis($key);
        }
        return $this->getFile($key);
    }

    public function append(string $key, array $data): void
    {
        if ($this->driver === 'redis') {
            $this->appendRedis($key, $data);
        } else {
            $this->appendFile($key, $data);
        }
    }

    public function delete(string $key): void
    {
        if ($this->driver === 'redis') {
            $this->deleteRedis($key);
        } else {
            $this->deleteFile($key);
        }
    }

    public function getAllKeys(): array
    {
        if ($this->driver === 'redis') {
            return $this->getRedisKeys();
        }
        return $this->getFileKeys();
    }

    protected function storeRedis(string $key, array $data): void
    {
        try {
            $prefix = $this->config['redis']['prefix'];
            $connection = $this->config['redis']['connection'];

            Cache::store('redis')
                ->connection($connection)
                ->put($prefix . $key, $data, $this->config['ttl']);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to store data in Redis: {$e->getMessage()}");
        }
    }

    protected function getRedis(string $key): array
    {
        try {
            $prefix = $this->config['redis']['prefix'];
            $connection = $this->config['redis']['connection'];

            return Cache::store('redis')
                ->connection($connection)
                ->get($prefix . $key, []);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to retrieve data from Redis: {$e->getMessage()}");
        }
    }

    protected function appendRedis(string $key, array $data): void
    {
        try {
            $prefix = $this->config['redis']['prefix'];
            $connection = $this->config['redis']['connection'];
            $existingData = $this->getRedis($key);
            $existingData[] = $data;

            $this->storeRedis($key, $existingData);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to append data to Redis: {$e->getMessage()}");
        }
    }

    protected function deleteRedis(string $key): void
    {
        try {
            $prefix = $this->config['redis']['prefix'];
            $connection = $this->config['redis']['connection'];

            Cache::store('redis')
                ->connection($connection)
                ->forget($prefix . $key);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to delete data from Redis: {$e->getMessage()}");
        }
    }

    protected function getRedisKeys(): array
    {
        try {
            $prefix = $this->config['redis']['prefix'];
            $connection = $this->config['redis']['connection'];

            $pattern = $prefix . '*';
            return Redis::connection($connection)->keys($pattern);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to get keys from Redis: {$e->getMessage()}");
        }
    }

    protected function storeFile(string $key, array $data): void
    {
        try {
            $path = $this->getFilePath($key);
            File::put($path, json_encode($data), true);
            chmod($path, $this->config['file']['permissions']['file']);
        } catch (\Exception $e) {
            Log::error('Enhanced Analytics: Failed to store data in file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new RuntimeException("Failed to store data in file: {$e->getMessage()}");
        }
    }

    protected function getFile(string $key): array
    {
        try {
            $path = $this->getFilePath($key);
            if (!File::exists($path)) {
                return [];
            }
            $content = File::get($path);
            $data = json_decode($content, true) ?: [];
            return $data;
        } catch (\Exception $e) {
            Log::error('Enhanced Analytics: Failed to retrieve data from file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new RuntimeException("Failed to retrieve data from file: {$e->getMessage()}");
        }
    }

    protected function appendFile(string $key, array $data): void
    {
        try {
            $existingData = $this->getFile($key);
            $existingData[] = $data;
            $this->storeFile($key, $existingData);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to append data to file: {$e->getMessage()}");
        }
    }

    protected function deleteFile(string $key): void
    {
        try {
            $path = $this->getFilePath($key);
            if (File::exists($path)) {
                File::delete($path);
            }
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to delete file: {$e->getMessage()}");
        }
    }

    protected function getFileKeys(): array
    {
        try {
            $path = $this->config['file']['path'];
            if (!File::exists($path)) {
                return [];
            }

            $files = File::files($path);
            $keys = [];

            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    // Get the filename without extension
                    $keys[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                }
            }

            return $keys;
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to get file keys: {$e->getMessage()}");
        }
    }

    protected function getFilePath(string $key): string
    {
        return $this->config['file']['path'] . '/' . $key . '.json';
    }

    protected function ensureStorageDirectory(): void
    {
        try {
            $path = $this->config['file']['path'];

            if (!File::exists($path)) {
                File::makeDirectory($path, $this->config['file']['permissions']['directory'], true);
            }

            // Verify directory is writable
            if (!is_writable($path)) {
                throw new RuntimeException("Storage directory is not writable: {$path}");
            }
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to create/verify storage directory: {$e->getMessage()}");
        }
    }

    public function cleanup(): void
    {
        if ($this->driver === 'file') {
            $this->cleanupFiles();
        } else {
            $this->cleanupRedis();
        }
    }

    protected function cleanupFiles(): void
    {
        try {
            $path = $this->config['file']['path'];
            $ttl = $this->config['ttl'];
            $now = Carbon::now();

            if (!File::exists($path)) {
                return;
            }

            foreach (File::files($path) as $file) {
                $lastModified = Carbon::createFromTimestamp($file->getMTime());
                if ($now->diffInSeconds($lastModified) > $ttl) {
                    File::delete($file->getPathname());
                }
            }
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to cleanup files: {$e->getMessage()}");
        }
    }

    protected function cleanupRedis(): void
    {
        // Redis handles TTL automatically, no need for manual cleanup
    }
}
