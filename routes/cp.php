<?php

use Illuminate\Support\Facades\Route;
use Mohammedshuaau\EnhancedAnalytics\Http\Controllers\AnalyticsDashboardController;

Route::prefix('enhanced-analytics')->group(function () {
    Route::get('/', [AnalyticsDashboardController::class, 'index'])->name('enhanced-analytics.index');
    Route::get('/data', [AnalyticsDashboardController::class, 'getData'])->name('enhanced-analytics.data');
    Route::get('/export', [AnalyticsDashboardController::class, 'export'])->name('enhanced-analytics.export');
}); 