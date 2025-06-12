---
title: E-commerce Analytics
description: Complete e-commerce analytics platform built with Laravel DuckDB
---

# E-commerce Analytics Example

This comprehensive example demonstrates how to build a full-featured e-commerce analytics platform using Laravel DuckDB. It showcases real-world patterns for analyzing sales data, customer behavior, and product performance.

## Overview

The example includes:
- **Analytical Models** for sales, customers, and products
- **Analytics Engines** for executive dashboards and customer segmentation
- **File Operations** for data import/export
- **Real-time Dashboards** with complex queries
- **Performance Optimizations** for large datasets

## Project Structure

```
examples/ecommerce-analytics/
├── app/
│   ├── Models/
│   │   ├── Customer.php         # Customer analytical model
│   │   ├── Order.php           # Order analytical model  
│   │   ├── OrderItem.php       # Order items with product analysis
│   │   └── Product.php         # Product performance model
│   ├── Analytics/
│   │   ├── ExecutiveDashboard.php   # KPI calculations
│   │   ├── CustomerAnalytics.php    # RFM, CLV, segmentation
│   │   └── ProductAnalytics.php     # Performance, trends
│   └── Commands/
│       └── FileOperations.php       # Import/export utilities
├── database/
│   ├── migrations/             # Schema definitions
│   └── seeders/               # Sample data generators
└── scripts/
    ├── setup.php              # Database setup
    ├── analytics.php          # Run analytics
    └── dashboard.php          # Web dashboard
```

## Analytical Models

### Customer Model

```php
<?php

namespace App\Models;

use ConnerOiSu\LaravelDuckDB\Eloquent\AnalyticalModel;
use ConnerOiSu\LaravelDuckDB\Eloquent\Traits\HasWindowFunctions;

class Customer extends AnalyticalModel
{
    use HasWindowFunctions;
    
    protected $connection = 'analytics';
    protected $table = 'customers';
    
    /**
     * Calculate RFM (Recency, Frequency, Monetary) scores
     */
    public function scopeWithRfmScores($query)
    {
        return $query
            ->selectRaw("
                customer_id,
                DATEDIFF('day', MAX(order_date), CURRENT_DATE) as recency_days,
                COUNT(DISTINCT order_id) as frequency,
                SUM(order_total) as monetary_value,
                NTILE(5) OVER (ORDER BY DATEDIFF('day', MAX(order_date), CURRENT_DATE) DESC) as recency_score,
                NTILE(5) OVER (ORDER BY COUNT(DISTINCT order_id)) as frequency_score,
                NTILE(5) OVER (ORDER BY SUM(order_total)) as monetary_score
            ")
            ->from('orders')
            ->groupBy('customer_id');
    }
    
    /**
     * Calculate Customer Lifetime Value (CLV)
     */
    public function scopeWithLifetimeValue($query)
    {
        return $query
            ->selectRaw("
                customer_id,
                first_name,
                last_name,
                email,
                SUM(order_total) as historical_value,
                COUNT(DISTINCT order_id) as total_orders,
                AVG(order_total) as avg_order_value,
                DATEDIFF('day', MIN(order_date), MAX(order_date)) as customer_lifespan_days,
                -- Predictive CLV using simple model
                SUM(order_total) * 
                POWER(1.1, GREATEST(0, 12 - DATEDIFF('month', MAX(order_date), CURRENT_DATE))) as predicted_clv
            ")
            ->leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
            ->groupBy('customers.id', 'first_name', 'last_name', 'email');
    }
    
    /**
     * Identify churned customers
     */
    public function scopeChurned($query, $daysSinceLastOrder = 180)
    {
        return $query
            ->selectRaw("
                customers.*,
                MAX(orders.order_date) as last_order_date,
                DATEDIFF('day', MAX(orders.order_date), CURRENT_DATE) as days_since_last_order
            ")
            ->leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
            ->groupBy('customers.id')
            ->having('days_since_last_order', '>', $daysSinceLastOrder);
    }
}
```

