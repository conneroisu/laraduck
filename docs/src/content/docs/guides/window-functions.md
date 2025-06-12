---
title: Window Functions
description: Master advanced analytics with DuckDB window functions in Laravel
---

# Window Functions Guide

Window functions are one of the most powerful features for analytical queries, allowing you to perform calculations across sets of rows while maintaining row-level detail. Laravel DuckDB provides full support for DuckDB's extensive window function capabilities.

## Understanding Window Functions

Unlike aggregate functions that collapse rows into groups, window functions operate on a "window" of rows and return a value for each row.

### Basic Anatomy

```sql
function_name() OVER (
    [PARTITION BY column1, column2, ...]
    [ORDER BY column3, column4, ...]
    [frame_clause]
)
```

## Ranking Functions

### ROW_NUMBER()

Assigns unique sequential integers to rows:

```php
use App\Models\Analytics\Sale;

// Number rows by date
$numbered = Sale::query()
    ->select('*')
    ->selectRaw('ROW_NUMBER() OVER (ORDER BY created_at) as row_num')
    ->get();

// Number within partitions
$partitioned = Sale::query()
    ->select('*')
    ->selectRaw('ROW_NUMBER() OVER (
        PARTITION BY product_id 
        ORDER BY created_at DESC
    ) as product_row_num')
    ->qualify('product_row_num = 1') // Most recent sale per product
    ->get();
```

### RANK() and DENSE_RANK()

Assign ranks with different handling of ties:

```php
// RANK() - Gaps after ties
$ranked = Sale::query()
    ->select('product_id', 'revenue')
    ->selectRaw('RANK() OVER (ORDER BY revenue DESC) as revenue_rank')
    ->get();
// Results: 1, 2, 2, 4, 5 (gap after tie)

// DENSE_RANK() - No gaps after ties  
$denseRanked = Sale::query()
    ->select('product_id', 'revenue')
    ->selectRaw('DENSE_RANK() OVER (ORDER BY revenue DESC) as revenue_rank')
    ->get();
// Results: 1, 2, 2, 3, 4 (no gap after tie)

// Ranking within categories
$categoryRanks = Sale::query()
    ->select('category_id', 'product_id', 'revenue')
    ->selectRaw('RANK() OVER (
        PARTITION BY category_id 
        ORDER BY revenue DESC
    ) as category_rank')
    ->qualify('category_rank <= 5') // Top 5 per category
    ->get();
```

### PERCENT_RANK() and CUME_DIST()

Calculate relative positions:

```php
// Percentile ranking (0 to 1)
$percentiles = Sale::query()
    ->select('customer_id', 'total_purchases')
    ->selectRaw('PERCENT_RANK() OVER (
        ORDER BY total_purchases
    ) as purchase_percentile')
    ->qualify('purchase_percentile >= 0.9') // Top 10% of customers
    ->get();

// Cumulative distribution
$distribution = Sale::query()
    ->select('product_id', 'revenue')
    ->selectRaw('CUME_DIST() OVER (
        ORDER BY revenue
    ) as revenue_cume_dist')
    ->get();
```

### NTILE()

Divide rows into buckets:

```php
// Divide customers into quartiles
$quartiles = Customer::query()
    ->select('customer_id', 'lifetime_value')
    ->selectRaw('NTILE(4) OVER (
        ORDER BY lifetime_value DESC
    ) as value_quartile')
    ->get();

// Create deciles for analysis
$deciles = Sale::query()
    ->select('product_id', 'revenue')
    ->selectRaw('NTILE(10) OVER (
        ORDER BY revenue
    ) as revenue_decile')
    ->get();
```

## Aggregate Window Functions

### Running Totals and Averages

```php
// Running total
$runningTotal = Sale::query()
    ->select('date', 'revenue')
    ->selectRaw('SUM(revenue) OVER (
        ORDER BY date
        ROWS UNBOUNDED PRECEDING
    ) as running_total')
    ->get();

// Running average
$runningAvg = Sale::query()
    ->select('date', 'revenue')
    ->selectRaw('AVG(revenue) OVER (
        ORDER BY date
        ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
    ) as moving_avg_7d')
    ->get();

// Partition running totals
$partitionedRunning = Sale::query()
    ->select('category_id', 'date', 'revenue')
    ->selectRaw('SUM(revenue) OVER (
        PARTITION BY category_id
        ORDER BY date
        ROWS UNBOUNDED PRECEDING
    ) as category_running_total')
    ->get();
```

