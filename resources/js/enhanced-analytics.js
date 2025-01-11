import { Chart } from 'chart.js/auto';

// Initialize charts
let pageViewsChart, deviceChart, countryChart, browserChart;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Content Loaded');

    // Add a small delay to ensure configuration is loaded
    setTimeout(() => {
        console.log('Checking Enhanced Analytics configuration...');
        console.log('Window object has EnhancedAnalytics:', 'EnhancedAnalytics' in window);
        console.log('Current EnhancedAnalytics value:', window.EnhancedAnalytics);

        // Verify configuration is available
        if (!window.EnhancedAnalytics) {
            console.error('Analytics configuration is missing - window.EnhancedAnalytics is not defined');
            return;
        }

        if (!window.EnhancedAnalytics.config) {
            console.error('Analytics configuration is invalid - config object is missing');
            return;
        }

        if (!window.EnhancedAnalytics.config.routes) {
            console.error('Analytics configuration is invalid - routes are missing');
            return;
        }

        console.log('Initializing analytics with config:', window.EnhancedAnalytics.config);

        // Initialize elements
        const timeRange = document.getElementById('timeRange');
        const customDateInputs = document.getElementById('customDateInputs');
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        const exportButton = document.getElementById('exportData');

        // Initialize everything
        initializeCharts();
        setupEventListeners(timeRange, customDateInputs, startDate, endDate, exportButton);
        fetchData(timeRange, startDate, endDate);

        // Set up auto-refresh
        const refreshInterval = window.EnhancedAnalytics.config.refreshInterval || 300;
        setInterval(() => fetchData(timeRange, startDate, endDate), refreshInterval * 1000);
    }, 100); // Small delay to ensure configuration is loaded
});

function initializeCharts() {
    // Page Views Chart
    pageViewsChart = new Chart(document.getElementById('pageViewsChart'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Page Views',
                data: [],
                borderColor: '#3b82f6',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Device Distribution Chart
    deviceChart = new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b']
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
                backgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Browser Chart
    browserChart = new Chart(document.getElementById('browserChart'), {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function setupEventListeners(timeRange, customDateInputs, startDate, endDate, exportButton) {
    timeRange.addEventListener('change', () => {
        customDateInputs.classList.toggle('hidden', timeRange.value !== 'custom');
        fetchData(timeRange, startDate, endDate);
    });

    startDate.addEventListener('change', () => fetchData(timeRange, startDate, endDate));
    endDate.addEventListener('change', () => fetchData(timeRange, startDate, endDate));

    exportButton.addEventListener('click', () => {
        const params = new URLSearchParams();
        if (timeRange.value === 'custom') {
            params.append('start_date', startDate.value);
            params.append('end_date', endDate.value);
        } else {
            params.append('range', timeRange.value);
        }
        window.location.href = `${window.EnhancedAnalytics?.config?.routes?.export}?${params.toString()}`;
    });
}

async function fetchData(timeRange, startDate, endDate) {
    try {
        // Check if configuration exists
        if (!window.EnhancedAnalytics?.config?.routes?.data) {
            console.error('Analytics configuration is missing or invalid:', window.EnhancedAnalytics);
            return;
        }

        const params = new URLSearchParams();
        if (timeRange.value === 'custom') {
            params.append('start_date', startDate.value);
            params.append('end_date', endDate.value);
        } else {
            params.append('range', timeRange.value);
        }

        const url = `${window.EnhancedAnalytics.config.routes.data}?${params.toString()}`;
        console.log('Fetching analytics data from:', url);

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data) {
            console.log('Received analytics data:', data);
            updateDashboard(data);
        }
    } catch (error) {
        console.error('Error fetching analytics data:', error);
        console.error('Full error details:', {
            message: error.message,
            stack: error.stack
        });
    }
}

function updateDashboard(data) {
    // Update overview stats if available
    if (data.overview) {
        document.getElementById('totalVisits').textContent = data.overview.total_visits || 0;
        document.getElementById('uniqueVisitors').textContent = data.overview.unique_visitors || 0;
        document.getElementById('avgTimeOnSite').textContent = formatDuration(data.overview.average_time || 0);
        document.getElementById('bounceRate').textContent = `${data.overview.bounce_rate || 0}%`;
    }

    // Update charts with null checks
    if (data.page_views) updatePageViewsChart(data.page_views);
    if (data.device_stats) updateDeviceChart(data.device_stats);
    if (data.country_stats) updateCountryChart(data.country_stats);
    if (data.browser_stats) updateBrowserChart(data.browser_stats);
    if (data.top_pages) updateTopPagesTable(data.top_pages);
}

function updatePageViewsChart(data) {
    if (!data || !data.labels || !data.values) return;
    pageViewsChart.data.labels = data.labels;
    pageViewsChart.data.datasets[0].data = data.values;
    pageViewsChart.update();
}

function updateDeviceChart(data) {
    if (!data) return;
    deviceChart.data.labels = Object.keys(data);
    deviceChart.data.datasets[0].data = Object.values(data);
    deviceChart.update();
}

function updateCountryChart(data) {
    if (!Array.isArray(data)) return;
    countryChart.data.labels = data.map(item => item.country);
    countryChart.data.datasets[0].data = data.map(item => item.visits);
    countryChart.update();
}

function updateBrowserChart(data) {
    if (!data) return;
    browserChart.data.labels = Object.keys(data);
    browserChart.data.datasets[0].data = Object.values(data);
    browserChart.update();
}

function updateTopPagesTable(pages) {
    if (!Array.isArray(pages)) return;
    const tbody = document.getElementById('topPagesTable');
    tbody.innerHTML = pages.map(page => `
        <tr>
            <td class="max-w-md truncate">${page.url}</td>
            <td>${page.views || 0}</td>
            <td>${page.unique_views || 0}</td>
            <td>${formatDuration(page.average_time || 0)}</td>
            <td>${page.bounce_rate || 0}%</td>
        </tr>
    `).join('') || '<tr><td colspan="5" class="text-center py-4">No data available</td></tr>';
}

function formatDuration(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}