### Order Model with Advanced Analytics

```php
<?php

namespace App\Models;

use ConnerOiSu\LaravelDuckDB\Eloquent\AnalyticalModel;
use ConnerOiSu\LaravelDuckDB\Eloquent\Traits\HasWindowFunctions;
use ConnerOiSu\LaravelDuckDB\Eloquent\Traits\QueriesDataFiles;

class Order extends AnalyticalModel
{
    use HasWindowFunctions, QueriesDataFiles;
    
    protected $connection = 'analytics';
    protected $table = 'orders';
    
    /**
     * Time-based cohort analysis
     */
    public function scopeCohortAnalysis($query, $cohortField = 'customer_first_order_date')
    {
        return $query
            ->selectRaw("
                DATE_TRUNC('month', c.first_order_date) as cohort_month,
                DATEDIFF('month', c.first_order_date, o.order_date) as months_since_first,
                COUNT(DISTINCT o.customer_id) as customers,
                SUM(o.order_total) as revenue
            ")
            ->from('orders as o')
            ->join(DB::raw("
                (SELECT customer_id, MIN(order_date) as first_order_date 
                 FROM orders GROUP BY customer_id) as c
            "), 'o.customer_id', '=', 'c.customer_id')
            ->groupBy('cohort_month', 'months_since_first')
            ->orderBy('cohort_month')
            ->orderBy('months_since_first');
    }
    
    /**
     * Revenue analytics with growth metrics
     */
    public function scopeRevenueAnalytics($query, $granularity = 'month')
    {
        return $query
            ->selectRaw("
                DATE_TRUNC('{$granularity}', order_date) as period,
                COUNT(*) as order_count,
                COUNT(DISTINCT customer_id) as unique_customers,
                SUM(order_total) as revenue,
                AVG(order_total) as avg_order_value,
                -- Year-over-year growth
                LAG(SUM(order_total), 12) OVER (ORDER BY DATE_TRUNC('{$granularity}', order_date)) as revenue_last_year,
                (SUM(order_total) - LAG(SUM(order_total), 12) OVER (ORDER BY DATE_TRUNC('{$granularity}', order_date))) / 
                NULLIF(LAG(SUM(order_total), 12) OVER (ORDER BY DATE_TRUNC('{$granularity}', order_date)), 0) * 100 as yoy_growth
            ")
            ->groupBy('period')
            ->orderBy('period');
    }
    
    /**
     * Export orders to data lake
     */
    public function exportToDataLake($year = null)
    {
        $query = $this->query();
        
        if ($year) {
            $query->whereYear('order_date', $year);
        }
        
        return $query->toParquetFile('s3://data-lake/orders/', [
            'partition_by' => ['year', 'month', 'region'],
            'compression' => 'snappy',
            'overwrite' => true
        ]);
    }
}
```

### Product Performance Model

