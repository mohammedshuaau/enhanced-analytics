<?php

namespace Mohammedshuaau\EnhancedAnalytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AnalyticsDashboardController
{
    public function index()
    {
        return view('enhanced-analytics::dashboard');
    }

    public function getData(Request $request)
    {
        $range = $request->input('range', config('enhanced-analytics.dashboard.default_range'));
        $startDate = $this->getStartDate($range, $request);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

        $data = [
            'overview' => $this->getOverviewStats($startDate, $endDate),
            'pageViews' => $this->getPageViewsData($startDate, $endDate),
            'topPages' => $this->getTopPages($startDate, $endDate),
            'deviceStats' => $this->getDeviceStats($startDate, $endDate),
            'countryStats' => $this->getCountryStats($startDate, $endDate),
            'browserStats' => $this->getBrowserStats($startDate, $endDate),
        ];

        // Debug information
        \Log::info('Analytics Data:', [
            'range' => $range,
            'startDate' => $startDate->toDateTimeString(),
            'endDate' => $endDate->toDateTimeString(),
            'data' => $data
        ]);

        return response()->json($data);
    }

    protected function getStartDate($range, Request $request)
    {
        if ($request->input('start_date')) {
            return Carbon::parse($request->input('start_date'));
        }

        return match($range) {
            '24hours' => Carbon::now()->subDay(),
            '7days' => Carbon::now()->subDays(7),
            '30days' => Carbon::now()->subDays(30),
            default => Carbon::now()->subDays(7),
        };
    }

    protected function getOverviewStats($startDate, $endDate)
    {
        $totalVisits = DB::table('enhanced_analytics_page_views')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->count();

        $uniqueVisitors = DB::table('enhanced_analytics_page_views')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->where('is_new_visitor', true)
            ->count();

        $bounceRate = DB::table('enhanced_analytics_page_views')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->where('is_new_page_visit', true)
            ->count() / ($totalVisits ?: 1);

        return [
            'total_visits' => $totalVisits,
            'unique_visitors' => $uniqueVisitors,
            'avg_time_on_site' => $this->calculateAverageTimeOnSite($startDate, $endDate),
            'bounce_rate' => $bounceRate,
        ];
    }

    protected function getPageViewsData($startDate, $endDate)
    {
        $views = DB::table('enhanced_analytics_page_views')
            ->select(
                DB::raw('DATE(visited_at) as date'),
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(CASE WHEN is_new_page_visit = 1 THEN 1 END) as unique_views')
            )
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $views->map(function($item) {
            return [
                'date' => $item->date,
                'views' => $item->views,
                'unique_views' => $item->unique_views,
            ];
        });
    }

    protected function getTopPages($startDate, $endDate, $limit = 10)
    {
        return DB::table('enhanced_analytics_page_views')
            ->select(
                'page_url',
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(CASE WHEN is_new_page_visit = 1 THEN 1 END) as unique_views'),
                DB::raw('AVG(CASE WHEN session_id IS NOT NULL THEN 1 ELSE 0 END) as bounce_rate')
            )
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->groupBy('page_url')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(function($page) {
                return [
                    'page_url' => $page->page_url,
                    'views' => $page->views,
                    'unique_views' => $page->unique_views,
                    'bounce_rate' => $page->bounce_rate,
                    'avg_time' => 0, // We'll implement this later with session tracking
                ];
            });
    }

    protected function getDeviceStats($startDate, $endDate)
    {
        return DB::table('enhanced_analytics_aggregates')
            ->where('dimension', 'device_type')
            ->where('type', 'daily')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('dimension_value', DB::raw('SUM(total_visits) as total'))
            ->groupBy('dimension_value')
            ->get();
    }

    protected function getCountryStats($startDate, $endDate)
    {
        return DB::table('enhanced_analytics_aggregates')
            ->where('dimension', 'country_code')
            ->where('type', 'daily')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('dimension_value', DB::raw('SUM(total_visits) as total'))
            ->groupBy('dimension_value')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    protected function getBrowserStats($startDate, $endDate)
    {
        return DB::table('enhanced_analytics_aggregates')
            ->where('dimension', 'browser')
            ->where('type', 'daily')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('dimension_value', DB::raw('SUM(total_visits) as total'))
            ->groupBy('dimension_value')
            ->orderByDesc('total')
            ->get();
    }

    protected function calculateAverageTimeOnSite($startDate, $endDate)
    {
        $sessions = DB::table('enhanced_analytics_page_views')
            ->select('session_id', 'visited_at')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->whereNotNull('session_id')
            ->orderBy('visited_at')
            ->get()
            ->groupBy('session_id');

        $totalTime = 0;
        $sessionCount = 0;

        foreach ($sessions as $sessionVisits) {
            if ($sessionVisits->count() > 1) {
                $firstVisit = Carbon::parse($sessionVisits->first()->visited_at);
                $lastVisit = Carbon::parse($sessionVisits->last()->visited_at);
                $totalTime += $lastVisit->diffInSeconds($firstVisit);
                $sessionCount++;
            }
        }

        return $sessionCount > 0 ? round($totalTime / $sessionCount) : 0;
    }

    public function export(Request $request)
    {
        $startDate = $this->getStartDate($request->input('range'), $request);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

        $data = DB::table('enhanced_analytics_page_views')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->get();

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            
            // Headers
            fputcsv($output, [
                'Page URL',
                'IP Address',
                'Country',
                'City',
                'Device Type',
                'Browser',
                'Platform',
                'Visited At'
            ]);

            // Data
            foreach ($data as $row) {
                fputcsv($output, [
                    $row->page_url,
                    $row->ip_address,
                    $row->country_name,
                    $row->city,
                    $row->device_type,
                    $row->browser,
                    $row->platform,
                    $row->visited_at
                ]);
            }

            fclose($output);
        }, 'analytics-export-' . Carbon::now()->format('Y-m-d') . '.csv');
    }

    public function getGeolocationStats()
    {
        $stats = \Mohammedshuaau\EnhancedAnalytics\Middleware\TrackPageVisit::getGeolocationStats();
        return response()->json($stats);
    }

    public function clearGeolocationCache()
    {
        \Mohammedshuaau\EnhancedAnalytics\Middleware\TrackPageVisit::clearGeolocationCache();
        return response()->json(['message' => 'Cache cleared successfully']);
    }
} 