### Moving Windows

```php
// 7-day moving average
$movingMetrics = Sale::query()
    ->selectRaw("DATE_TRUNC('day', created_at) as date")
    ->selectRaw('SUM(revenue) as daily_revenue')
    ->selectRaw('AVG(SUM(revenue)) OVER (
        ORDER BY DATE_TRUNC(\'day\', created_at)
        ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
    ) as revenue_ma7')
    ->selectRaw('MIN(SUM(revenue)) OVER (
        ORDER BY DATE_TRUNC(\'day\', created_at)
        ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
    ) as revenue_min7')
    ->selectRaw('MAX(SUM(revenue)) OVER (
        ORDER BY DATE_TRUNC(\'day\', created_at)
        ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
    ) as revenue_max7')
    ->groupBy('date')
    ->get();

// Centered moving average
$centered = Sale::query()
    ->select('date', 'value')
    ->selectRaw('AVG(value) OVER (
        ORDER BY date
        ROWS BETWEEN 3 PRECEDING AND 3 FOLLOWING
    ) as centered_ma7')
    ->get();
```

## Value Functions

### LAG() and LEAD()

Access values from other rows:

```php
// Previous and next values
$comparison = Sale::query()
    ->selectRaw("DATE_TRUNC('month', created_at) as month")
    ->selectRaw('SUM(revenue) as revenue')
    ->selectRaw('LAG(SUM(revenue), 1) OVER (ORDER BY DATE_TRUNC(\'month\', created_at)) as prev_month')
    ->selectRaw('LEAD(SUM(revenue), 1) OVER (ORDER BY DATE_TRUNC(\'month\', created_at)) as next_month')
    ->groupBy('month')
    ->get();

// Year-over-year comparison
$yoy = Sale::query()
    ->selectRaw("DATE_TRUNC('month', created_at) as month")
    ->selectRaw('SUM(revenue) as revenue')
    ->selectRaw('LAG(SUM(revenue), 12) OVER (ORDER BY DATE_TRUNC(\'month\', created_at)) as revenue_last_year')
    ->selectRaw('(SUM(revenue) - LAG(SUM(revenue), 12) OVER (ORDER BY DATE_TRUNC(\'month\', created_at))) / LAG(SUM(revenue), 12) OVER (ORDER BY DATE_TRUNC(\'month\', created_at)) * 100 as yoy_growth')
    ->groupBy('month')
    ->get();

// With default values
$withDefaults = Sale::query()
    ->select('customer_id', 'order_date', 'amount')
    ->selectRaw('LAG(amount, 1, 0) OVER (
        PARTITION BY customer_id 
        ORDER BY order_date
    ) as prev_order_amount')
    ->get();
```

### FIRST_VALUE() and LAST_VALUE()

Get first/last values in a window:

```php
// First and last values
$firstLast = Sale::query()
    ->select('product_id', 'date', 'price')
    ->selectRaw('FIRST_VALUE(price) OVER (
        PARTITION BY product_id 
        ORDER BY date
        ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
    ) as initial_price')
    ->selectRaw('LAST_VALUE(price) OVER (
        PARTITION BY product_id 
        ORDER BY date
        ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
    ) as latest_price')
    ->get();

// Price changes
$priceChanges = Product::query()
    ->select('product_id', 'date', 'price')
    ->selectRaw('price - FIRST_VALUE(price) OVER (
        PARTITION BY product_id 
        ORDER BY date
    ) as price_change_from_start')
    ->selectRaw('(price / FIRST_VALUE(price) OVER (
        PARTITION BY product_id 
        ORDER BY date
    ) - 1) * 100 as price_change_pct')
    ->get();
```

### NTH_VALUE()

Get the nth value in a window:

```php
// Get median-like values
$nthValues = Sale::query()
    ->select('category_id', 'product_id', 'revenue')
    ->selectRaw('NTH_VALUE(revenue, 2) OVER (
        PARTITION BY category_id 
        ORDER BY revenue DESC
    ) as second_highest_revenue')
    ->selectRaw('NTH_VALUE(revenue, 3) OVER (
        PARTITION BY category_id 
        ORDER BY revenue DESC
    ) as third_highest_revenue')
    ->get();
```

## Frame Specifications

### Row-based Frames

