import { Chart } from 'chart.js/auto';
import Alpine from 'alpinejs';

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
        total_visits_change: {
            value: comparisons.total_visits,
            is_positive_good: true
        },
        unique_visitors_change: {
            value: comparisons.unique_visitors,
            is_positive_good: true
        },
        bounce_rate_change: {
            value: comparisons.bounce_rate,
            is_positive_good: false
        },
    };


    for (const [id, data] of Object.entries(elements)) {
        const element = document.getElementById(id);
        if (!element) continue;

        const isPositive = data.value >= 0;
        const change = isPositive ? `+${data.value}%` : `${data.value}%`;
        const color =
            (isPositive && data.positiveGood) || (!isPositive && !data.positiveGood)
                ? 'text-green-600'
                : 'text-red-600';
        element.textContent = `${change} vs previous period`;
        element.className = `text-sm ea-text-secondary ${color}`;
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
