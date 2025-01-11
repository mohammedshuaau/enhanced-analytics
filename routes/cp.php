<?php

use Illuminate\Support\Facades\Route;
use Mohammedshuaau\EnhancedAnalytics\Http\Controllers\AnalyticsDashboardController;

Route::prefix('enhanced-analytics')->middleware(['statamic.cp'])->group(function () {
    Route::get('/', [AnalyticsDashboardController::class, 'index'])->name('enhanced-analytics.index');
    Route::get('/data', [AnalyticsDashboardController::class, 'getData'])->name('enhanced-analytics.data');
    Route::get('/export', [AnalyticsDashboardController::class, 'export'])->name('enhanced-analytics.export');
    Route::get('/geo-stats', [AnalyticsDashboardController::class, 'getGeolocationStats'])->name('enhanced-analytics.geo-stats');
    Route::post('/clear-cache', [AnalyticsDashboardController::class, 'clearGeolocationCache'])->name('enhanced-analytics.clear-cache');
}); 