```php
// Fixed number of rows
$fixed = Sale::query()
    ->select('date', 'value')
    ->selectRaw('AVG(value) OVER (
        ORDER BY date
        ROWS BETWEEN 2 PRECEDING AND 2 FOLLOWING
    ) as avg_5_rows')
    ->get();

// All preceding rows
$cumulative = Sale::query()
    ->select('date', 'amount')
    ->selectRaw('SUM(amount) OVER (
        ORDER BY date
        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
    ) as cumulative_sum')
    ->get();

// All rows in partition
$partitionTotal = Sale::query()
    ->select('category', 'product', 'revenue')
    ->selectRaw('SUM(revenue) OVER (
        PARTITION BY category
        ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
    ) as category_total')
    ->selectRaw('revenue::FLOAT / SUM(revenue) OVER (
        PARTITION BY category
        ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
    ) * 100 as pct_of_category')
    ->get();
```

### Range-based Frames

```php
// Time-based windows
$timeWindow = Sale::query()
    ->select('timestamp', 'value')
    ->selectRaw("SUM(value) OVER (
        ORDER BY timestamp
        RANGE BETWEEN INTERVAL '1 hour' PRECEDING AND CURRENT ROW
    ) as sum_last_hour")
    ->get();

// Value-based ranges
$valueRange = Product::query()
    ->select('price', 'product_name')
    ->selectRaw('COUNT(*) OVER (
        ORDER BY price
        RANGE BETWEEN 10 PRECEDING AND 10 FOLLOWING
    ) as products_in_20_dollar_range')
    ->get();
```

## QUALIFY Clause

Filter results based on window functions:

```php
// Top N per group
$topN = Sale::query()
    ->select('*')
    ->selectRaw('RANK() OVER (
        PARTITION BY category_id 
        ORDER BY revenue DESC
    ) as rank')
    ->qualify('rank <= 3')
    ->get();

// Complex qualifying conditions
$qualified = Customer::query()
    ->select('*')
    ->selectRaw('COUNT(*) OVER (PARTITION BY region) as region_customers')
    ->selectRaw('SUM(lifetime_value) OVER (PARTITION BY region) as region_total_value')
    ->selectRaw('lifetime_value / SUM(lifetime_value) OVER (PARTITION BY region) * 100 as pct_of_region')
    ->qualify('pct_of_region > 5') // Customers representing >5% of region value
    ->get();

// Multiple window functions in QUALIFY
$multiQualify = Sale::query()
    ->select('*')
    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY created_at DESC) as rn')
    ->selectRaw('SUM(amount) OVER (PARTITION BY customer_id) as customer_total')
    ->qualify('rn = 1 AND customer_total > 1000') // Latest order from high-value customers
    ->get();
```

## Advanced Patterns

### Gap and Island Detection

```php
// Find consecutive sequences
$sequences = DB::connection('analytics')->select("
    WITH numbered AS (
        SELECT 
            user_id,
            login_date,
            login_date - INTERVAL (ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY login_date)) DAY as grp
        FROM user_logins
    )
    SELECT 
        user_id,
        MIN(login_date) as sequence_start,
        MAX(login_date) as sequence_end,
        COUNT(*) as consecutive_days
    FROM numbered
    GROUP BY user_id, grp
    HAVING COUNT(*) >= 7
");
```

### Sessionization

```php
// Create sessions based on time gaps
$sessions = Event::query()
    ->select('user_id', 'timestamp', 'event_type')
    ->selectRaw("
        SUM(CASE 
            WHEN timestamp - LAG(timestamp) OVER (PARTITION BY user_id ORDER BY timestamp) > INTERVAL '30 minutes'
            THEN 1 
            ELSE 0 
        END) OVER (PARTITION BY user_id ORDER BY timestamp) as session_id
    ")
    ->get();
```

### Funnel Analysis

```php
// Conversion funnel with window functions
$funnel = DB::connection('analytics')->select("
    WITH events_ordered AS (
        SELECT 
            user_id,
            event_type,
            timestamp,
            LEAD(event_type, 1) OVER (PARTITION BY user_id ORDER BY timestamp) as next_event,
            LEAD(timestamp, 1) OVER (PARTITION BY user_id ORDER BY timestamp) as next_timestamp
        FROM user_events
        WHERE event_type IN ('view_product', 'add_to_cart', 'checkout', 'purchase')
    )
    SELECT 
        event_type as from_step,
        next_event as to_step,
        COUNT(DISTINCT user_id) as users,
        AVG(EXTRACT(EPOCH FROM (next_timestamp - timestamp))) as avg_time_seconds
    FROM events_ordered
    WHERE next_event IS NOT NULL
    GROUP BY event_type, next_event
");
```

