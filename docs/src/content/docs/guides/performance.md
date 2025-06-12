---
title: Performance Optimization
description: Optimize Laravel DuckDB queries for maximum performance
---

# Performance Optimization Guide

This guide covers techniques to maximize the performance of your Laravel DuckDB queries, from query optimization to system configuration.

## Performance Benchmarks

DuckDB demonstrates exceptional performance for analytical workloads:

| Operation | Performance | Details |
|-----------|-------------|----------|
| **Bulk Insert** | 83,100 rows/sec | 100k rows in 1.2 seconds |
| **Aggregations** | 32.8ms | GROUP BY on 110k rows |
| **Window Functions** | 45.2ms | Complex analytical queries |
| **Parquet Write** | 469,000 rows/sec | Columnar format export |
| **Parquet Read** | 62,900 rows/sec | Direct file querying |

### Database Comparison

For analytical queries on 110k rows:
- **DuckDB**: 32.8ms (baseline)
- **PostgreSQL**: 89.1ms (2.7x slower)
- **MySQL**: 156.3ms (4.8x slower)
- **SQLite**: 234.6ms (7.2x slower)

See [detailed benchmarks](/BENCHMARKS) for complete performance metrics.

## Query Performance Basics

### Understanding DuckDB's Architecture

DuckDB is optimized for analytical workloads through:
- **Columnar Storage**: Data stored by column for better compression and scan performance
- **Vectorized Execution**: Processes data in batches for CPU efficiency
- **Parallel Processing**: Utilizes all available CPU cores
- **Smart Query Optimizer**: Automatically optimizes query plans

### Query Profiling

```php
// Enable query profiling
DB::connection('analytics')->enableQueryLog();

// Run your query
$results = Sale::query()
    ->whereYear('created_at', 2024)
    ->groupBy('product_id')
    ->sum('revenue');

// Get query log with timing
$queries = DB::connection('analytics')->getQueryLog();
foreach ($queries as $query) {
    Log::info('Query executed', [
        'sql' => $query['query'],
        'bindings' => $query['bindings'],
        'time' => $query['time'] . 'ms'
    ]);
}

// Explain query plan
$plan = Sale::query()
    ->whereYear('created_at', 2024)
    ->groupBy('product_id')
    ->explain();

// Analyze query execution
$analysis = Sale::query()
    ->whereYear('created_at', 2024)
    ->groupBy('product_id')
    ->explainAnalyze();
```

## Query Optimization Techniques

### 1. Column Selection

```php
// ❌ Bad: Selecting all columns
$sales = Sale::all(); // Reads entire table

// ✅ Good: Select only needed columns
$sales = Sale::select(['product_id', 'quantity', 'price'])->get();

// ✅ Better: Use aggregations
$summary = Sale::query()
    ->groupBy('product_id')
    ->selectRaw('product_id, SUM(quantity * price) as revenue')
    ->get();
```

### 2. Filter Pushdown

```php
// ❌ Bad: Filter after loading
$sales = Sale::all()->filter(fn($sale) => $sale->created_at->year === 2024);

// ✅ Good: Filter at database level
$sales = Sale::whereYear('created_at', 2024)->get();

// ✅ Better: Use indexes for filtering
Schema::table('sales', function ($table) {
    $table->index(['created_at', 'product_id']);
});

$sales = Sale::query()
    ->whereYear('created_at', 2024)
    ->where('product_id', 123)
    ->get();
```

### 3. Aggregation Optimization

```php
// ❌ Bad: Multiple queries
$totalRevenue = Sale::sum('revenue');
$avgRevenue = Sale::avg('revenue');
$maxRevenue = Sale::max('revenue');

// ✅ Good: Single query with multiple aggregations
$stats = Sale::query()
    ->selectRaw('
        SUM(revenue) as total_revenue,
        AVG(revenue) as avg_revenue,
        MAX(revenue) as max_revenue,
        MIN(revenue) as min_revenue,
        COUNT(*) as count,
        STDDEV(revenue) as stddev_revenue
    ')
    ->first();

// ✅ Better: Grouped aggregations
$productStats = Sale::query()
    ->groupBy('product_id')
    ->selectRaw('
        product_id,
        SUM(revenue) as total_revenue,
        COUNT(*) as sales_count,
        AVG(revenue) as avg_revenue
    ')
    ->having('sales_count', '>', 100)
    ->orderByDesc('total_revenue')
    ->limit(20)
    ->get();
```

