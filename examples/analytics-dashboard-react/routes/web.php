<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['web'])->group(function () {
    // Main Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Analytics Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/sales', [AnalyticsController::class, 'sales'])->name('sales');
        Route::get('/customers', [AnalyticsController::class, 'customers'])->name('customers');
        Route::get('/products', [AnalyticsController::class, 'products'])->name('products');
        Route::get('/advanced', [AnalyticsController::class, 'advanced'])->name('advanced');
        Route::get('/export', [AnalyticsController::class, 'export'])->name('export');
    });
    
});