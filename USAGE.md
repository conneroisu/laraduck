# Laravel DuckDB Usage Examples

## Table of Contents

- [Setup](#setup)
- [Basic Queries](#basic-queries)
- [File Operations](#file-operations)
- [Window Functions](#window-functions)
- [Advanced Analytics](#advanced-analytics)
- [Performance Optimization](#performance-optimization)

## Setup

### Model Definition

```php
<?php

namespace App\Models;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;

class Analytics extends AnalyticalModel
{
    protected $connection = 'duckdb';
    protected $table = 'analytics';

    // Allow single-row operations if needed
    protected $allowSingleRowOperations = true;

    // Custom chunk size for batch operations
    protected $bulkInsertChunkSize = 50000;
}
```

### Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Laraduck\EloquentDuckDB\Schema\Blueprint;

class CreateAnalyticsTable extends Migration
{
    public function up()
    {
        Schema::connection('duckdb')->create('analytics', function (Blueprint $table) {
            $table->integer('id');
            $table->string('event_type');
            $table->json('properties');
            $table->decimal128('value', 10);
            $table->timestamp('created_at');

            // DuckDB-specific types
            $table->list('tags', 'varchar');
            $table->struct('metadata', [
                'browser' => 'varchar',
                'ip' => 'varchar',
                'country' => 'varchar'
            ]);
        });
    }
}
```

## Basic Queries

### Standard Eloquent Operations

```php
// Query with conditions
$events = Analytics::where('event_type', 'pageview')
    ->where('value', '>', 100)
    ->orderBy('created_at', 'desc')
    ->limit(100)
    ->get();

// Aggregations
$stats = Analytics::where('event_type', 'purchase')
    ->selectRaw('DATE_TRUNC(\'day\', created_at) as day')
    ->selectRaw('COUNT(*) as count')
    ->selectRaw('SUM(value) as total')
    ->groupBy('day')
    ->get();
```

## File Operations

### Querying Files Directly

```php
// Query Parquet files
$sales = Analytics::fromParquet('/data/sales_2024.parquet')
    ->where('region', 'US')
    ->where('amount', '>', 1000)
    ->get();

// Query multiple Parquet files with glob
$allSales = Analytics::fromParquetGlob('/data/sales_*.parquet')
    ->whereYear('date', 2024)
    ->get();

// Query CSV with auto-detection
$customers = Analytics::fromCsv('/data/customers.csv')
    ->where('country', 'USA')
    ->get();

// Query CSV with options
$transactions = Analytics::fromCsv('/data/transactions.csv', [
    'header' => true,
    'delimiter' => '|',
    'dateformat' => '%Y-%m-%d',
    'columns' => ['id', 'date', 'amount', 'customer_id']
])->get();

// Query JSON files
$logs = Analytics::fromJson('/logs/app.json', [
    'format' => 'array',
    'columns' => ['timestamp', 'level', 'message']
])->get();
```

### Cloud Storage Integration

```php
// S3 Parquet files
$s3Data = Analytics::fromS3Parquet('my-bucket', 'analytics/data.parquet', [
    's3_region' => 'us-east-1',
    's3_access_key_id' => 'key',
    's3_secret_access_key' => 'secret'
])->get();

// HTTP/HTTPS files
$remoteData = Analytics::fromHttp('https://example.com/data.csv', 'csv')
    ->where('status', 'active')
    ->get();

// Google Sheets
$sheetData = Analytics::fromGoogleSheets(
    'https://docs.google.com/spreadsheets/d/SHEET_ID/export?format=csv'
)->get();
```

### Exporting Data

```php
// Export query results to Parquet
Analytics::where('created_at', '>=', '2024-01-01')
    ->toParquet('/exports/analytics_2024.parquet', [
        'compression' => 'snappy',
        'row_group_size' => 100000
    ]);

// Export to partitioned Parquet files
Analytics::query()
    ->exportToParquetPartitioned('/exports/analytics/', ['year', 'month'], [
        'compression' => 'zstd',
        'compression_level' => 3
    ]);

// Export to CSV
Analytics::where('event_type', 'purchase')
    ->toCsv('/exports/purchases.csv', [
        'header' => true,
        'delimiter' => ',',
        'quote' => '"'
    ]);

// Export to JSON Lines
Analytics::query()
    ->exportJsonLines('/exports/analytics.jsonl');
```

## Window Functions

### Ranking Functions

```php
// Row number
$ranked = Analytics::query()
    ->selectRaw('*')
    ->rowNumber('value DESC', 'event_type')
    ->qualify('row_number', '<=', 10)
    ->get();

// Dense rank
$products = Product::query()
    ->selectRaw('*, DENSE_RANK() OVER (ORDER BY sales DESC) as sales_rank')
    ->get();

// Percent rank
$employees = Employee::query()
    ->selectRaw('*, PERCENT_RANK() OVER (PARTITION BY department ORDER BY salary) as salary_percentile')
    ->get();
```

### Analytical Functions

```php
// Lead/Lag
$timeSeries = Analytics::query()
    ->selectRaw('*')
    ->leadLag('value', 1, 0, 'created_at')
    ->selectRaw('value - LAG(value, 1, value) OVER (ORDER BY created_at) as change')
    ->get();

// Running calculations
$cumulative = Sales::query()
    ->selectRaw('*')
    ->runningTotal('amount', 'date')
    ->runningAverage('amount', 'date')
    ->get();

// Moving average
$ma = StockPrice::query()
    ->selectRaw('*')
    ->movingAverage('close_price', 20, 0, 'date', 'symbol')
    ->get();

// First/Last value
$sessions = UserSession::query()
    ->selectRaw('user_id')
    ->firstValue('page', 'timestamp', 'user_id')
    ->lastValue('page', 'timestamp', 'user_id', 'RANGE BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING')
    ->groupBy('user_id')
    ->get();
```

### Complex Window Frames

```php
// Custom window frames
$analysis = Analytics::query()
    ->windowFrame(
        'AVG(value)',
        'category',
        'timestamp',
        '3 PRECEDING',
        '3 FOLLOWING'
    )->get();

// Multiple window functions
$report = Sales::query()
    ->selectRaw('*')
    ->selectRaw('SUM(amount) OVER w as running_total')
    ->selectRaw('AVG(amount) OVER w as running_avg')
    ->selectRaw('COUNT(*) OVER w as running_count')
    ->fromRaw('sales WINDOW w AS (PARTITION BY region ORDER BY date ROWS UNBOUNDED PRECEDING)')
    ->get();
```

## Advanced Analytics

### CTEs (Common Table Expressions)

```php
// Simple CTE
$highValueCustomers = Customer::where('total_purchases', '>', 10000);

$results = DB::connection('duckdb')
    ->query()
    ->withCte('vip_customers', $highValueCustomers)
    ->from('vip_customers')
    ->join('orders', 'vip_customers.id', '=', 'orders.customer_id')
    ->get();

// Recursive CTE
$hierarchy = DB::connection('duckdb')
    ->query()
    ->withRecursiveCte(
        'org_hierarchy',
        Employee::where('manager_id', null)->select('id', 'name', 'manager_id', DB::raw('1 as level')),
        function($query) {
            $query->from('employees as e')
                ->join('org_hierarchy as h', 'e.manager_id', '=', 'h.id')
                ->select('e.id', 'e.name', 'e.manager_id', DB::raw('h.level + 1'));
        }
    )
    ->from('org_hierarchy')
    ->orderBy('level')
    ->get();
```

### QUALIFY Clause

```php
// Top N per group
$topProducts = Product::query()
    ->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY category ORDER BY revenue DESC) as rn')
    ->qualify('rn', '<=', 5)
    ->get();

// Complex qualify conditions
$analysis = Analytics::query()
    ->selectRaw('*, NTILE(4) OVER (ORDER BY value) as quartile')
    ->qualify(function($query) {
        $query->where('quartile', 4)
              ->orWhere('value', '>', 1000);
    })
    ->get();
```

### Pivot/Unpivot

```php
// Pivot data
$pivoted = Sales::query()
    ->pivot(
        ['region'],           // Row groups
        ['product_category'], // Column groups
        ['SUM' => 'amount', 'COUNT' => '*'] // Aggregates
    )->get();

// Unpivot data
$unpivoted = WideTable::query()
    ->unpivot(
        ['jan', 'feb', 'mar', 'apr'], // Columns to unpivot
        'month',  // Name column
        'sales'   // Value column
    )->get();
```

### Sampling

```php
// Bernoulli sampling (row-based)
$sample = LargeTable::query()
    ->sample(0.01, 'BERNOULLI') // 1% sample
    ->get();

// Reservoir sampling (fixed size)
$fixedSample = LargeTable::query()
    ->sample(1000, 'RESERVOIR') // Exactly 1000 rows
    ->get();

// Stratified sampling
$stratified = Customer::query()
    ->selectRaw('*')
    ->rowNumber('RANDOM()', 'segment')
    ->qualify('row_number', '<=', 100) // 100 per segment
    ->get();
```

## Performance Optimization

### Batch Operations

```php
// Efficient batch insert
$data = [];
for ($i = 0; $i < 1000000; $i++) {
    $data[] = [
        'event_type' => 'impression',
        'value' => rand(1, 100),
        'created_at' => now()->subMinutes($i)
    ];
}

Analytics::insertBatch($data, 50000); // Insert in chunks of 50k

// Bulk update using INSERT ... ON CONFLICT
Analytics::upsert(
    $data,
    ['id'], // Unique columns
    ['value', 'updated_at'] // Columns to update
);
```

### Query Optimization

```php
// Column projection
$efficient = Analytics::query()
    ->selectColumns(['id', 'event_type', 'value'])
    ->where('created_at', '>=', now()->subDays(7))
    ->get();

// Predicate pushdown
$filtered = Analytics::fromParquet('/data/large_file.parquet')
    ->where('year', 2024) // Pushed to Parquet reader
    ->where('amount', '>', 100)
    ->selectColumns(['id', 'amount', 'customer_id'])
    ->get();

// Materialized CTEs for better performance
$complex = DB::connection('duckdb')
    ->query()
    ->withCte('expensive_calculation', function($query) {
        $query->from('large_table')
              ->selectRaw('category, APPROX_QUANTILE(value, 0.5) as median')
              ->groupBy('category');
    }, null, true) // materialized = true
    ->from('expensive_calculation')
    ->get();
```

### Memory Management

```php
// Process large datasets in chunks
Analytics::query()
    ->chunkByColumn('id', 10000, function($chunk) {
        // Process chunk
        $chunk->each(function($record) {
            // Process record
        });
    });

// Use summarize for quick statistics
$summary = Analytics::query()
    ->summarize('value', 'created_at')
    ->get();

// Describe table structure
$schema = Analytics::describe();
```

### Working with Time-Series Data

```php
// Time bucketing
$timeSeries = Analytics::query()
    ->selectRaw("DATE_TRUNC('hour', created_at) as hour")
    ->selectRaw('COUNT(*) as events')
    ->selectRaw('AVG(value) as avg_value')
    ->groupBy('hour')
    ->orderBy('hour')
    ->get();

// Gap filling
$continuous = DB::connection('duckdb')
    ->query()
    ->withCte('time_spine', function($query) {
        $query->selectRaw("UNNEST(GENERATE_SERIES(
            TIMESTAMP '2024-01-01',
            TIMESTAMP '2024-12-31',
            INTERVAL '1 day'
        )) as date");
    })
    ->from('time_spine')
    ->leftJoin('daily_stats', 'time_spine.date', '=', 'daily_stats.date')
    ->selectRaw('time_spine.date, COALESCE(daily_stats.value, 0) as value')
    ->get();
```
