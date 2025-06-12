---
title: Analytical Models
description: Learn how to use Analytical Models for OLAP workloads in Laravel
---

# Analytical Models

Analytical Models are the cornerstone of Laravel DuckDB, providing an Eloquent-compatible interface optimized for analytical workloads (OLAP) rather than transactional workloads (OLTP).

## Understanding Analytical Models

Traditional Eloquent models are designed for CRUD operations - creating, reading, updating, and deleting individual records. Analytical Models flip this paradigm, optimizing for:

- **Bulk operations** over single-row operations
- **Read-heavy** workloads over write-heavy
- **Aggregations** over individual record access
- **Column-oriented** operations over row-oriented

## Creating an Analytical Model

### Basic Model

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
        'created_at'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
    ];
}
```

### Advanced Model with Features

```php
<?php

namespace App\Models\Analytics;

use ConnerOiSu\LaravelDuckDB\Eloquent\AnalyticalModel;
use ConnerOiSu\LaravelDuckDB\Eloquent\Traits\HasWindowFunctions;
use ConnerOiSu\LaravelDuckDB\Eloquent\Traits\QueriesDataFiles;

class CustomerMetric extends AnalyticalModel
{
    use HasWindowFunctions, QueriesDataFiles;
    
    protected $connection = 'analytics';
    protected $table = 'customer_metrics';
    
    // Disable single-row operations by default
    protected $allowsSingleRowOperations = false;
    
    // Optimize for read-only operations
    protected $readOnly = true;
    
    // Define computed columns
    protected $appends = ['lifetime_value', 'churn_risk'];
    
    // Analytical scopes
    public function scopeHighValue($query)
    {
        return $query->where('total_revenue', '>', 10000);
    }
    
    public function scopeActive($query)
    {
        return $query->where('last_order_date', '>', now()->subDays(90));
    }
    
    // Computed attributes
    public function getLifetimeValueAttribute()
    {
        return $this->total_revenue * $this->retention_probability;
    }
    
    public function getChurnRiskAttribute()
    {
        $daysSinceLastOrder = now()->diffInDays($this->last_order_date);
        return match(true) {
            $daysSinceLastOrder <= 30 => 'low',
            $daysSinceLastOrder <= 90 => 'medium',
            default => 'high'
        };
    }
}
```

## Key Differences from Standard Models

### 1. Single-Row Operations

By default, Analytical Models discourage single-row operations:

```php
// This will throw an exception
$sale = new Sale(['product_id' => 1, 'quantity' => 5]);
$sale->save(); // ❌ RuntimeException: Single-row operations not recommended

// Enable if absolutely necessary
class Sale extends AnalyticalModel
{
    protected $allowsSingleRowOperations = true; // ⚠️ Use with caution
}

// Better: Use batch operations
Sale::insert([
    ['product_id' => 1, 'quantity' => 5, 'unit_price' => 99.99],
    ['product_id' => 2, 'quantity' => 3, 'unit_price' => 49.99],
    // ... thousands more rows
]);
```

### 2. Batch Insert Methods

Analytical Models provide optimized batch insert methods:

```php
// Basic batch insert
Sale::batchInsert($records); // Automatically chunks large datasets

// With progress callback
Sale::batchInsert($records, chunkSize: 10000, function ($inserted, $total) {
    $this->info("Inserted {$inserted}/{$total} records");
});

// From file
Sale::insertFromCsv(storage_path('imports/sales.csv'), [
    'delimiter' => ',',
    'header' => true,
    'columns' => ['order_id', 'product_id', 'quantity', 'unit_price']
]);

// From query results
Sale::insertFromQuery(
    DB::connection('mysql')->table('legacy_sales')->where('year', 2024)
);
```

### 3. Aggregation Methods

Built-in methods optimized for analytics:

```php
// Revenue by time period
$revenue = Sale::aggregateByTime('day', [
    'revenue' => 'SUM(quantity * unit_price * (1 - discount))',
    'orders' => 'COUNT(DISTINCT order_id)',
    'units' => 'SUM(quantity)'
]);

// Cohort analysis
$cohorts = Customer::cohortAnalysis(
    cohortField: 'first_order_date',
    metricField: 'last_order_date',
    interval: 'month'
);

// Distribution analysis
$distribution = Sale::distribution('unit_price', buckets: 10);
```

## Working with Traits

### HasWindowFunctions Trait

Adds support for window functions:

```php
use ConnerOiSu\LaravelDuckDB\Eloquent\Traits\HasWindowFunctions;