### 4. Join Optimization

```php
// ❌ Bad: N+1 queries
$products = Product::all();
foreach ($products as $product) {
    $product->total_sales = Sale::where('product_id', $product->id)->sum('revenue');
}

// ✅ Good: Single query with join
$products = Product::query()
    ->leftJoin('sales', 'products.id', '=', 'sales.product_id')
    ->groupBy('products.id')
    ->select('products.*')
    ->selectRaw('COALESCE(SUM(sales.revenue), 0) as total_sales')
    ->get();

// ✅ Better: Use subquery for complex aggregations
$products = Product::query()
    ->selectSub(
        Sale::query()
            ->whereColumn('product_id', 'products.id')
            ->selectRaw('SUM(revenue)'),
        'total_sales'
    )
    ->get();
```

## Index Strategies

### Creating Effective Indexes

```php
Schema::table('sales', function ($table) {
    // Single column index
    $table->index('created_at');
    
    // Composite index for common queries
    $table->index(['created_at', 'product_id']);
    
    // Index for sorting
    $table->index(['product_id', 'revenue']);
    
    // Partial index (DuckDB supports this)
    DB::statement('
        CREATE INDEX idx_high_value_sales 
        ON sales(customer_id, created_at) 
        WHERE revenue > 1000
    ');
});
```

### Index Usage Analysis

```php
// Check if indexes are being used
$plan = DB::connection('analytics')
    ->table('sales')
    ->where('created_at', '>=', '2024-01-01')
    ->where('product_id', 123)
    ->explain();

// Force index usage (if needed)
$results = DB::connection('analytics')
    ->table('sales')
    ->from(DB::raw('sales /*+ INDEX(idx_created_product) */'))
    ->where('created_at', '>=', '2024-01-01')
    ->get();
```

## Partitioning Strategies

### Table Partitioning

```php
// Create partitioned table
DB::connection('analytics')->statement("
    CREATE TABLE sales_partitioned (
        id INTEGER,
        product_id INTEGER,
        customer_id INTEGER,
        revenue DECIMAL(10,2),
        created_at TIMESTAMP,
        year INTEGER,
        month INTEGER
    ) PARTITION BY (year, month)
");

// Query specific partitions
$recentSales = DB::connection('analytics')
    ->table('sales_partitioned')
    ->where('year', 2024)
    ->where('month', 6)
    ->sum('revenue');
```

### Dynamic Partitioning for Exports

```php
// Export data with partitioning
Sale::query()
    ->whereYear('created_at', 2024)
    ->toParquetFile('s3://data-lake/sales/', [
        'partition_by' => ['year', 'month', 'region'],
        'overwrite' => true
    ]);

// Query partitioned data efficiently
$regionalSales = Sale::fromParquetFile(
    's3://data-lake/sales/year=2024/month=06/region=US/*.parquet'
)->sum('revenue');
```

## Memory Optimization

### Configure Memory Limits

```php
// Set memory limit for connection
DB::connection('analytics')->statement("SET memory_limit = '8GB'");

// Configure in database.php
'analytics' => [
    'driver' => 'duckdb',
    'memory_limit' => env('DUCKDB_MEMORY_LIMIT', '8GB'),
    'temp_directory' => env('DUCKDB_TEMP_DIR', '/mnt/fast-ssd/duckdb-temp'),
];
```

### Memory-Efficient Queries

```php
// ❌ Bad: Loading everything into memory
$allSales = Sale::all();
$grouped = $allSales->groupBy('product_id');

// ✅ Good: Let database handle grouping
$grouped = Sale::query()
    ->groupBy('product_id')
    ->selectRaw('product_id, COUNT(*) as count, SUM(revenue) as total')
    ->get();

// ✅ Better: Process in chunks for huge datasets
Sale::query()
    ->whereYear('created_at', 2024)
    ->chunk(10000, function ($sales) {
        // Process chunk
        ProcessSalesChunk::dispatch($sales);
    });
```