```php
<?php

namespace App\Models;

use ConnerOiSu\LaravelDuckDB\Eloquent\AnalyticalModel;

class Product extends AnalyticalModel
{
    protected $connection = 'analytics';
    protected $table = 'products';
    
    /**
     * ABC analysis for inventory management
     */
    public function scopeAbcAnalysis($query)
    {
        return $query
            ->selectRaw("
                p.*,
                COALESCE(sales_data.revenue, 0) as total_revenue,
                COALESCE(sales_data.units_sold, 0) as total_units_sold,
                SUM(COALESCE(sales_data.revenue, 0)) OVER () as total_revenue_all,
                COALESCE(sales_data.revenue, 0) / NULLIF(SUM(COALESCE(sales_data.revenue, 0)) OVER (), 0) * 100 as revenue_percentage,
                SUM(COALESCE(sales_data.revenue, 0) / NULLIF(SUM(COALESCE(sales_data.revenue, 0)) OVER (), 0) * 100) 
                    OVER (ORDER BY COALESCE(sales_data.revenue, 0) DESC) as cumulative_percentage,
                CASE 
                    WHEN SUM(COALESCE(sales_data.revenue, 0) / NULLIF(SUM(COALESCE(sales_data.revenue, 0)) OVER (), 0) * 100) 
                         OVER (ORDER BY COALESCE(sales_data.revenue, 0) DESC) <= 80 THEN 'A'
                    WHEN SUM(COALESCE(sales_data.revenue, 0) / NULLIF(SUM(COALESCE(sales_data.revenue, 0)) OVER (), 0) * 100) 
                         OVER (ORDER BY COALESCE(sales_data.revenue, 0) DESC) <= 95 THEN 'B'
                    ELSE 'C'
                END as abc_category
            ")
            ->from('products as p')
            ->leftJoin(DB::raw("
                (SELECT 
                    product_id,
                    SUM(quantity * unit_price) as revenue,
                    SUM(quantity) as units_sold
                 FROM order_items
                 GROUP BY product_id) as sales_data
            "), 'p.id', '=', 'sales_data.product_id');
    }
    
    /**
     * Product velocity (turnover rate)
     */
    public function scopeWithVelocity($query, $days = 30)
    {
        return $query
            ->selectRaw("
                products.*,
                COALESCE(recent_sales.units_sold, 0) as units_sold_{$days}d,
                COALESCE(recent_sales.revenue, 0) as revenue_{$days}d,
                COALESCE(recent_sales.unique_customers, 0) as customers_{$days}d,
                COALESCE(recent_sales.units_sold, 0) / NULLIF(products.stock_quantity, 0) * 365 / {$days} as annual_turnover_rate
            ")
            ->leftJoin(DB::raw("
                (SELECT 
                    product_id,
                    SUM(quantity) as units_sold,
                    SUM(quantity * unit_price) as revenue,
                    COUNT(DISTINCT order_id) as unique_customers
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.order_date >= CURRENT_DATE - INTERVAL '{$days} days'
                 GROUP BY product_id) as recent_sales
            "), 'products.id', '=', 'recent_sales.product_id');
    }
}
```

## Analytics Engines

### Executive Dashboard

```php
<?php

namespace App\Analytics;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ExecutiveDashboard
{
    /**
     * Get key performance indicators
     */
    public function getKPIs($startDate = null, $endDate = null)
    {
        return Cache::remember('executive_kpis_' . md5($startDate . $endDate), 300, function () use ($startDate, $endDate) {
            $orderQuery = Order::query();
            
            if ($startDate) $orderQuery->where('order_date', '>=', $startDate);
            if ($endDate) $orderQuery->where('order_date', '<=', $endDate);
            
            $kpis = $orderQuery
                ->selectRaw("
                    COUNT(*) as total_orders,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    SUM(order_total) as total_revenue,
                    AVG(order_total) as avg_order_value,
                    PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY order_total) as median_order_value
                ")
                ->first();
            
            // Add growth metrics
            $previousPeriod = $orderQuery
                ->whereRaw("order_date >= ? - INTERVAL '1 year'", [$startDate])
                ->whereRaw("order_date <= ? - INTERVAL '1 year'", [$endDate])
                ->selectRaw("
                    COUNT(*) as total_orders,
                    SUM(order_total) as total_revenue
                ")
                ->first();
            
            $kpis->order_growth = (($kpis->total_orders - $previousPeriod->total_orders) / 
                                  $previousPeriod->total_orders) * 100;
            $kpis->revenue_growth = (($kpis->total_revenue - $previousPeriod->total_revenue) / 
                                    $previousPeriod->total_revenue) * 100;
            
            return $kpis;
        });
    }
    
    /**
     * Get time series metrics
     */
    public function getTimeSeriesMetrics($granularity = 'day', $metrics = ['revenue', 'orders', 'customers'])
    {
        $query = Order::query()
            ->selectRaw("DATE_TRUNC('{$granularity}', order_date) as period");
        
        if (in_array('revenue', $metrics)) {
            $query->selectRaw('SUM(order_total) as revenue');
        }
        
        if (in_array('orders', $metrics)) {
            $query->selectRaw('COUNT(*) as order_count');
        }
        
        if (in_array('customers', $metrics)) {
            $query->selectRaw('COUNT(DISTINCT customer_id) as unique_customers');
        }
        
        // Add moving averages
        $query->selectRaw("AVG(SUM(order_total)) OVER (
            ORDER BY DATE_TRUNC('{$granularity}', order_date)
            ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
        ) as revenue_ma7");
        
        return $query
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
    
    /**
     * Get top performers across dimensions
     */
    public function getTopPerformers($limit = 10)
    {
        return [
            'products' => Product::query()
                ->withVelocity(30)
                ->orderByDesc('revenue_30d')
                ->limit($limit)
                ->get(),
                
            'customers' => Customer::query()
                ->withLifetimeValue()
                ->orderByDesc('predicted_clv')
                ->limit($limit)
                ->get(),
                
            'categories' => DB::connection('analytics')
                ->table('products as p')
                ->join('order_items as oi', 'p.id', '=', 'oi.product_id')
                ->select('p.category')
                ->selectRaw('SUM(oi.quantity * oi.unit_price) as revenue')
                ->selectRaw('COUNT(DISTINCT oi.order_id) as order_count')
                ->groupBy('p.category')
                ->orderByDesc('revenue')
                ->limit($limit)
                ->get(),
        ];
    }
}
```