class Sale extends AnalyticalModel
{
    use HasWindowFunctions;
}

// Usage
$ranked = Sale::query()
    ->withRowNumber('product_rank', 'product_id', 'revenue DESC')
    ->withRank('overall_rank', orderBy: 'revenue DESC')
    ->withDenseRank('category_rank', 'category_id', 'revenue DESC')
    ->qualify('product_rank <= 3')
    ->get();

// Moving averages
$trend = Sale::query()
    ->withMovingAverage('revenue_ma7', 'revenue', 7, 'created_at')
    ->withMovingSum('revenue_sum7', 'revenue', 7, 'created_at')
    ->get();

// Lag/Lead functions
$comparison = Sale::query()
    ->withLag('prev_revenue', 'revenue', 1, 'created_at')
    ->withLead('next_revenue', 'revenue', 1, 'created_at')
    ->selectRaw('revenue - prev_revenue as revenue_change')
    ->get();
```

### QueriesDataFiles Trait

Query external files directly:

```php
use ConnerOiSu\LaravelDuckDB\Eloquent\Traits\QueriesDataFiles;

class Sale extends AnalyticalModel
{
    use QueriesDataFiles;
}

// Query Parquet files
$historical = Sale::fromParquetFile('s3://data-lake/sales/2023/*.parquet')
    ->where('region', 'North America')
    ->sum('revenue');

// Query CSV files
$imports = Sale::fromCsvFile(storage_path('imports/pending/*.csv'))
    ->whereNull('processed_at')
    ->get();

// Query JSON files
$events = Sale::fromJsonFile('s3://events/2024-01-*.json')
    ->where('event_type', 'purchase')
    ->count();

// Multiple file formats
$combined = Sale::fromFiles([
    's3://data/sales-2023.parquet',
    'local/sales-2024-01.csv',
    'https://api.example.com/sales-latest.json'
])->sum('revenue');
```

### HasTimeSeries Trait

Specialized time-series analysis:

```php
use ConnerOiSu\LaravelDuckDB\Eloquent\Traits\HasTimeSeries;

class Metric extends AnalyticalModel
{
    use HasTimeSeries;
    
    protected $timeColumn = 'recorded_at';
}

// Time-based aggregations
$hourly = Metric::timeSeriesAggregate('hour', [
    'avg_value' => 'AVG(value)',
    'max_value' => 'MAX(value)',
    'min_value' => 'MIN(value)'
]);

// Gap filling
$complete = Metric::fillTimeGaps('day', [
    'value' => 0, // Fill missing days with 0
]);

// Seasonal decomposition
$seasonal = Metric::seasonalDecompose('value', 'month');
```

## Scopes and Query Building

### Analytical Scopes

Create reusable query patterns:

```php
class Sale extends AnalyticalModel
{
    // Time-based scopes
    public function scopeCurrentQuarter($query)
    {
        return $query->whereRaw("created_at >= DATE_TRUNC('quarter', CURRENT_DATE)")
                    ->whereRaw("created_at < DATE_TRUNC('quarter', CURRENT_DATE) + INTERVAL '3 months'");
    }
    
    // Performance scopes
    public function scopeTopPerformers($query, $limit = 10)
    {
        return $query->selectRaw('product_id, SUM(revenue) as total_revenue')
                    ->groupBy('product_id')
                    ->orderByDesc('total_revenue')
                    ->limit($limit);
    }
    
    // Sampling scope
    public function scopeSample($query, $percentage = 1)
    {
        return $query->whereRaw("RANDOM() < ?", [$percentage / 100]);
    }
    