## Performance Optimization

### Index Strategies

```php
// Create indexes for window function performance
Schema::table('sales', function ($table) {
    // For PARTITION BY customer_id ORDER BY created_at
    $table->index(['customer_id', 'created_at']);
    
    // For PARTITION BY product_id ORDER BY revenue DESC
    $table->index(['product_id', 'revenue']);
});
```

### Materialized Window Results

```php
// Pre-calculate frequently used window results
DB::connection('analytics')->statement("
    CREATE TABLE customer_metrics AS
    SELECT 
        customer_id,
        order_date,
        amount,
        ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY order_date) as order_sequence,
        LAG(order_date) OVER (PARTITION BY customer_id ORDER BY order_date) as prev_order_date,
        SUM(amount) OVER (PARTITION BY customer_id ORDER BY order_date) as running_total
    FROM orders
");

// Query the materialized results
$metrics = DB::connection('analytics')
    ->table('customer_metrics')
    ->where('order_sequence', 1) // First orders
    ->get();
```

## Using with Eloquent Models

### Model Traits

```php
trait HasWindowFunctions
{
    public function scopeWithRank($query, $partitionBy = null, $orderBy = 'created_at')
    {
        $partition = $partitionBy ? "PARTITION BY {$partitionBy}" : '';
        return $query->selectRaw("*, RANK() OVER ({$partition} ORDER BY {$orderBy}) as rank");
    }
    
    public function scopeWithRunningTotal($query, $column, $partitionBy = null)
    {
        $partition = $partitionBy ? "PARTITION BY {$partitionBy}" : '';
        return $query->selectRaw("*, SUM({$column}) OVER ({$partition} ORDER BY created_at) as running_{$column}");
    }
    
    public function scopeWithMovingAverage($query, $column, $window = 7)
    {
        return $query->selectRaw("*, AVG({$column}) OVER (ORDER BY created_at ROWS BETWEEN {$window} - 1 PRECEDING AND CURRENT ROW) as {$column}_ma{$window}");
    }
}
```

### Usage in Models

```php
class Sale extends AnalyticalModel
{
    use HasWindowFunctions;
    
    public function scopeTopPerformers($query)
    {
        return $query
            ->withRank('product_id', 'revenue DESC')
            ->qualify('rank <= 10');
    }
    
    public function scopeWithGrowthMetrics($query)
    {
        return $query
            ->selectRaw('*, LAG(revenue, 1) OVER (PARTITION BY product_id ORDER BY month) as prev_revenue')
            ->selectRaw('(revenue - LAG(revenue, 1) OVER (PARTITION BY product_id ORDER BY month)) / LAG(revenue, 1) OVER (PARTITION BY product_id ORDER BY month) * 100 as growth_rate');
    }
}

// Usage
$topProducts = Sale::topPerformers()->get();
$growthAnalysis = Sale::withGrowthMetrics()->get();
```

## Common Pitfalls and Solutions

### 1. Frame Clause Confusion

```php
// ❌ Wrong: LAST_VALUE without proper frame
$wrong = Sale::query()
    ->selectRaw('LAST_VALUE(price) OVER (ORDER BY date) as last_price')
    ->get(); // Only considers rows up to current row

// ✅ Correct: Specify full frame
$correct = Sale::query()
    ->selectRaw('LAST_VALUE(price) OVER (
        ORDER BY date 
        ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
    ) as last_price')
    ->get();
```

### 2. Partition Performance

```php
// ❌ Inefficient: Large partitions
$slow = Sale::query()
    ->selectRaw('*, ROW_NUMBER() OVER (ORDER BY created_at) as rn')
    ->get(); // Single partition for entire table

// ✅ Efficient: Smaller partitions
$fast = Sale::query()
    ->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY DATE_TRUNC(\'day\', created_at) ORDER BY created_at) as daily_rn')
    ->get();
```

## Next Steps

- Explore [Time Series Analysis](/guides/time-series) using window functions
- Learn about [Performance Optimization](/guides/performance) for complex queries
- See [Advanced SQL Features](/reference/query-grammar) in Laravel DuckDB
- Check [Real-world Examples](/examples/ecommerce) using window functions