<?php

namespace Mohammedshuaau\EnhancedAnalytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnalyticsDashboardController
{
    public function index()
    {
        return view('enhanced-analytics::dashboard');
    }

    public function getData(Request $request)
    {
        $range = $request->input('range', '7days');
        $startDate = $this->getStartDate($range, $request);
        $endDate = Carbon::now();

        if ($range === 'custom') {
            if ($request->input('start_date') && $request->input('end_date')) {
                $startDate = Carbon::parse($request->input('start_date'));
                $endDate = Carbon::parse($request->input('end_date'));
            }
        }

        // Get previous period for comparisons
        $periodLength = $startDate->diffInDays($endDate);
        $previousStartDate = $startDate->copy()->subDays($periodLength);
        $previousEndDate = $startDate->copy()->subDay();

        $data = [
            'overview' => array_merge(
                $this->getOverviewStats($startDate, $endDate),
                ['comparisons' => $this->getComparisons($startDate, $endDate, $previousStartDate, $previousEndDate)]
            ),
            'engagement' => $this->getEngagementMetrics($startDate, $endDate),
            'page_views' => $this->getPageViewsData($startDate, $endDate),
            'device_stats' => $this->getDeviceStats($startDate, $endDate),
            'country_stats' => $this->getCountryStats($startDate, $endDate),
            'browser_stats' => $this->getBrowserStats($startDate, $endDate),
            'top_pages' => $this->getTopPages($startDate, $endDate),
            'user_flow' => $this->getUserFlow($startDate, $endDate)
        ];

        Log::info('Analytics Data:', [
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
        $data = DB::table('enhanced_analytics_page_views')
            ->select(
                DB::raw('DATE(visited_at) as date'),
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(CASE WHEN is_new_page_visit = 1 THEN 1 END) as unique_views')
            )
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data;
    }

    protected function getTopPages($startDate, $endDate, $limit = 10)
    {
        // Get total views and unique views per page
        $pages = DB::table('enhanced_analytics_page_views as a')
            ->select(
                'page_url',
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(CASE WHEN is_new_page_visit = 1 THEN 1 END) as unique_views'),
                DB::raw('COUNT(CASE WHEN is_new_page_visit = 1 AND is_new_visitor = 1 THEN 1 END) / COUNT(CASE WHEN is_new_page_visit = 1 THEN 1 END) as bounce_rate')
            )
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->groupBy('page_url')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();

        // Calculate average time and exit rate for each page
        foreach ($pages as $page) {
            // Calculate average time on page
            $sessions = DB::table('enhanced_analytics_page_views')
                ->select('session_id', 'visited_at')
                ->where('page_url', $page->page_url)
                ->whereBetween('visited_at', [$startDate, $endDate])
                ->orderBy('session_id')
                ->orderBy('visited_at')
                ->get()
                ->groupBy('session_id');

            $totalTime = 0;
            $timeCount = 0;

            foreach ($sessions as $sessionVisits) {
                $visits = $sessionVisits->values();
                for ($i = 0; $i < count($visits) - 1; $i++) {
                    $currentVisit = Carbon::parse($visits[$i]->visited_at);
                    $nextVisit = Carbon::parse($visits[$i + 1]->visited_at);
                    $timeDiff = $nextVisit->diffInSeconds($currentVisit);
                    if ($timeDiff < 3600) { // Ignore differences more than an hour
                        $totalTime += $timeDiff;
                        $timeCount++;
                    }
                }
            }

            $page->avg_time = $timeCount > 0 ? $totalTime / $timeCount : 0;

            // Calculate exit rate
            $totalPageViews = $page->views;
            $exits = DB::table('enhanced_analytics_page_views as a')
                ->where('page_url', $page->page_url)
                ->whereBetween('visited_at', [$startDate, $endDate])
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('enhanced_analytics_page_views as b')
                        ->whereRaw('a.session_id = b.session_id')
                        ->whereRaw('a.visited_at < b.visited_at');
                })
                ->count();

            $page->exit_rate = $totalPageViews > 0 ? $exits / $totalPageViews : 0;
        }

        return $pages;
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

    protected function getComparisons($startDate, $endDate, $previousStartDate, $previousEndDate)
    {
        $current = $this->getOverviewStats($startDate, $endDate);
        $previous = $this->getOverviewStats($previousStartDate, $previousEndDate);

        return [
            'total_visits' => $this->calculatePercentageChange(
                $previous['total_visits'],
                $current['total_visits']
            ),
            'unique_visitors' => $this->calculatePercentageChange(
                $previous['unique_visitors'],
                $current['unique_visitors']
            ),
            'bounce_rate' => $this->calculatePercentageChange(
                $previous['bounce_rate'],
                $current['bounce_rate']
            )
        ];
    }

    protected function calculatePercentageChange($previous, $current)
    {
        if ($previous == 0) return 100;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function getEngagementMetrics($startDate, $endDate)
    {
        $newVisitors = DB::table('enhanced_analytics_page_views')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->where('is_new_visitor', true)
            ->count();

        $returningVisitors = DB::table('enhanced_analytics_page_views')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->where('is_new_visitor', false)
            ->count();

        $sessions = DB::table('enhanced_analytics_page_views')
            ->select('session_id')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->whereNotNull('session_id')
            ->distinct()
            ->get();

        $totalPageViews = DB::table('enhanced_analytics_page_views')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->count();

        $sessionCount = $sessions->count();
        $pagesPerSession = $sessionCount > 0 ? $totalPageViews / $sessionCount : 0;

        return [
            'new_visitors' => $newVisitors,
            'returning_visitors' => $returningVisitors,
            'pages_per_session' => $pagesPerSession,
            'avg_session_duration' => $this->calculateAverageTimeOnSite($startDate, $endDate)
        ];
    }

    protected function getUserFlow($startDate, $endDate)
    {
        // Get top entry pages
        $entryPages = DB::table('enhanced_analytics_page_views')
            ->select(
                'page_url',
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->where('is_new_page_visit', true)
            ->groupBy('page_url')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Get most engaged pages
        $engagedPages = collect();
        $pages = DB::table('enhanced_analytics_page_views')
            ->select('page_url', 'session_id', 'visited_at')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->whereNotNull('session_id')
            ->orderBy('session_id')
            ->orderBy('visited_at')
            ->get()
            ->groupBy('page_url');

        foreach ($pages as $url => $visits) {
            $totalTime = 0;
            $timeCount = 0;

            $sessionVisits = $visits->groupBy('session_id');
            foreach ($sessionVisits as $sessionVisit) {
                $orderedVisits = $sessionVisit->values();
                for ($i = 0; $i < count($orderedVisits) - 1; $i++) {
                    $currentVisit = Carbon::parse($orderedVisits[$i]->visited_at);
                    $nextVisit = Carbon::parse($orderedVisits[$i + 1]->visited_at);
                    $timeDiff = $nextVisit->diffInSeconds($currentVisit);
                    if ($timeDiff < 3600) { // Ignore differences more than an hour
                        $totalTime += $timeDiff;
                        $timeCount++;
                    }
                }
            }

            if ($timeCount > 0) {
                $engagedPages->push((object)[
                    'url' => $url,
                    'avg_time' => $totalTime / $timeCount
                ]);
            }
        }

        $engagedPages = $engagedPages->sortByDesc('avg_time')->take(5)->values();

        // Get top exit pages
        $exitPages = collect();
        $pages = DB::table('enhanced_analytics_page_views')
            ->select('page_url', 'session_id', 'visited_at')
            ->whereBetween('visited_at', [$startDate, $endDate])
            ->get()
            ->groupBy('page_url');

        foreach ($pages as $url => $visits) {
            $totalVisits = $visits->count();
            $exits = $visits->filter(function ($visit) use ($visits) {
                return !$visits->where('session_id', $visit->session_id)
                    ->where('visited_at', '>', $visit->visited_at)
                    ->count();
            })->count();

            $exitPages->push((object)[
                'url' => $url,
                'exits' => $exits,
                'exit_rate' => $totalVisits > 0 ? $exits / $totalVisits : 0
            ]);
        }

        $exitPages = $exitPages->sortByDesc('exits')->take(5)->values();

        return [
            'entry_pages' => $entryPages,
            'engaged_pages' => $engagedPages,
            'exit_pages' => $exitPages
        ];
    }
}
