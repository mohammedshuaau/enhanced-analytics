@extends('statamic::layout')

@section('title', 'Enhanced Analytics')

@push('styles')
    @vite(['resources/css/enhanced-analytics.css'], 'vendor/enhanced-analytics')
@endpush

@section('content')
    {{-- Initialize configuration in the head --}}
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            console.log('Setting up Enhanced Analytics configuration...');
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
            console.log('Enhanced Analytics configuration:', window.EnhancedAnalytics);
        </script>
    @endpush

    <div class="p-4">
        <!-- Header with Settings Toggle -->
        <header class="mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Analytics Dashboard</h1>
            <button id="settingsToggle" class="btn-primary flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Settings
            </button>
        </header>

        <!-- Settings Panel -->
        <div id="settingsPanel" class="hidden mb-6">
            <div class="card p-4">
                <h2 class="text-xl font-bold mb-4">Analytics Settings</h2>
                
                <!-- Geolocation Stats -->
                <div class="mb-6">
                    <h3 class="font-bold text-gray-700 mb-2">Geolocation Statistics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm text-gray-600">Total Lookups</p>
                            <p id="totalLookups" class="text-lg font-bold">0</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm text-gray-600">Success Rate</p>
                            <p id="successRate" class="text-lg font-bold">0%</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm text-gray-600">Unique IPs</p>
                            <p id="uniqueIps" class="text-lg font-bold">0</p>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-500">
                        Last Lookup: <span id="lastLookup">Never</span>
                    </div>
                </div>

                <!-- Cache Management -->
                <div class="mb-6">
                    <h3 class="font-bold text-gray-700 mb-2">Cache Management</h3>
                    <div class="flex items-center space-x-4">
                        <button id="clearGeoCache" class="btn">
                            Clear Geolocation Cache
                        </button>
                        <span id="cacheClearStatus" class="text-sm text-gray-500"></span>
                    </div>
                </div>

                <!-- Current Configuration -->
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Current Configuration</h3>
                    <div class="bg-gray-50 p-3 rounded">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                            <div>
                                <dt class="text-gray-600">Cache Duration</dt>
                                <dd>{{ config('enhanced-analytics.geolocation.cache_duration') / 60 }} hours</dd>
                            </div>
                            <div>
                                <dt class="text-gray-600">Rate Limit</dt>
                                <dd>{{ config('enhanced-analytics.geolocation.rate_limit') }} requests/minute</dd>
                            </div>
                            <div>
                                <dt class="text-gray-600">Processing Frequency</dt>
                                <dd>{{ config('enhanced-analytics.processing.frequency') }} minutes</dd>
                            </div>
                            <div>
                                <dt class="text-gray-600">Dashboard Refresh</dt>
                                <dd>{{ config('enhanced-analytics.dashboard.refresh_interval') }} seconds</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
            
        <!-- Date Range Selector -->
        <div class="flex items-center space-x-4 mb-6">
            <select id="timeRange" class="select-input">
                <option value="24hours">Last 24 Hours</option>
                <option value="7days" selected>Last 7 Days</option>
                <option value="30days">Last 30 Days</option>
                <option value="custom">Custom Range</option>
            </select>
            
            <div id="customDateInputs" class="hidden flex items-center space-x-2">
                <input type="date" id="startDate" class="input-text">
                <span>to</span>
                <input type="date" id="endDate" class="input-text">
            </div>

            <button id="exportData" class="btn-primary">
                Export Data
            </button>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="card p-4">
                <h3 class="font-bold text-gray-700">Total Visits</h3>
                <p id="totalVisits" class="text-2xl font-bold">0</p>
            </div>
            <div class="card p-4">
                <h3 class="font-bold text-gray-700">Unique Visitors</h3>
                <p id="uniqueVisitors" class="text-2xl font-bold">0</p>
            </div>
            <div class="card p-4">
                <h3 class="font-bold text-gray-700">Avg. Time on Site</h3>
                <p id="avgTimeOnSite" class="text-2xl font-bold">0:00</p>
            </div>
            <div class="card p-4">
                <h3 class="font-bold text-gray-700">Bounce Rate</h3>
                <p id="bounceRate" class="text-2xl font-bold">0%</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Page Views Over Time -->
            <div class="card p-4">
                <h3 class="font-bold mb-4">Page Views Over Time</h3>
                <div class="chart-wrapper">
                    <canvas id="pageViewsChart"></canvas>
                </div>
            </div>

            <!-- Device Distribution -->
            <div class="card p-4">
                <h3 class="font-bold mb-4">Device Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>

            <!-- Top Countries -->
            <div class="card p-4">
                <h3 class="font-bold mb-4">Top Countries</h3>
                <div class="chart-wrapper">
                    <canvas id="countryChart"></canvas>
                </div>
            </div>

            <!-- Browser Usage -->
            <div class="card p-4">
                <h3 class="font-bold mb-4">Browser Usage</h3>
                <div class="chart-wrapper">
                    <canvas id="browserChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Pages Table -->
        <div class="card p-4">
            <h3 class="font-bold mb-4">Top Pages</h3>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Page URL</th>
                            <th>Views</th>
                            <th>Unique Views</th>
                            <th>Avg. Time</th>
                            <th>Bounce Rate</th>
                        </tr>
                    </thead>
                    <tbody id="topPagesTable">
                        <tr>
                            <td colspan="5" class="text-center py-4">Loading data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Load Chart.js first --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    {{-- Load our enhanced analytics script --}}
    @vite(['resources/js/enhanced-analytics.js'], 'vendor/enhanced-analytics')
@endpush 