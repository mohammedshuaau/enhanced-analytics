@extends('statamic::layout')

@section('title', 'Enhanced Analytics')

@push('styles')
    @vite('resources/css/enhanced-analytics.css', 'vendor/enhanced-analytics/build')
@endpush

@section('content')
    {{-- Initialize configuration in the head --}}
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            window.EnhancedAnalytics = {
                config: {
                    refreshInterval: {{ config('enhanced-analytics.dashboard.refresh_interval', 300) }},
                    routes: {
                        data: '{!! cp_route('enhanced-analytics.data') !!}',
                        export: '{!! cp_route('enhanced-analytics.export') !!}',
                        clearCache: '{!! cp_route('enhanced-analytics.clear-cache') !!}',
                        geoStats: '{!! cp_route('enhanced-analytics.geo-stats') !!}'
                    }
                }
            };
        </script>
    @endpush

    <div class="ea-container">
        {{-- Header with Controls --}}
        <div class="ea-header">
            <div class="ea-controls">
                <select id="dateRange" class="ea-select">
                    <option value="24hours">Last 24 Hours</option>
                    <option value="7days" selected>Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="custom">Custom Range</option>
                </select>
                <div id="customDateRange" class="hidden ea-controls">
                    <input type="date" id="startDate" class="ea-input">
                    <span class="ea-text-lg">to</span>
                    <input type="date" id="endDate" class="ea-input">
                </div>
            </div>
            <div class="ea-controls">
                <button id="refreshData" class="ea-btn ea-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Data
                </button>
                <button id="exportData" class="ea-btn ea-btn-success">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export Data
                </button>
                <button id="toggleSettings" class="ea-btn">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>Settings</span>
                </button>
            </div>
        </div>

        {{-- Settings Panel --}}
        <div id="settingsPanel" class="ea-card hidden">
            <h3 class="ea-text-xl ea-mb-4">Analytics Settings</h3>
            <div class="ea-grid ea-grid-cols-2 ea-gap-8">
                <div class="ea-space-y-4">
                    <h4 class="ea-text-lg ea-font-semibold">Geolocation Stats</h4>
                    <div class="ea-space-y-2">
                        <div class="ea-flex ea-justify-between">
                            <span class="ea-text-secondary">Total Lookups:</span>
                            <span id="totalLookups" class="ea-font-medium">0</span>
                        </div>
                        <div class="ea-flex ea-justify-between">
                            <span class="ea-text-secondary">Success Rate:</span>
                            <span id="successRate" class="ea-font-medium">0%</span>
                        </div>
                        <div class="ea-flex ea-justify-between">
                            <span class="ea-text-secondary">Unique IPs:</span>
                            <span id="uniqueIps" class="ea-font-medium">0</span>
                        </div>
                        <div class="ea-flex ea-justify-between">
                            <span class="ea-text-secondary">Last Lookup:</span>
                            <span id="lastLookup" class="ea-font-medium">Never</span>
                        </div>
                    </div>
                    <button id="clearCache" class="ea-btn ea-btn-primary ea-mt-4">Clear Geo Cache</button>
                </div>
                <div class="ea-space-y-4">
                    <h4 class="ea-text-lg ea-font-semibold">Current Configuration</h4>
                    <div class="ea-space-y-2">
                        <div class="ea-flex ea-justify-between">
                            <span class="ea-text-secondary">Cache Duration:</span>
                            <span class="ea-font-medium">{{ config('enhanced-analytics.geolocation.cache_duration', 1440) }} minutes</span>
                        </div>
                        <div class="ea-flex ea-justify-between">
                            <span class="ea-text-secondary">Rate Limit:</span>
                            <span class="ea-font-medium">{{ config('enhanced-analytics.geolocation.rate_limit', 45) }} requests/minute</span>
                        </div>
                        <div class="ea-flex ea-justify-between">
                            <span class="ea-text-secondary">Processing:</span>
                            <span class="ea-font-medium">Every {{ config('enhanced-analytics.processing.frequency', 15) }} minutes</span>
                        </div>
                        <div class="ea-flex ea-justify-between">
                            <span class="ea-text-secondary">Dashboard Refresh:</span>
                            <span class="ea-font-medium">{{ config('enhanced-analytics.dashboard.refresh_interval', 300) }} seconds</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Stats Overview --}}
        <div class="ea-grid ea-grid-cols-4">
            <div class="ea-card">
                <h3 class="ea-font-semibold">Total Visits</h3>
                <p id="totalVisits" class="ea-text-lg">0</p>
                <p id="totalVisitsChange" class="ea-text-secondary">vs previous period</p>
            </div>
            <div class="ea-card">
                <h3 class="ea-font-semibold">Unique Visitors</h3>
                <p id="uniqueVisitors" class="ea-text-lg">0</p>
                <p id="uniqueVisitorsChange" class="ea-text-secondary">vs previous period</p>
            </div>
            <div class="ea-card">
                <h3 class="ea-font-semibold">Engagement</h3>
                <p id="avgTimeOnSite" class="ea-text-lg">0:00</p>
                <p class="ea-text-secondary">avg. time on site</p>
            </div>
            <div class="ea-card">
                <h3 class="ea-font-semibold">Bounce Rate</h3>
                <p id="bounceRate" class="ea-text-lg">0%</p>
                <p id="bounceRateChange" class="ea-text-secondary">vs previous period</p>
            </div>
        </div>

        {{-- Visitor Engagement Metrics --}}
        <div class="ea-grid ea-grid-cols-2">
            <div class="ea-card">
                <h3 class="ea-font-bold">Visit Frequency</h3>
                <div class="ea-grid ea-grid-cols-2">
                    <div>
                        <p class="ea-text-secondary">New Visitors</p>
                        <p id="newVisitors" class="ea-text-lg">0</p>
                    </div>
                    <div>
                        <p class="ea-text-secondary">Returning Visitors</p>
                        <p id="returningVisitors" class="ea-text-lg">0</p>
                    </div>
                    <div>
                        <p class="ea-text-secondary">Pages/Session</p>
                        <p id="pagesPerSession" class="ea-text-lg">0</p>
                    </div>
                    <div>
                        <p class="ea-text-secondary">Avg. Session Duration</p>
                        <p id="avgSessionDuration" class="ea-text-lg">0:00</p>
                    </div>
                </div>
            </div>
            <div class="ea-card">
                <h3 class="ea-font-bold">Page Views Over Time</h3>
                <div class="ea-chart-wrapper">
                    <canvas id="pageViewsChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Geographic & Technical Insights --}}
        <div class="ea-grid ea-grid-cols-1">
            <div class="ea-card">
                <h3 class="ea-font-bold">Top Countries</h3>
                <div class="ea-chart-wrapper">
                    <canvas id="countryChart"></canvas>
                </div>
                <div>
                    <table class="ea-table">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th class="ea-text-right">Visits</th>
                                <th class="ea-text-right">% of Total</th>
                            </tr>
                        </thead>
                        <tbody id="countryTable"></tbody>
                    </table>
                </div>
            </div>
            <div class="ea-card">
                <h3 class="ea-font-bold">Device Types</h3>
                <div class="ea-chart-wrapper">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
            <div class="ea-card">
                <h3 class="ea-font-bold">Browser Usage</h3>
                <div class="ea-chart-wrapper">
                    <canvas id="browserChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Page Performance --}}
        <div class="ea-card">
            <h3 class="ea-font-bold">Page Performance</h3>
            <div class="overflow-x-auto">
                <table class="ea-table">
                    <thead>
                        <tr>
                            <th>Page URL</th>
                            <th>Views</th>
                            <th>Unique Views</th>
                            <th>Avg. Time</th>
                            <th>Bounce Rate</th>
                            <th>Exit Rate</th>
                        </tr>
                    </thead>
                    <tbody id="topPagesTable">
                        <tr>
                            <td colspan="6" class="ea-text-center">Loading data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- User Flow --}}
        <div class="ea-card">
            <h3 class="ea-font-bold">User Flow</h3>
            <div class="ea-grid ea-grid-cols-3">
                <div>
                    <h4 class="ea-font-semibold">Top Entry Pages</h4>
                    <p class="ea-text-muted">Entry points</p>
                    <div id="entryPages"></div>
                </div>
                <div>
                    <h4 class="ea-font-semibold">Most Engaged Pages</h4>
                    <p class="ea-text-muted">Highest engagement</p>
                    <div id="engagedPages"></div>
                </div>
                <div>
                    <h4 class="ea-font-semibold">Top Exit Pages</h4>
                    <p class="ea-text-muted">Exit points</p>
                    <div id="exitPages"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/enhanced-analytics.js', 'vendor/enhanced-analytics/build')
@endpush
