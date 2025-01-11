<?php

namespace Mohammedshuaau\EnhancedAnalytics\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;
use Carbon\Carbon;
use Mohammedshuaau\EnhancedAnalytics\Cache\CacheManager;

class TrackPageVisit
{
    protected $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            if ($this->shouldTrack($request)) {
                $this->trackVisit($request);
            }
        } catch (\Exception $e) {
            report($e);
        }

        return $response;
    }

    protected function shouldTrack(Request $request)
    {
        // Don't track excluded paths
        $excludePaths = config('enhanced-analytics.tracking.exclude_paths', []);
        foreach ($excludePaths as $path) {
            if ($request->is($path)) {
                return false;
            }
        }

        // Don't track excluded IPs
        $excludeIps = config('enhanced-analytics.tracking.exclude_ips', []);
        if (in_array($request->ip(), $excludeIps)) {
            return false;
        }

        // Don't track bots if configured
        if (config('enhanced-analytics.tracking.exclude_bots', true)) {
            $agent = new Agent();
            if ($agent->isRobot()) {
                return false;
            }
        }

        // Check if we should track authenticated users
        if (!config('enhanced-analytics.tracking.track_authenticated_users', true) && Auth::check()) {
            return false;
        }

        return true;
    }

    protected function trackVisit(Request $request)
    {
        $agent = new Agent();
        $now = Carbon::now();
        $sessionId = Session::getId();
        $pageUrl = $request->fullUrl();
        
        // Generate unique keys for different time periods
        $dayKey = $now->format('Y-m-d');
        $hourKey = $now->format('Y-m-d-H');
        $pageKey = md5($pageUrl);

        // Check if this is a unique visit for this session
        $isNewVisitor = !Session::has('analytics_first_visit');
        $isNewDayVisit = !Session::has("analytics_day_{$dayKey}");
        $isNewHourVisit = !Session::has("analytics_hour_{$hourKey}");
        $isNewPageVisit = !Session::has("analytics_page_{$pageKey}");

        // Mark the visit in session
        if ($isNewVisitor) {
            Session::put('analytics_first_visit', true);
            Session::put('analytics_visitor_id', uniqid('', true));
        }
        
        Session::put("analytics_day_{$dayKey}", true);
        Session::put("analytics_hour_{$hourKey}", true);
        Session::put("analytics_page_{$pageKey}", true);

        $visitData = [
            'page_url' => $pageUrl,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->getDeviceType($agent),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'referrer_url' => $request->header('referer'),
            'user_id' => Auth::id(),
            'session_id' => $sessionId,
            'visitor_id' => Session::get('analytics_visitor_id'),
            'is_new_visitor' => $isNewVisitor,
            'is_new_day_visit' => $isNewDayVisit,
            'is_new_hour_visit' => $isNewHourVisit,
            'is_new_page_visit' => $isNewPageVisit,
            'visited_at' => $now->toDateTimeString(),
        ];

        $cacheKey = 'visits_' . $now->format('Y_m_d_H_i');
        $this->cache->append($cacheKey, $visitData);
    }

    protected function getDeviceType(Agent $agent)
    {
        if ($agent->isTablet()) {
            return 'tablet';
        }
        if ($agent->isMobile()) {
            return 'mobile';
        }
        return 'desktop';
    }
} 