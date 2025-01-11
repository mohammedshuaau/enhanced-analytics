<?php

namespace Mohammedshuaau\EnhancedAnalytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mohammedshuaau\EnhancedAnalytics\Middleware\TrackPageVisit;

class AnalyticsSettingsController
{
    public function index()
    {
        return view('enhanced-analytics::settings', [
            'stats' => TrackPageVisit::getGeolocationStats(),
            'config' => [
                'cache_driver' => config('enhanced-analytics.cache.driver'),
                'geolocation_cache_duration' => config('enhanced-analytics.geolocation.cache_duration'),
                'geolocation_rate_limit' => config('enhanced-analytics.geolocation.rate_limit'),
                'processing_frequency' => config('enhanced-analytics.processing.frequency'),
                'processing_chunk_size' => config('enhanced-analytics.processing.chunk_size'),
                'excluded_ips' => config('enhanced-analytics.tracking.exclude_ips'),
                'excluded_paths' => config('enhanced-analytics.tracking.exclude_paths'),
                'exclude_bots' => config('enhanced-analytics.tracking.exclude_bots'),
                'track_authenticated_users' => config('enhanced-analytics.tracking.track_authenticated_users'),
            ]
        ]);
    }

    public function clearCache()
    {
        try {
            TrackPageVisit::clearGeolocationCache();
            return response()->json([
                'success' => true,
                'message' => 'Geolocation cache cleared successfully.',
                'stats' => TrackPageVisit::getGeolocationStats()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStats()
    {
        return response()->json([
            'success' => true,
            'stats' => TrackPageVisit::getGeolocationStats()
        ]);
    }
} 