## Parallel Processing

### Configure Thread Usage

```php
// Set thread count
DB::connection('analytics')->statement("SET threads = 16");

// Auto-detect optimal threads
DB::connection('analytics')->statement("SET threads = 0"); // 0 = auto

// Configure per connection
'analytics' => [
    'driver' => 'duckdb',
    'threads' => env('DUCKDB_THREADS', null), // null = auto-detect
];
```

### Parallel Query Patterns

```php
// Parallel aggregation
$results = Sale::query()
    ->selectRaw("
        DATE_TRUNC('day', created_at) as date,
        COUNT(*) as orders,
        SUM(revenue) as revenue,
        COUNT(DISTINCT customer_id) as unique_customers
    ")
    ->groupBy('date')
    ->orderBy('date')
    ->get();

// Parallel file processing
$files = [
    's3://data/sales_2024_q1.parquet',
    's3://data/sales_2024_q2.parquet',
    's3://data/sales_2024_q3.parquet',
    's3://data/sales_2024_q4.parquet',
];

$totalRevenue = collect($files)->map(function ($file) {
    return Sale::fromParquetFile($file)->sum('revenue');
})->sum();
```

## Caching Strategies

### Query Result Caching

```php
// Cache expensive queries
$topProducts = Cache::remember('top_products_2024', 3600, function () {
    return Sale::query()
        ->whereYear('created_at', 2024)
        ->groupBy('product_id')
        ->selectRaw('product_id, SUM(revenue) as total_revenue')
        ->orderByDesc('total_revenue')
        ->limit(100)
        ->get();
});

// Tagged caching for invalidation
$monthlyStats = Cache::tags(['sales', 'monthly'])->remember(
    'sales_stats_' . now()->format('Y-m'),
    86400,
    function () {
        return Sale::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('
                COUNT(*) as order_count,
                SUM(revenue) as total_revenue,
                AVG(revenue) as avg_revenue
            ')
            ->first();
    }
);

// Invalidate when data changes
Cache::tags(['sales'])->flush();
```

### Materialized Views

```php
// Create materialized view
DB::connection('analytics')->statement("
    CREATE TABLE sales_daily_summary AS
    SELECT 
        DATE_TRUNC('day', created_at) as date,
        product_id,
        COUNT(*) as order_count,
        SUM(revenue) as total_revenue,
        AVG(revenue) as avg_revenue
    FROM sales
    GROUP BY date, product_id
");

// Refresh materialized view
Artisan::command('analytics:refresh-daily-summary', function () {
    DB::connection('analytics')->statement("
        CREATE OR REPLACE TABLE sales_daily_summary AS
        SELECT 
            DATE_TRUNC('day', created_at) as date,
            product_id,
            COUNT(*) as order_count,
            SUM(revenue) as total_revenue,
            AVG(revenue) as avg_revenue
        FROM sales
        WHERE created_at >= CURRENT_DATE - INTERVAL '90 days'
        GROUP BY date, product_id
    ");
});

// Schedule refresh
Schedule::command('analytics:refresh-daily-summary')->daily();
```

## File Format Optimization

### Parquet Optimization

```php
// Optimize Parquet files for queries
Sale::query()
    ->orderBy('created_at') // Sort for better compression
    ->orderBy('product_id') // Secondary sort for common filters
    ->toParquetFile('/exports/sales_optimized.parquet', [
        'compression' => 'snappy', // Fast compression
        'row_group_size' => 100000, // Optimize for your query patterns
        'statistics' => true // Enable column statistics
    ]);

// Use column statistics for filtering
$filtered = Sale::fromParquetFile('/exports/sales_optimized.parquet')
    ->where('created_at', '>=', '2024-06-01') // Uses min/max statistics
    ->where('product_id', 123) // Uses column statistics
    ->get();
```

### CSV Optimization

```php
// Optimize CSV for loading
Sale::query()
    ->toCsvFile('/exports/sales.csv', [
        'delimiter' => '|', // Avoid delimiter conflicts
        'compression' => 'gzip', // Compress large files
        'parallel' => true // Parallel CSV writing
    ]);

// Fast CSV loading
Sale::copyFrom('/imports/sales.csv.gz', [
    'format' => 'csv',
    'compression' => 'gzip',
    'parallel' => true,
    'sample_size' => 20000 // Larger sample for schema detection
]);
```