### Customer Analytics

```php
<?php

namespace App\Analytics;

use App\Models\Customer;
use App\Models\Order;

class CustomerAnalytics
{
    /**
     * Perform RFM segmentation
     */
    public function performRfmSegmentation()
    {
        $rfmScores = Customer::withRfmScores()->get();
        
        return $rfmScores->map(function ($customer) {
            $segment = $this->determineRfmSegment(
                $customer->recency_score,
                $customer->frequency_score,
                $customer->monetary_score
            );
            
            $customer->segment = $segment['name'];
            $customer->segment_description = $segment['description'];
            $customer->marketing_action = $segment['action'];
            
            return $customer;
        });
    }
    
    /**
     * Determine RFM segment based on scores
     */
    private function determineRfmSegment($r, $f, $m)
    {
        $segments = [
            'Champions' => [
                'condition' => $r >= 4 && $f >= 4 && $m >= 4,
                'description' => 'Best customers, buy frequently and spend the most',
                'action' => 'Reward with exclusive offers and early access'
            ],
            'Loyal Customers' => [
                'condition' => $f >= 4 && $m >= 3,
                'description' => 'Buy frequently with good monetary value',
                'action' => 'Upsell higher-value products'
            ],
            'Potential Loyalists' => [
                'condition' => $r >= 3 && $f >= 2 && $m >= 2,
                'description' => 'Recent customers with potential',
                'action' => 'Offer membership/loyalty programs'
            ],
            'At Risk' => [
                'condition' => $r <= 2 && $f >= 3 && $m >= 3,
                'description' => 'Previously good customers who haven\'t bought recently',
                'action' => 'Win-back campaigns with special offers'
            ],
            'Can\'t Lose Them' => [
                'condition' => $r <= 2 && $f >= 4 && $m >= 4,
                'description' => 'Best customers who are slipping away',
                'action' => 'Urgent win-back with personalized approach'
            ],
            'New Customers' => [
                'condition' => $r >= 4 && $f == 1,
                'description' => 'Recently made first purchase',
                'action' => 'Welcome series and onboarding'
            ],
            'Hibernating' => [
                'condition' => $r == 1 && $f <= 2,
                'description' => 'Long time since last purchase',
                'action' => 'Reactivation campaigns'
            ],
        ];
        
        foreach ($segments as $name => $segment) {
            if ($segment['condition']) {
                return array_merge(['name' => $name], $segment);
            }
        }
        
        return [
            'name' => 'Other',
            'description' => 'Mixed behavior patterns',
            'action' => 'Standard marketing campaigns'
        ];
    }
    
    /**
     * Calculate retention cohorts
     */
    public function getRetentionCohorts($cohortSize = 'month')
    {
        return Order::cohortAnalysis()
            ->selectRaw("
                cohort_month,
                months_since_first,
                customers,
                FIRST_VALUE(customers) OVER (PARTITION BY cohort_month ORDER BY months_since_first) as cohort_size,
                customers::FLOAT / FIRST_VALUE(customers) OVER (PARTITION BY cohort_month ORDER BY months_since_first) * 100 as retention_rate
            ")
            ->get()
            ->groupBy('cohort_month');
    }
    
    /**
     * Predict churn probability
     */
    public function predictChurn()
    {
        return Customer::query()
            ->selectRaw("
                c.*,
                COALESCE(customer_stats.days_since_last_order, 999) as days_since_last_order,
                COALESCE(customer_stats.order_count, 0) as total_orders,
                COALESCE(customer_stats.avg_days_between_orders, 999) as avg_days_between_orders,
                CASE 
                    WHEN COALESCE(customer_stats.days_since_last_order, 999) > 
                         COALESCE(customer_stats.avg_days_between_orders, 30) * 2 
                    THEN 'High'
                    WHEN COALESCE(customer_stats.days_since_last_order, 999) > 
                         COALESCE(customer_stats.avg_days_between_orders, 30) * 1.5 
                    THEN 'Medium'
                    ELSE 'Low'
                END as churn_risk
            ")
            ->from('customers as c')
            ->leftJoin(DB::raw("
                (SELECT 
                    customer_id,
                    DATEDIFF('day', MAX(order_date), CURRENT_DATE) as days_since_last_order,
                    COUNT(*) as order_count,
                    AVG(DATEDIFF('day', LAG(order_date) OVER (PARTITION BY customer_id ORDER BY order_date), order_date)) as avg_days_between_orders
                 FROM orders
                 GROUP BY customer_id) as customer_stats
            "), 'c.id', '=', 'customer_stats.customer_id')
            ->get();
    }
}
```

