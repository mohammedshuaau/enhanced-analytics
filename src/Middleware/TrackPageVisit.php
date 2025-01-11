<?php

namespace Mohammedshuaau\EnhancedAnalytics\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class TrackPageVisit
{
    protected $agent;

    public function __construct()
    {
        $this->agent = new Agent();
    }

    protected function getGeolocationData($ipAddress)
    {
        try {
            Log::debug('Enhanced Analytics: Getting geolocation data', [
                'ip_address' => $ipAddress
            ]);

            // Skip for localhost/private IPs
            if (in_array($ipAddress, ['127.0.0.1', '::1']) || filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                Log::debug('Enhanced Analytics: Skipping geolocation for local/private IP');
                return [
                    'country_code' => null,
                    'country_name' => null,
                    'city' => null
                ];
            }

            // Try to get from cache first
            $cacheKey = 'enhanced_analytics_geo_' . $ipAddress;
            $cacheDuration = config('enhanced-analytics.geolocation.cache_duration', 60 * 24);
            $rateLimitKey = 'enhanced_analytics_geo_ratelimit';
            $rateLimit = config('enhanced-analytics.geolocation.rate_limit', 45);

            // Check rate limit
            $currentMinute = now()->format('Y-m-d H:i');
            $requestCount = Cache::get($rateLimitKey . '_' . $currentMinute, 0);

            if ($requestCount >= $rateLimit) {
                Log::warning('Enhanced Analytics: IP Geolocation rate limit reached. Using fallback data.');
                return $this->getFallbackGeolocationData($ipAddress);
            }

            return Cache::remember($cacheKey, $cacheDuration * 60, function () use ($ipAddress, $rateLimitKey, $currentMinute, $requestCount) {
                try {
                    // Increment rate limit counter
                    Cache::put($rateLimitKey . '_' . $currentMinute, $requestCount + 1, 60);

                    Log::debug('Enhanced Analytics: Calling IP-API', [
                        'ip_address' => $ipAddress,
                        'url' => "http://ip-api.com/json/{$ipAddress}?fields=status,message,countryCode,country,city"
                    ]);

                    $response = file_get_contents("http://ip-api.com/json/{$ipAddress}?fields=status,message,countryCode,country,city");
                    $data = json_decode($response, true);

                    Log::debug('Enhanced Analytics: IP-API response', [
                        'response' => $response,
                        'decoded_data' => $data
                    ]);

                    if ($data && isset($data['status']) && $data['status'] === 'success') {
                        // Store successful lookup in analytics
                        $this->trackGeolocationLookup($ipAddress, true);
                        $geoData = [
                            'country_code' => $data['countryCode'] ?? null,
                            'country_name' => $data['country'] ?? null,
                            'city' => $data['city'] ?? null
                        ];
                        Log::debug('Enhanced Analytics: Geolocation data retrieved', [
                            'geo_data' => $geoData
                        ]);
                        return $geoData;
                    }

                    // Store failed lookup in analytics
                    Log::warning('Enhanced Analytics: IP-API lookup failed', [
                        'status' => $data['status'] ?? 'unknown',
                        'message' => $data['message'] ?? 'No message'
                    ]);
                    $this->trackGeolocationLookup($ipAddress, false);
                    return $this->getFallbackGeolocationData($ipAddress);
                } catch (\Exception $e) {
                    Log::error('Enhanced Analytics: Geolocation API error', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $this->trackGeolocationLookup($ipAddress, false);
                    return $this->getFallbackGeolocationData($ipAddress);
                }
            });
        } catch (\Exception $e) {
            Log::error('Enhanced Analytics: Geolocation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getFallbackGeolocationData($ipAddress);
        }
    }

    protected function getFallbackGeolocationData($ipAddress)
    {
        // Try to get from historical data
        $historicalKey = 'enhanced_analytics_historical_geo';
        $historicalData = Cache::get($historicalKey, []);
        
        return $historicalData[$ipAddress] ?? [
            'country_code' => null,
            'country_name' => null,
            'city' => null
        ];
    }

    protected function trackGeolocationLookup($ipAddress, $success)
    {
        $statsKey = 'enhanced_analytics_geolocation_stats';
        $stats = Cache::get($statsKey, [
            'total_lookups' => 0,
            'successful_lookups' => 0,
            'failed_lookups' => 0,
            'unique_ips' => [],
            'last_lookup' => null,
        ]);

        $stats['total_lookups']++;
        if ($success) {
            $stats['successful_lookups']++;
        } else {
            $stats['failed_lookups']++;
        }

        if (!in_array($ipAddress, $stats['unique_ips'])) {
            $stats['unique_ips'][] = $ipAddress;
        }

        $stats['last_lookup'] = now()->toDateTimeString();

        Cache::put($statsKey, $stats, now()->addDays(30));
    }

    public static function getGeolocationStats()
    {
        $statsKey = 'enhanced_analytics_geolocation_stats';
        return Cache::get($statsKey, [
            'total_lookups' => 0,
            'successful_lookups' => 0,
            'failed_lookups' => 0,
            'unique_ips' => [],
            'last_lookup' => null,
        ]);
    }

    public static function clearGeolocationCache()
    {
        $pattern = 'enhanced_analytics_geo_*';
        $keys = Cache::get('enhanced_analytics_cache_keys', []);
        
        foreach ($keys as $key) {
            if (Str::is($pattern, $key)) {
                Cache::forget($key);
            }
        }

        Cache::forget('enhanced_analytics_geolocation_stats');
        Cache::forget('enhanced_analytics_cache_keys');
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            Log::debug('Enhanced Analytics: Middleware called', [
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if ($this->shouldTrack($request)) {
                Log::debug('Enhanced Analytics: Request should be tracked');
                
                $visitorId = $request->session()->get('visitor_id', (string) Str::uuid());
                $request->session()->put('visitor_id', $visitorId);

                $pageUrl = $request->path();
                $ipAddress = $request->ip();

                // Get geographic data
                $geoData = $this->getGeolocationData($ipAddress);

                $visitData = [
                    'page_url' => $pageUrl,
                    'ip_address' => $ipAddress,
                    'user_agent' => $request->userAgent(),
                    'country_code' => $geoData['country_code'],
                    'country_name' => $geoData['country_name'],
                    'city' => $geoData['city'],
                    'device_type' => $this->getDeviceType(),
                    'browser' => $this->agent->browser(),
                    'platform' => $this->agent->platform(),
                    'referrer_url' => $request->header('referer'),
                    'user_id' => auth()->id(),
                    'session_id' => $request->session()->getId(),
                    'visitor_id' => $visitorId,
                    'is_new_visitor' => !$request->session()->has('visitor_id'),
                    'is_new_day_visit' => !$request->session()->has('last_visit_date') || 
                        $request->session()->get('last_visit_date') < now()->startOfDay(),
                    'is_new_hour_visit' => !$request->session()->has('last_visit_hour') || 
                        $request->session()->get('last_visit_hour') < now()->startOfHour(),
                    'is_new_page_visit' => !$request->session()->has('visited_pages') || 
                        !in_array($pageUrl, $request->session()->get('visited_pages', [])),
                    'visited_at' => now()->format('Y-m-d H:i:s'),
                ];

                Log::debug('Enhanced Analytics: Visit data prepared', [
                    'geo_data' => $geoData,
                    'visit_data' => $visitData
                ]);

                // Update session data
                $request->session()->put('last_visit_date', now());
                $request->session()->put('last_visit_hour', now());
                $visitedPages = $request->session()->get('visited_pages', []);
                $visitedPages[] = $pageUrl;
                $request->session()->put('visited_pages', array_unique($visitedPages));

                $this->storeVisit($visitData);
            } else {
                Log::debug('Enhanced Analytics: Request should not be tracked', [
                    'path' => $request->path(),
                    'excluded_paths' => config('enhanced-analytics.tracking.exclude_paths', []),
                    'excluded_ips' => config('enhanced-analytics.tracking.exclude_ips', []),
                    'is_bot' => $this->agent->isRobot(),
                    'exclude_bots' => config('enhanced-analytics.tracking.exclude_bots', true),
                    'is_authenticated' => auth()->check(),
                    'track_authenticated' => config('enhanced-analytics.tracking.track_authenticated_users', true)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Enhanced Analytics: Error in middleware', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $next($request);
    }

    protected function shouldTrack(Request $request): bool
    {
        // Get excluded paths and IPs from config
        $excludedPaths = config('enhanced-analytics.tracking.exclude_paths', []);
        $excludedIps = config('enhanced-analytics.tracking.exclude_ips', []);
        $excludeBots = config('enhanced-analytics.tracking.exclude_bots', true);
        $trackAuthenticated = config('enhanced-analytics.tracking.track_authenticated_users', true);

        // Check if the path should be excluded
        foreach ($excludedPaths as $path) {
            if (Str::is($path, $request->path())) {
                return false;
            }
        }

        // Check if the IP should be excluded
        if (in_array($request->ip(), $excludedIps)) {
            return false;
        }

        // Check if it's a bot and should be excluded
        if ($excludeBots && $this->agent->isRobot()) {
            return false;
        }

        // Check if authenticated users should be tracked
        if (!$trackAuthenticated && auth()->check()) {
            return false;
        }

        return true;
    }

    protected function getDeviceType(): string
    {
        if ($this->agent->isTablet()) {
            return 'tablet';
        }

        if ($this->agent->isMobile()) {
            return 'mobile';
        }

        return 'desktop';
    }

    protected function storeVisit(array $visitData)
    {
        try {
            $key = 'analytics_' . now()->format('Y_m_d_H_i');
            $path = $this->getCachePath($key);

            Log::debug('Enhanced Analytics: Storing visit data', [
                'key' => $key,
                'path' => $path,
                'config_path' => config('enhanced-analytics.cache.file.path'),
                'visit_data' => $visitData
            ]);

            // Ensure directory exists
            $directory = dirname($path);
            if (!File::exists($directory)) {
                Log::debug('Enhanced Analytics: Creating directory', ['directory' => $directory]);
                File::makeDirectory($directory, 0755, true);
            }

            // Get existing data or create new array
            $data = [];
            if (File::exists($path)) {
                $content = File::get($path);
                $data = json_decode($content, true);
                if (!is_array($data)) {
                    Log::warning('Enhanced Analytics: Invalid JSON data in file', [
                        'path' => $path,
                        'content' => $content
                    ]);
                    $data = [];
                }
                Log::debug('Enhanced Analytics: Found existing data', ['count' => count($data)]);
            }

            // Append new visit
            $data[] = $visitData;

            // Store updated data
            $jsonData = json_encode($data);
            if ($jsonData === false) {
                Log::error('Enhanced Analytics: JSON encode error', [
                    'error' => json_last_error_msg(),
                    'data' => $data
                ]);
                return;
            }

            File::put($path, $jsonData);
            Log::debug('Enhanced Analytics: Stored visit data', [
                'total_visits' => count($data),
                'json_size' => strlen($jsonData)
            ]);

            // Update cache index
            $this->updateCacheIndex($key);
        } catch (\Exception $e) {
            Log::error('Enhanced Analytics: Error storing visit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function getCachePath($key): string
    {
        $basePath = config('enhanced-analytics.cache.file.path');
        return $basePath . '/' . $key . '.json';
    }

    protected function updateCacheIndex($newKey)
    {
        $indexPath = $this->getCachePath('index');
        $keys = [];

        if (File::exists($indexPath)) {
            $keys = json_decode(File::get($indexPath), true) ?? [];
        }

        $keys[] = $newKey;
        File::put($indexPath, json_encode(array_unique($keys)));
    }
} 