## System Configuration

### DuckDB Settings

```php
// Optimal settings for different workloads
class DuckDBConfigurator
{
    public static function configureForOLAP()
    {
        $connection = DB::connection('analytics');
        
        // Memory settings
        $connection->statement("SET memory_limit = '16GB'");
        $connection->statement("SET temp_directory = '/mnt/nvme/duckdb-temp'");
        
        // Performance settings
        $connection->statement("SET threads = 0"); // Auto-detect
        $connection->statement("SET enable_progress_bar = false");
        $connection->statement("SET enable_profiling = false");
        
        // Query optimization
        $connection->statement("SET optimizer_enable_cse = true");
        $connection->statement("SET enable_optimizer = true");
        $connection->statement("SET force_parallelism = true");
    }
    
    public static function configureForETL()
    {
        $connection = DB::connection('analytics');
        
        // Optimize for bulk operations
        $connection->statement("SET checkpoint_threshold = '1GB'");
        $connection->statement("SET wal_autocheckpoint = '1GB'");
        $connection->statement("SET preserve_insertion_order = false");
        $connection->statement("SET threads = 16");
    }
}
```

### Hardware Considerations

```php
// Detect and optimize for hardware
$cpuCount = swoole_cpu_num() ?? 8;
$memory = $this->getAvailableMemory();

config([
    'database.connections.analytics.threads' => $cpuCount,
    'database.connections.analytics.memory_limit' => $memory * 0.6 . 'GB',
    'database.connections.analytics.temp_directory' => $this->getFastestDisk(),
]);
```

## Monitoring and Alerting

### Performance Monitoring

```php
class QueryPerformanceMonitor
{
    public function monitor($query, $threshold = 1000)
    {
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        $result = $query();
        
        $duration = (microtime(true) - $start) * 1000;
        $memoryUsed = memory_get_usage() - $startMemory;
        
        if ($duration > $threshold) {
            Log::warning('Slow query detected', [
                'duration_ms' => $duration,
                'memory_mb' => $memoryUsed / 1024 / 1024,
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);
            
            // Send alert
            SlowQueryAlert::dispatch($query, $duration);
        }
        
        return $result;
    }
}

// Usage
$monitor = new QueryPerformanceMonitor();
$results = $monitor->monitor(
    fn() => Sale::query()
        ->whereYear('created_at', 2024)
        ->groupBy('product_id')
        ->sum('revenue'),
    threshold: 500 // Alert if > 500ms
);
```

### Resource Usage Tracking

```php
// Track DuckDB resource usage
DB::listen(function ($query) {
    if ($query->connection === 'analytics') {
        $stats = DB::connection('analytics')
            ->select("SELECT * FROM duckdb_memory()")[0];
        
        Log::info('DuckDB query executed', [
            'sql' => $query->sql,
            'time' => $query->time,
            'memory_usage_mb' => $stats->memory_usage_bytes / 1024 / 1024,
            'memory_limit_mb' => $stats->memory_limit_bytes / 1024 / 1024,
        ]);
    }
});
```

## Best Practices Summary

### Do's
- ✅ Select only needed columns
- ✅ Push filters to the database
- ✅ Use appropriate indexes
- ✅ Leverage parallel processing
- ✅ Cache expensive queries
- ✅ Monitor query performance
- ✅ Use columnar formats (Parquet) for analytics
- ✅ Batch operations for bulk data

### Don'ts
- ❌ Select all columns unnecessarily
- ❌ Filter data in PHP
- ❌ Use row-by-row operations for bulk data
- ❌ Ignore query plans
- ❌ Over-index tables
- ❌ Load entire datasets into memory

## Next Steps

- Explore [Window Functions](/guides/window-functions) for complex analytics
- Learn about [Import/Export](/guides/import-export) optimization
- Check [Time Series Analysis](/guides/time-series) techniques
- See [Real-world Examples](/examples/ecommerce) of optimized queries