## Data Import/Export Operations

### File Operations Service

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class FileOperationsService
{
    /**
     * Export data to various formats
     */
    public function exportOrders($format = 'parquet', $options = [])
    {
        $query = Order::query()
            ->with(['items', 'customer'])
            ->whereYear('order_date', $options['year'] ?? date('Y'));
        
        switch ($format) {
            case 'parquet':
                return $query->toParquetFile($options['path'] ?? 's3://exports/orders/', [
                    'partition_by' => ['year', 'month'],
                    'compression' => 'snappy',
                    'overwrite' => true
                ]);
                
            case 'csv':
                return $query->toCsvFile($options['path'] ?? '/exports/orders.csv', [
                    'delimiter' => ',',
                    'header' => true,
                    'compression' => 'gzip'
                ]);
                
            case 'json':
                return $query->toJsonFile($options['path'] ?? '/exports/orders.json', [
                    'format' => 'lines',
                    'pretty' => false
                ]);
        }
    }
    
    /**
     * Import data from files
     */
    public function importFromDataLake($source)
    {
        // Import historical orders
        Order::insertFromParquet($source . '/orders/year=*/month=*/*.parquet');
        
        // Import product catalog
        Product::insertFromCsv($source . '/products/catalog.csv', [
            'delimiter' => ',',
            'header' => true,
            'columns' => [
                'sku' => 'sku',
                'name' => 'name',
                'category' => 'category',
                'price' => 'price'
            ]
        ]);
        
        // Process streaming data
        $this->processStreamingData($source . '/realtime/*.jsonl');
    }
    
    /**
     * Process streaming data files
     */
    private function processStreamingData($pattern)
    {
        $files = glob($pattern);
        
        foreach ($files as $file) {
            // Process file
            Order::fromJsonFile($file, ['format' => 'lines'])
                ->whereNull('processed_at')
                ->chunk(1000, function ($orders) {
                    // Process orders
                    foreach ($orders as $order) {
                        ProcessOrder::dispatch($order);
                    }
                });
            
            // Archive processed file
            Storage::move($file, str_replace('/realtime/', '/processed/', $file));
        }
    }
}
```

## Web Dashboard

### Dashboard Controller

```php
<?php