    // Complex analytical scope
    public function scopeWithCustomerMetrics($query)
    {
        return $query->selectRaw('
            customer_id,
            COUNT(DISTINCT order_id) as order_count,
            SUM(revenue) as total_revenue,
            AVG(revenue) as avg_order_value,
            MAX(created_at) as last_order_date,
            DATE_DIFF(\'day\', MAX(created_at), CURRENT_DATE) as days_since_last_order
        ')->groupBy('customer_id');
    }
}
```

### Chainable Methods

Build complex queries fluently:

```php
$analysis = Sale::query()
    ->currentQuarter()
    ->topPerformers(20)
    ->withRank('rank', orderBy: 'total_revenue DESC')
    ->withPercentile('revenue_percentile', 'total_revenue')
    ->having('total_revenue', '>', 10000)
    ->get();
```

## Performance Optimization

### 1. Use Column Selection

```php
// Bad: Selecting all columns
$sales = Sale::all();

// Good: Select only needed columns
$sales = Sale::select(['product_id', 'quantity', 'unit_price'])->get();

// Better: Use aggregations
$summary = Sale::groupBy('product_id')
    ->selectRaw('product_id, SUM(quantity * unit_price) as revenue')
    ->get();
```

### 2. Leverage Partitioning

```php
class Sale extends AnalyticalModel
{
    // Define partition columns
    protected $partitionBy = ['year', 'month'];
    
    // Partition pruning in queries
    public function scopeCurrentYear($query)
    {
        return $query->where('year', date('Y'));
    }
}

// Queries automatically use partition pruning
$currentYearSales = Sale::currentYear()->sum('revenue');
```

### 3. Use Appropriate Indexes

```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->integer('product_id');
    $table->integer('customer_id');
    $table->timestamp('created_at');
    $table->decimal('revenue', 10, 2);
    
    // Indexes for common access patterns
    $table->index(['created_at', 'product_id']); // Time-series by product
    $table->index(['customer_id', 'created_at']); // Customer history
    $table->index(['product_id', 'revenue']); // Top products
});
```

## Model Events and Observers

While Analytical Models support events, use them sparingly:

```php
class SaleObserver
{
    public function creating(Sale $sale)
    {
        // Validation or transformation
    }
    
    public function inserted(array $records)
    {
        // Post-batch-insert processing
        Cache::forget('sales_dashboard_metrics');
    }
}

// Register observer
Sale::observe(SaleObserver::class);
```

## Best Practices

### 1. Design for Analytics

```php
// Instead of normalized transactional model
class Order extends Model
{
    public function items() { return $this->hasMany(OrderItem::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}

// Use denormalized analytical model
class SalesFact extends AnalyticalModel
{
    // Pre-joined, denormalized data optimized for queries
    protected $table = 'sales_facts';
    // Contains: order data + customer data + product data
}
```

### 2. Materialize Complex Calculations

```php
class CustomerLifetimeValue extends AnalyticalModel
{
    // Pre-calculated metrics refreshed periodically
    protected $table = 'customer_ltv';
    
    public static function refresh()
    {
        DB::statement('
            CREATE OR REPLACE TABLE customer_ltv AS
            SELECT 
                customer_id,
                SUM(revenue) as total_revenue,
                COUNT(DISTINCT order_id) as order_count,
                DATEDIFF(\'day\', MIN(order_date), MAX(order_date)) as customer_lifespan,
                -- Complex LTV calculation
                SUM(revenue) * POW(1.1, DATEDIFF(\'year\', MAX(order_date), CURRENT_DATE)) as predicted_ltv
            FROM sales_facts
            GROUP BY customer_id
        ');
    }
}
```

### 3. Use Read Replicas

```php
class AnalyticalReport extends AnalyticalModel
{
    // Always use read-only connection
    protected $connection = 'analytics_read';
    protected $readOnly = true;
    
    // Prevent accidental writes
    public function save(array $options = [])
    {
        throw new \RuntimeException('Cannot write to analytical reports');
    }
}
```

## Migration from Eloquent Models

Transitioning existing models to Analytical Models:

```php
// Before: Traditional Eloquent
class Order extends Model
{
    public function getTotalRevenueAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
    }
}

// After: Analytical Model
class OrderAnalytics extends AnalyticalModel
{
    protected $table = 'orders'; // Same table
    protected $connection = 'analytics'; // DuckDB connection
    
    public function scopeWithRevenue($query)
    {
        return $query->selectRaw('*, 
            (SELECT SUM(quantity * unit_price) 
             FROM order_items 
             WHERE order_id = orders.id) as total_revenue
        ');
    }
}

// Usage remains familiar
$topOrders = OrderAnalytics::withRevenue()
    ->orderByDesc('total_revenue')
    ->limit(100)
    ->get();
```

## Next Steps

- Learn about the [Query Builder](/concepts/query-builder/) for complex analytical queries
- Explore [File Querying](/concepts/file-querying/) capabilities
- Master [Batch Operations](/concepts/batch-operations/) for data loading
- See real-world [Examples](/examples/ecommerce/) of Analytical Models in action