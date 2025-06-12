---
title: Quick Start
description: Build your first analytical query with Laravel DuckDB in minutes
---

# Quick Start Guide

Let's build a simple sales analytics dashboard to demonstrate Laravel DuckDB's capabilities. In just a few minutes, you'll see how to perform complex analytical queries with familiar Laravel syntax.

## Setting Up the Example

### 1. Create an Analytical Model

```php
<?php

namespace App\Models\Analytics;

use ConnerOiSu\LaravelDuckDB\Eloquent\AnalyticalModel;

class Sale extends AnalyticalModel
{
    protected $connection = 'analytics';
    protected $table = 'sales';

    protected $fillable = [
        'order_id',
        'product_id',
        'customer_id',
        'quantity',
        'unit_price',
        'discount',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    // Computed column for revenue
    public function getRevenueAttribute()
    {
        return ($this->quantity * $this->unit_price) * (1 - $this->discount);
    }
}
```

### 2. Create Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use ConnerOiSu\LaravelDuckDB\Schema\Blueprint;
use ConnerOiSu\LaravelDuckDB\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'analytics';

    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->integer('product_id');
            $table->integer('customer_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 5, 2)->default(0);
            $table->timestamp('created_at');

            // DuckDB-specific: Add indexes for analytics
            $table->index(['created_at', 'product_id']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales');
    }
};
```

### 3. Seed Sample Data

```php
<?php

namespace Database\Seeders;

use App\Models\Analytics\Sale;
use Illuminate\Database\Seeder;

class SalesSeeder extends Seeder
{
    public function run()
    {
        $startDate = now()->subYear();
        $products = range(1, 100);
        $customers = range(1, 1000);

        // Generate 1 million sales records
        $batchSize = 10000;
        $totalRecords = 1000000;

        for ($i = 0; $i < $totalRecords; $i += $batchSize) {
            $records = [];

            for ($j = 0; $j < $batchSize; $j++) {
                $records[] = [
                    'order_id' => 'ORD-' . ($i + $j),
                    'product_id' => $products[array_rand($products)],
                    'customer_id' => $customers[array_rand($customers)],
                    'quantity' => rand(1, 10),
                    'unit_price' => rand(10, 1000) / 10,
                    'discount' => rand(0, 30) / 100,
                    'created_at' => $startDate->copy()->addMinutes($i + $j),
                ];
            }

            // Batch insert for performance
            Sale::insert($records);
        }
    }
}
```

Run the migration and seeder:

```bash
php artisan migrate --database=analytics
php artisan db:seed --class=SalesSeeder
```

## Basic Analytical Queries

### 1. Revenue by Month

```php
use App\Models\Analytics\Sale;

$monthlyRevenue = Sale::query()
    ->selectRaw("DATE_TRUNC('month', created_at) as month")
    ->selectRaw('SUM(quantity * unit_price * (1 - discount)) as revenue')
    ->selectRaw('COUNT(*) as orders')
    ->selectRaw('SUM(quantity) as units_sold')
    ->groupBy('month')
    ->orderBy('month')
    ->get();

// Results in milliseconds for 1M rows!
```

### 2. Top Products

```php
$topProducts = Sale::query()
    ->select('product_id')
    ->selectRaw('SUM(quantity * unit_price * (1 - discount)) as revenue')
    ->selectRaw('SUM(quantity) as units_sold')
    ->selectRaw('COUNT(DISTINCT customer_id) as unique_customers')
    ->groupBy('product_id')
    ->orderByDesc('revenue')
    ->limit(10)
    ->get();
```

### 3. Customer Segments with RFM Analysis

```php
$rfmAnalysis = Sale::query()
    ->select('customer_id')
    ->selectRaw("DATE_DIFF('day', MAX(created_at), CURRENT_DATE) as recency")
    ->selectRaw('COUNT(DISTINCT order_id) as frequency')
    ->selectRaw('SUM(quantity * unit_price * (1 - discount)) as monetary')
    ->groupBy('customer_id')
    ->having('frequency', '>', 1)
    ->get();

// Segment customers
$segments = $rfmAnalysis->map(function ($customer) {
    $score = 0;
    $score += $customer->recency < 30 ? 3 : ($customer->recency < 90 ? 2 : 1);
    $score += $customer->frequency > 10 ? 3 : ($customer->frequency > 5 ? 2 : 1);
    $score += $customer->monetary > 1000 ? 3 : ($customer->monetary > 500 ? 2 : 1);

    return [
        'customer_id' => $customer->customer_id,
        'segment' => match(true) {
            $score >= 8 => 'Champions',
            $score >= 6 => 'Loyal Customers',
            $score >= 4 => 'Potential Loyalists',
            default => 'At Risk',
        }
    ];
});
```

## Advanced Features

### 1. Window Functions

Find the best performing day for each product:

```php
$bestDays = Sale::query()
    ->selectRaw("DATE_TRUNC('day', created_at) as sale_date")
    ->select('product_id')
    ->selectRaw('SUM(quantity * unit_price * (1 - discount)) as daily_revenue')
    ->selectRaw('RANK() OVER (PARTITION BY product_id ORDER BY SUM(quantity * unit_price * (1 - discount)) DESC) as rank')
    ->groupBy('sale_date', 'product_id')
    ->qualify('rank = 1')
    ->get();