namespace App\Http\Controllers;

use App\Analytics\ExecutiveDashboard;
use App\Analytics\CustomerAnalytics;
use App\Analytics\ProductAnalytics;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private ExecutiveDashboard $executiveDashboard,
        private CustomerAnalytics $customerAnalytics,
        private ProductAnalytics $productAnalytics
    ) {}
    
    /**
     * Main dashboard view
     */
    public function index(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        
        $data = [
            'kpis' => $this->executiveDashboard->getKPIs($dateRange['start'], $dateRange['end']),
            'timeSeries' => $this->executiveDashboard->getTimeSeriesMetrics('day'),
            'topPerformers' => $this->executiveDashboard->getTopPerformers(),
            'customerSegments' => $this->customerAnalytics->performRfmSegmentation(),
            'productAnalysis' => $this->productAnalytics->getAbcAnalysis(),
        ];
        
        if ($request->wantsJson()) {
            return response()->json($data);
        }
        
        return view('dashboard.index', $data);
    }
    
    /**
     * Real-time metrics endpoint
     */
    public function realtime()
    {
        $metrics = Cache::remember('realtime_metrics', 10, function () {
            return [
                'current_visitors' => $this->getCurrentVisitors(),
                'recent_orders' => $this->getRecentOrders(5),
                'revenue_today' => $this->getTodaysRevenue(),
                'conversion_rate' => $this->getCurrentConversionRate(),
            ];
        });
        
        return response()->json($metrics);
    }
    
    /**
     * Export dashboard data
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $dateRange = $this->getDateRange($request);
        
        $exporter = new DashboardExporter();
        
        return $exporter->export($format, [
            'kpis' => $this->executiveDashboard->getKPIs($dateRange['start'], $dateRange['end']),
            'segments' => $this->customerAnalytics->performRfmSegmentation(),
            'products' => $this->productAnalytics->getTopProducts(100),
        ]);
    }
}
```

### Dashboard View

```blade
{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="dashboard-container">
    {{-- KPI Cards --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <h3>Total Revenue</h3>
            <div class="kpi-value">${{ number_format($kpis->total_revenue, 2) }}</div>
            <div class="kpi-change {{ $kpis->revenue_growth >= 0 ? 'positive' : 'negative' }}">
                {{ $kpis->revenue_growth >= 0 ? '↑' : '↓' }} 
                {{ abs(round($kpis->revenue_growth, 1)) }}% YoY
            </div>
        </div>
        
        <div class="kpi-card">
            <h3>Orders</h3>
            <div class="kpi-value">{{ number_format($kpis->total_orders) }}</div>
            <div class="kpi-change {{ $kpis->order_growth >= 0 ? 'positive' : 'negative' }}">
                {{ $kpis->order_growth >= 0 ? '↑' : '↓' }} 
                {{ abs(round($kpis->order_growth, 1)) }}% YoY
            </div>
        </div>
        
        <div class="kpi-card">
            <h3>Average Order Value</h3>
            <div class="kpi-value">${{ number_format($kpis->avg_order_value, 2) }}</div>
            <div class="kpi-subtitle">Median: ${{ number_format($kpis->median_order_value, 2) }}</div>
        </div>
        
        <div class="kpi-card">
            <h3>Customers</h3>
            <div class="kpi-value">{{ number_format($kpis->unique_customers) }}</div>
            <div class="kpi-subtitle">Active this period</div>
        </div>
    </div>
    
    {{-- Revenue Chart --}}
    <div class="chart-container">
        <h2>Revenue Trend</h2>
        <canvas id="revenueChart"></canvas>
    </div>
    
    {{-- Customer Segments --}}
    <div class="segments-container">
        <h2>Customer Segments</h2>
        <div class="segments-grid">
            @foreach($customerSegments->groupBy('segment') as $segment => $customers)
                <div class="segment-card">
                    <h4>{{ $segment }}</h4>
                    <div class="segment-count">{{ $customers->count() }} customers</div>
                    <div class="segment-value">${{ number_format($customers->sum('monetary_value'), 2) }}</div>
                </div>
            @endforeach
        </div>
    </div>
    
    {{-- Top Products --}}
    <div class="products-table">
        <h2>Top Products</h2>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Revenue (30d)</th>
                    <th>Units Sold</th>
                    <th>Velocity</th>
                    <th>ABC Category</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topPerformers['products'] as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>${{ number_format($product->revenue_30d, 2) }}</td>
                    <td>{{ number_format($product->units_sold_30d) }}</td>
                    <td>{{ round($product->annual_turnover_rate, 1) }}x/year</td>
                    <td><span class="badge badge-{{ strtolower($product->abc_category) }}">{{ $product->abc_category }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
// Revenue chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($timeSeries->pluck('period')),
        datasets: [{
            label: 'Daily Revenue',
            data: @json($timeSeries->pluck('revenue')),
            borderColor: 'rgb(59, 130, 246)',
            tension: 0.1
        }, {
            label: '7-Day Average',
            data: @json($timeSeries->pluck('revenue_ma7')),
            borderColor: 'rgb(249, 115, 22)',
            borderDash: [5, 5],
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Real-time updates
setInterval(async () => {
    const response = await fetch('/dashboard/realtime');
    const data = await response.json();
    updateRealtimeMetrics(data);
}, 10000);
</script>
@endpush
@endsection
```

## Performance Optimizations

### Caching Strategy

```php
// config/cache.php
'stores' => [
    'analytics' => [
        'driver' => 'redis',
        'connection' => 'analytics',
        'prefix' => 'analytics',
        'ttl' => 300, // 5 minutes for real-time data
    ],
],

// In analytics classes
public function getMetrics($useCache = true)
{
    if (!$useCache) {
        return $this->calculateMetrics();
    }
    
    return Cache::store('analytics')->remember(
        $this->getCacheKey(),
        $this->getCacheTtl(),
        fn() => $this->calculateMetrics()
    );
}
```

### Background Processing

```php
// app/Jobs/RefreshAnalytics.php
class RefreshAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    
    public function handle()
    {
        // Refresh materialized views
        DB::connection('analytics')->statement('
            CREATE OR REPLACE TABLE customer_metrics_mv AS
            SELECT * FROM customer_metrics_view
        ');
        
        // Pre-calculate expensive metrics
        $dashboard = new ExecutiveDashboard();
        $dashboard->preCalculateMetrics();
        
        // Clear old cache
        Cache::store('analytics')->tags(['dashboard'])->flush();
    }
}

// Schedule the job
Schedule::job(new RefreshAnalytics)->hourly();
```

## Running the Example

### 1. Setup

```bash
cd examples/ecommerce-analytics
composer install
php setup.php
```

### 2. Generate Sample Data

```bash
php artisan db:seed --class=EcommerceSeeder
# Generates 1M orders, 100K customers, 10K products
```

### 3. Run Analytics

```bash
php analytics.php
# Outputs key metrics and insights
```

### 4. Start Dashboard

```bash
php artisan serve
# Visit http://localhost:8000/dashboard
```

### 5. Export Data

```bash
php artisan analytics:export --format=parquet --destination=s3://data-lake/
```

## Key Takeaways

1. **Analytical Models**: Optimized for read-heavy OLAP workloads
2. **Window Functions**: Essential for time-series and ranking analytics
3. **File Operations**: Direct querying without ETL pipelines
4. **Performance**: Proper indexing and caching strategies
5. **Scalability**: Handles millions of records efficiently

## Next Steps

- Explore the [source code](https://github.com/laraduck/laraduck/tree/main/examples/ecommerce-analytics)
- Try the [Log Analysis](/examples/log-analysis) example
- Read about [Performance Optimization](/guides/performance)
- Learn about [Time Series Analysis](/guides/time-series)