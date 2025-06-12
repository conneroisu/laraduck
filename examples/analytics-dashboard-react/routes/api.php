<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('api')->group(function () {
    // Dashboard API endpoints
    Route::get('/dashboard-metrics', [DashboardController::class, 'index'])->name('api.dashboard.metrics');
    
    // Analytics API endpoints
    Route::get('/sales-data', [AnalyticsController::class, 'sales'])->name('api.sales.data');
    Route::get('/customer-data', [AnalyticsController::class, 'customers'])->name('api.customers.data');
    Route::get('/product-data', [AnalyticsController::class, 'products'])->name('api.products.data');
});