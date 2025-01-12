@extends('statamic::layout')

@section('title', 'Enhanced Analytics')

@push('styles')
    @vite('resources/css/enhanced-analytics.css')
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

    <div class="p-4">
        {{-- Header with Controls --}}
        <div class="mb-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <select id="dateRange" class="select-input dark:bg-gray-800 dark:text-white dark:border-gray-700">
                    <option value="24hours">Last 24 Hours</option>
                    <option value="7days" selected>Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="custom">Custom Range</option>
                </select>
                <div id="customDateRange" class="hidden flex items-center space-x-2">
                    <input type="date" id="startDate" class="input-text dark:bg-gray-800 dark:text-white dark:border-gray-700">
                    <span class="dark:text-white">to</span>
                    <input type="date" id="endDate" class="input-text dark:bg-gray-800 dark:text-white dark:border-gray-700">
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <button id="refreshData" class="btn-primary dark:bg-blue-600 dark:hover:bg-blue-700">
                    Refresh Data
                </button>
                <button id="exportData" class="btn-primary dark:bg-blue-600 dark:hover:bg-blue-700">
                    Export Data
                </button>
                <button id="toggleSettings" class="btn dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>Settings</span>
                </button>
            </div>
        </div>

        {{-- Settings Panel --}}
        <div id="settingsPanel" class="card hidden mb-6 dark:bg-gray-800 rounded shadow-sm p-4">
            <h3 class="text-lg font-bold mb-4 dark:text-white">Analytics Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <h4 class="font-semibold mb-2 dark:text-white">Geolocation Stats</h4>
                    <p class="dark:text-gray-300">Total Lookups: <span id="totalLookups" class="dark:text-white">0</span></p>
                    <p class="dark:text-gray-300">Success Rate: <span id="successRate" class="dark:text-white">0%</span></p>
                    <p class="dark:text-gray-300">Unique IPs: <span id="uniqueIps" class="dark:text-white">0</span></p>
                    <p class="dark:text-gray-300">Last Lookup: <span id="lastLookup" class="dark:text-white">Never</span></p>
                    <button id="clearCache" class="btn-primary dark:bg-blue-600 dark:hover:bg-blue-700 mt-2">Clear Geo Cache</button>
                </div>
                <div>
                    <h4 class="font-semibold mb-2 dark:text-white">Current Configuration</h4>
                    <p class="dark:text-gray-300">Cache Duration: <span class="dark:text-white">{{ config('enhanced-analytics.geolocation.cache_duration', 1440) }} minutes</span></p>
                    <p class="dark:text-gray-300">Rate Limit: <span class="dark:text-white">{{ config('enhanced-analytics.geolocation.rate_limit', 45) }} requests/minute</span></p>
                    <p class="dark:text-gray-300">Processing: Every <span class="dark:text-white">{{ config('enhanced-analytics.processing.frequency', 15) }} minutes</span></p>
                    <p class="dark:text-gray-300">Dashboard Refresh: <span class="dark:text-white">{{ config('enhanced-analytics.dashboard.refresh_interval', 300) }} seconds</span></p>
                </div>
            </div>
        </div>

        {{-- Quick Stats Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="card p-4 dark:bg-gray-800">
                <h3 class="font-bold text-gray-700 dark:text-gray-300">Total Visits</h3>
                <p id="totalVisits" class="text-2xl font-bold dark:text-white">0</p>
                <p id="totalVisitsChange" class="text-sm text-gray-500 dark:text-gray-400">vs previous period</p>
            </div>
            <div class="card p-4 dark:bg-gray-800">
                <h3 class="font-bold text-gray-700 dark:text-gray-300">Unique Visitors</h3>
                <p id="uniqueVisitors" class="text-2xl font-bold dark:text-white">0</p>
                <p id="uniqueVisitorsChange" class="text-sm text-gray-500 dark:text-gray-400">vs previous period</p>
            </div>
            <div class="card p-4 dark:bg-gray-800">
                <h3 class="font-bold text-gray-700 dark:text-gray-300">Engagement</h3>
                <p id="avgTimeOnSite" class="text-2xl font-bold dark:text-white">0:00</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">avg. time on site</p>
            </div>
            <div class="card p-4 dark:bg-gray-800">
                <h3 class="font-bold text-gray-700 dark:text-gray-300">Bounce Rate</h3>
                <p id="bounceRate" class="text-2xl font-bold dark:text-white">0%</p>
                <p id="bounceRateChange" class="text-sm text-gray-500 dark:text-gray-400">vs previous period</p>
            </div>
        </div>

        {{-- Visitor Engagement Metrics --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="card p-4 dark:bg-gray-800">
                <h3 class="font-bold mb-4 dark:text-white">Visit Frequency</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">New Visitors</p>
                        <p id="newVisitors" class="text-xl font-bold dark:text-white">0</p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Returning Visitors</p>
                        <p id="returningVisitors" class="text-xl font-bold dark:text-white">0</p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Pages/Session</p>
                        <p id="pagesPerSession" class="text-xl font-bold dark:text-white">0</p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Avg. Session Duration</p>
                        <p id="avgSessionDuration" class="text-xl font-bold dark:text-white">0:00</p>
                    </div>
                </div>
            </div>
            <div class="card p-4 dark:bg-gray-800">
                <h3 class="font-bold mb-4 dark:text-white">Page Views Over Time</h3>
                <div class="chart-wrapper">
                    <canvas id="pageViewsChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Geographic & Technical Insights --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="card p-4 dark:bg-gray-800">
                <h3 class="font-bold mb-4 dark:text-white">Top Countries</h3>
                <div class="chart-wrapper">
                    <canvas id="countryChart"></canvas>
                </div>
                <div class="mt-4">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left dark:text-gray-300">Country</th>
                                <th class="text-right dark:text-gray-300">Visits</th>
                                <th class="text-right dark:text-gray-300">% of Total</th>
                            </tr>
                        </thead>
                        <tbody id="countryTable" class="dark:text-gray-400"></tbody>
                    </table>
                </div>
            </div>
            <div class="card p-4 dark:bg-gray-800">
                <h3 class="font-bold mb-4 dark:text-white">Device & Browser Stats</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex w-1/2">
                        <h4 class="font-semibold mb-2 dark:text-gray-300">Devices</h4>
                        <div class="chart-wrapper flex items-center justify-center">
                            <canvas id="deviceChart"></canvas>
                        </div>
                    </div>
                    <div class="flex w-1/2">
                        <h4 class="font-semibold mb-2 dark:text-gray-300">Browsers</h4>
                        <div class="chart-wrapper">
                            <canvas id="browserChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Page Performance --}}
        <div class="card p-4 mb-8 dark:bg-gray-800">
            <h3 class="font-bold mb-4 dark:text-white">Page Performance</h3>
            <div class="overflow-x-auto">
                <table class="data-table w-full">
                    <thead>
                        <tr>
                            <th class="dark:text-gray-300">Page URL</th>
                            <th class="dark:text-gray-300">Views</th>
                            <th class="dark:text-gray-300">Unique Views</th>
                            <th class="dark:text-gray-300">Avg. Time</th>
                            <th class="dark:text-gray-300">Bounce Rate</th>
                            <th class="dark:text-gray-300">Exit Rate</th>
                        </tr>
                    </thead>
                    <tbody id="topPagesTable" class="dark:text-gray-400">
                        <tr>
                            <td colspan="6" class="text-center py-4 dark:text-gray-400">Loading data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- User Flow --}}
        <div class="card p-4 dark:bg-gray-800">
            <h3 class="font-bold mb-4 dark:text-white">User Flow</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <h4 class="font-semibold mb-2 dark:text-gray-300">Top Entry Pages</h4>
                    <div id="entryPages" class="space-y-2 dark:text-gray-400"></div>
                </div>
                <div>
                    <h4 class="font-semibold mb-2 dark:text-gray-300">Most Engaged Pages</h4>
                    <div id="engagedPages" class="space-y-2 dark:text-gray-400"></div>
                </div>
                <div>
                    <h4 class="font-semibold mb-2 dark:text-gray-300">Top Exit Pages</h4>
                    <div id="exitPages" class="space-y-2 dark:text-gray-400"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Include Alpine.js from CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Include Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script>
        // Initialize Alpine.js
        window.Alpine = Alpine;
        Alpine.start();

        // Chart.js instances
        let pageViewsChart, deviceChart, countryChart, browserChart;

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.location.pathname.includes(`/enhanced-analytics`)) {
                return;
            }

            initializeCharts();
            setupEventListeners();
            fetchData();
            setupAutoRefresh();
        });

        function initializeCharts() {
            // Page Views Chart
            pageViewsChart = new Chart(document.getElementById('pageViewsChart'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Total Views',
                        data: [],
                        borderColor: 'rgb(59, 130, 246)',
                        tension: 0.1
                    }, {
                        label: 'Unique Views',
                        data: [],
                        borderColor: 'rgb(16, 185, 129)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            // Device Chart
            deviceChart = new Chart(document.getElementById('deviceChart'), {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)',
                            'rgb(251, 191, 36)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Country Chart
            countryChart = new Chart(document.getElementById('countryChart'), {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Visits',
                        data: [],
                        backgroundColor: 'rgb(59, 130, 246)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y'
                }
            });

            // Browser Chart
            browserChart = new Chart(document.getElementById('browserChart'), {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)',
                            'rgb(251, 191, 36)',
                            'rgb(236, 72, 153)',
                            'rgb(124, 58, 237)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        function setupEventListeners() {
            // Date range selector
            document.getElementById('dateRange').addEventListener('change', function (e) {
                const customRange = document.getElementById('customDateRange');
                if (e.target.value === 'custom') {
                    customRange.classList.remove('hidden');
                } else {
                    customRange.classList.add('hidden');
                    fetchData();
                }
            });

            // Custom date inputs
            ['startDate', 'endDate'].forEach(id => {
                document.getElementById(id).addEventListener('change', fetchData);
            });

            // Settings toggle
            document.getElementById('toggleSettings').addEventListener('click', function () {
                const panel = document.getElementById('settingsPanel');
                panel.classList.toggle('hidden');
            });

            // Refresh button
            document.getElementById('refreshData').addEventListener('click', fetchData);

            // Export button
            document.getElementById('exportData').addEventListener('click', exportData);

            // Clear cache button
            document.getElementById('clearCache').addEventListener('click', clearGeolocationCache);
        }

        function setupAutoRefresh() {
            const interval = window.EnhancedAnalytics.config.refreshInterval * 1000;
            setInterval(fetchData, interval);
        }

        async function fetchData() {
            try {
                const dateRange = document.getElementById('dateRange').value;
                let params = new URLSearchParams({ range: dateRange });

                if (dateRange === 'custom') {
                    const startDate = document.getElementById('startDate').value;
                    const endDate = document.getElementById('endDate').value;
                    if (startDate && endDate) {
                        params.append('start_date', startDate);
                        params.append('end_date', endDate);
                    }
                }

                const response = await fetch(`${window.EnhancedAnalytics.config.routes.data}?${params}`);
                if (!response.ok) throw new Error('Failed to fetch analytics data');

                const data = await response.json();
                updateDashboard(data);
                await fetchGeolocationStats();
            } catch (error) {
                console.error('Error fetching analytics data:', error);
            }
        }

        function updateDashboard(data) {
            // Update overview stats
            if (data.overview) {
                document.getElementById('totalVisits').textContent = data.overview.total_visits.toLocaleString();
                document.getElementById('uniqueVisitors').textContent = data.overview.unique_visitors.toLocaleString();
                document.getElementById('avgTimeOnSite').textContent = formatDuration(data.overview.avg_time_on_site);
                document.getElementById('bounceRate').textContent = `${(data.overview.bounce_rate * 100).toFixed(1)}%`;

                // Update comparison stats
                updateComparisonStats(data.overview.comparisons);
            }

            // Update engagement metrics
            if (data.engagement) {
                document.getElementById('newVisitors').textContent = data.engagement.new_visitors.toLocaleString();
                document.getElementById('returningVisitors').textContent = data.engagement.returning_visitors.toLocaleString();
                document.getElementById('pagesPerSession').textContent = data.engagement.pages_per_session.toFixed(1);
                document.getElementById('avgSessionDuration').textContent = formatDuration(data.engagement.avg_session_duration);
            }

            // Update charts
            if (data.page_views) updatePageViewsChart(data.page_views);
            if (data.device_stats) updateDeviceChart(data.device_stats);
            if (data.country_stats) {
                updateCountryChart(data.country_stats);
                updateCountryTable(data.country_stats, data.overview.total_visits);
            }
            if (data.browser_stats) updateBrowserChart(data.browser_stats);
            if (data.top_pages) updateTopPagesTable(data.top_pages);
            if (data.user_flow) updateUserFlow(data.user_flow);
        }

        function updateComparisonStats(comparisons) {
            if (!comparisons) return;

            const elements = {
                totalVisitsChange: ['totalVisitsChange', comparisons.total_visits],
                uniqueVisitorsChange: ['uniqueVisitorsChange', comparisons.unique_visitors],
                bounceRateChange: ['bounceRateChange', comparisons.bounce_rate]
            };

            for (const [id, value] of Object.entries(elements)) {
                const element = document.getElementById(id);
                if (!element) continue;

                const change = value >= 0 ? `+${value}%` : `${value}%`;
                const color = value >= 0 ? 'text-green-600' : 'text-red-600';
                element.textContent = `${change} vs previous period`;
                element.className = `text-sm ${color}`;
            }
        }

        function updatePageViewsChart(data) {
            if (!data || !Array.isArray(data)) return;

            pageViewsChart.data.labels = data.map(item => item.date);
            pageViewsChart.data.datasets[0].data = data.map(item => item.total_views);
            pageViewsChart.data.datasets[1].data = data.map(item => item.unique_views);
            pageViewsChart.update();
        }

        function updateDeviceChart(data) {
            if (!data || !Array.isArray(data)) return;

            deviceChart.data.labels = data.map(item => item.dimension_value);
            deviceChart.data.datasets[0].data = data.map(item => item.total);
            deviceChart.update();
        }

        function updateCountryChart(data) {
            if (!Array.isArray(data)) return;

            countryChart.data.labels = data.map(item => item.dimension_value);
            countryChart.data.datasets[0].data = data.map(item => item.total);
            countryChart.update();
        }

        function updateCountryTable(data, totalVisits) {
            if (!Array.isArray(data)) return;

            const tbody = document.getElementById('countryTable');
            tbody.innerHTML = data.map(country => `
            <tr>
                <td>${country.dimension_value}</td>
                <td class="text-right">${country.total.toLocaleString()}</td>
                <td class="text-right">${((country.total / totalVisits) * 100).toFixed(1)}%</td>
            </tr>
        `).join('');
        }

        function updateBrowserChart(data) {
            if (!Array.isArray(data)) return;

            browserChart.data.labels = data.map(item => item.dimension_value);
            browserChart.data.datasets[0].data = data.map(item => item.total);
            browserChart.update();
        }

        function updateTopPagesTable(pages) {
            if (!Array.isArray(pages)) return;

            const tbody = document.getElementById('topPagesTable');
            tbody.innerHTML = pages.map(page => `
            <tr>
                <td class="max-w-md truncate">${page.page_url}</td>
                <td>${page.views.toLocaleString()}</td>
                <td>${page.unique_views.toLocaleString()}</td>
                <td>${formatDuration(page.avg_time)}</td>
                <td>${(page.bounce_rate * 100).toFixed(1)}%</td>
                <td>${(page.exit_rate * 100).toFixed(1)}%</td>
            </tr>
        `).join('');
        }

        function updateUserFlow(flow) {
            if (!flow) return;

            // Update entry pages
            const entryPages = document.getElementById('entryPages');
            entryPages.innerHTML = flow.entry_pages.map(page => `
            <div class="flex justify-between items-center">
                <span class="truncate">${page.page_url}</span>
                <span class="text-gray-500">${page.count.toLocaleString()}</span>
            </div>
        `).join('');

            // Update engaged pages
            const engagedPages = document.getElementById('engagedPages');
            engagedPages.innerHTML = flow.engaged_pages.map(page => `
            <div class="flex justify-between items-center">
                <span class="truncate">${page.url}</span>
                <span class="text-gray-500">${formatDuration(page.avg_time)}</span>
            </div>
        `).join('');

            // Update exit pages
            const exitPages = document.getElementById('exitPages');
            exitPages.innerHTML = flow.exit_pages.map(page => `
            <div class="flex justify-between items-center">
                <span class="truncate">${page.url}</span>
                <span class="text-gray-500">${(page.exit_rate * 100).toFixed(1)}%</span>
            </div>
        `).join('');
        }

        async function fetchGeolocationStats() {
            try {
                const response = await fetch(window.EnhancedAnalytics.config.routes.geoStats);
                if (!response.ok) throw new Error('Failed to fetch geolocation stats');

                const stats = await response.json();
                updateGeolocationStats(stats);
            } catch (error) {
                console.error('Error fetching geolocation stats:', error);
            }
        }

        function updateGeolocationStats(stats) {
            document.getElementById('totalLookups').textContent = stats.total_lookups.toLocaleString();

            const successRate = stats.total_lookups > 0
                ? ((stats.successful_lookups / stats.total_lookups) * 100).toFixed(1)
                : 0;
            document.getElementById('successRate').textContent = `${successRate}%`;

            document.getElementById('uniqueIps').textContent = stats.unique_ips.length.toLocaleString();

            const lastLookup = stats.last_lookup
                ? new Date(stats.last_lookup).toLocaleString()
                : 'Never';
            document.getElementById('lastLookup').textContent = lastLookup;
        }

        async function clearGeolocationCache() {
            try {
                const button = document.getElementById('clearCache');
                button.disabled = true;
                button.textContent = 'Clearing...';

                const response = await fetch(window.EnhancedAnalytics.config.routes.clearCache, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Failed to clear cache');

                const result = await response.json();
                button.textContent = 'Cleared!';
                setTimeout(() => {
                    button.disabled = false;
                    button.textContent = 'Clear Geo Cache';
                }, 2000);

                await fetchGeolocationStats();
            } catch (error) {
                console.error('Error clearing geolocation cache:', error);
                const button = document.getElementById('clearCache');
                button.textContent = 'Error!';
                setTimeout(() => {
                    button.disabled = false;
                    button.textContent = 'Clear Geo Cache';
                }, 2000);
            }
        }

        async function exportData() {
            try {
                const dateRange = document.getElementById('dateRange').value;
                let params = new URLSearchParams({ range: dateRange });

                if (dateRange === 'custom') {
                    const startDate = document.getElementById('startDate').value;
                    const endDate = document.getElementById('endDate').value;
                    if (startDate && endDate) {
                        params.append('start_date', startDate);
                        params.append('end_date', endDate);
                    }
                }

                window.location.href = `${window.EnhancedAnalytics.config.routes.export}?${params}`;
            } catch (error) {
                console.error('Error exporting data:', error);
            }
        }

        function formatDuration(seconds) {
            if (!seconds) return '0:00';
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
    </script>


@endpush