```

### 2. Moving Averages

Calculate 7-day moving average of sales:

```php
$movingAverage = Sale::query()
    ->selectRaw("DATE_TRUNC('day', created_at) as date")
    ->selectRaw('SUM(quantity * unit_price * (1 - discount)) as daily_revenue')
    ->selectRaw('AVG(SUM(quantity * unit_price * (1 - discount))) OVER (
        ORDER BY DATE_TRUNC(\'day\', created_at)
        ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
    ) as moving_avg_7d')
    ->groupBy('date')
    ->orderBy('date')
    ->get();
```

### 3. Year-over-Year Growth

```php
$yoyGrowth = Sale::query()
    ->selectRaw("DATE_TRUNC('month', created_at) as month")
    ->selectRaw('SUM(quantity * unit_price * (1 - discount)) as revenue')
    ->selectRaw("LAG(SUM(quantity * unit_price * (1 - discount)), 12) OVER (ORDER BY DATE_TRUNC('month', created_at)) as revenue_last_year")
    ->selectRaw("(SUM(quantity * unit_price * (1 - discount)) - LAG(SUM(quantity * unit_price * (1 - discount)), 12) OVER (ORDER BY DATE_TRUNC('month', created_at))) / LAG(SUM(quantity * unit_price * (1 - discount)), 12) OVER (ORDER BY DATE_TRUNC('month', created_at)) * 100 as yoy_growth_pct")
    ->groupBy('month')
    ->orderBy('month')
    ->get();
```

## File-Based Analytics

### Query CSV Files

```php
// Analyze uploaded CSV without importing
$csvAnalysis = Sale::fromCsvFile(storage_path('uploads/sales_2023.csv'))
    ->select('product_category')
    ->selectRaw('SUM(revenue) as total_revenue')
    ->groupBy('product_category')
    ->orderByDesc('total_revenue')
    ->get();
```

### Query Parquet Files from S3

```php
// Direct S3 query
$s3Analysis = Sale::fromParquetFile('s3://analytics-bucket/sales/year=2024/month=*/*.parquet')
    ->whereColumn('region', 'North America')
    ->selectRaw("DATE_TRUNC('week', order_date) as week")
    ->selectRaw('SUM(revenue) as weekly_revenue')
    ->groupBy('week')
    ->get();
```

### Export Results

```php
// Export analysis results to Parquet
$results = Sale::query()
    ->whereYear('created_at', 2024)
    ->get();

$results->toParquetFile(storage_path('exports/sales_2024.parquet'));

// Export to partitioned dataset
$results->toParquetFile(
    's3://exports/sales/',
    partitionBy: ['year', 'month']
);
```

## Building a Dashboard

Here's a complete dashboard controller:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Analytics\Sale;
use Illuminate\Http\Request;

class AnalyticsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('from', now()->subDays(30));
        $dateTo = $request->input('to', now());

        // Key metrics
        $metrics = Sale::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('SUM(quantity * unit_price * (1 - discount)) as total_revenue')
            ->selectRaw('COUNT(DISTINCT order_id) as total_orders')
            ->selectRaw('COUNT(DISTINCT customer_id) as unique_customers')
            ->selectRaw('AVG(quantity * unit_price * (1 - discount)) as avg_order_value')
            ->first();

        // Daily trend
        $dailyTrend = Sale::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw("DATE_TRUNC('day', created_at) as date")
            ->selectRaw('SUM(quantity * unit_price * (1 - discount)) as revenue')
            ->selectRaw('COUNT(DISTINCT order_id) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top products
        $topProducts = Sale::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('product_id')
            ->selectRaw('SUM(quantity * unit_price * (1 - discount)) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Customer distribution
        $customerDist = Sale::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('customer_id')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('CASE
                WHEN COUNT(*) = 1 THEN "One-time"
                WHEN COUNT(*) BETWEEN 2 AND 5 THEN "Occasional"
                WHEN COUNT(*) > 5 THEN "Frequent"
            END as customer_type')
            ->groupBy('customer_id')
            ->groupBy('customer_type')
            ->selectRaw('COUNT(*) as customer_count')
            ->get();

        return view('dashboard.analytics', compact(
            'metrics',
            'dailyTrend',
            'topProducts',
            'customerDist'
        ));
    }
}
```

## Performance Tips

1. **Use Batch Operations**

   ```php
   // Good: Batch insert
   Sale::insert($records);

   // Avoid: Individual inserts
   foreach ($records as $record) {
       Sale::create($record);
   }
   ```

2. **Leverage Indexes**

   ```php
   // Create indexes on frequently queried columns
   $table->index(['created_at', 'product_id']);
   ```

3. **Use Appropriate Data Types**
   ```php
   // Use appropriate types for better compression
   $table->integer('quantity');     // Not string
   $table->decimal('price', 10, 2); // Not float
   ```

## Next Steps

Congratulations! You've built your first analytical queries with Laravel DuckDB. To learn more:

- Explore [Analytical Models](/concepts/analytical-models/) for advanced model features
- Learn about [File Querying](/concepts/file-querying/) to work with external data
- Master [Window Functions](/guides/window-functions/) for complex analytics
- Optimize performance with our [Performance Guide](/guides/performance/)

Ready to dive deeper? Check out our [comprehensive examples](/examples/ecommerce/) for real-world use cases.
