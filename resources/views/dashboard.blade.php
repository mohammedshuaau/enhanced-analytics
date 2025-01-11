@extends('statamic::layout')

@section('title', 'Enhanced Analytics')

@push('styles')
    @vite(['resources/css/enhanced-analytics.css'], 'vendor/enhanced-analytics')
@endpush

@section('content')
    {{-- Initialize configuration in the head --}}
    @push('head')
        <script>
            console.log('Setting up Enhanced Analytics configuration...');
            window.EnhancedAnalytics = {
                config: {
                    refreshInterval: {{ config('enhanced-analytics.dashboard.refresh_interval', 300) }},
                    routes: {
                        data: '{!! cp_route('enhanced-analytics.data') !!}',
                        export: '{!! cp_route('enhanced-analytics.export') !!}'
                    }
                }
            };
            console.log('Enhanced Analytics configuration:', window.EnhancedAnalytics);
        </script>
    @endpush

    <div class="p-4">
        <!-- Header -->
        <header class="mb-6">
            <h1 class="text-2xl font-bold mb-4">Analytics Dashboard</h1>
            
            <!-- Date Range Selector -->
            <div class="flex items-center space-x-4">
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
        </header>

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