---
title: Query Builder
description: Master DuckDB's advanced SQL features through Laravel's query builder
---

# Query Builder

Laravel DuckDB extends Laravel's query builder with powerful analytical SQL features while maintaining the familiar, fluent interface you love.

## DuckDB-Specific Features

### Window Functions

Window functions perform calculations across a set of rows while maintaining individual row details:

```php
use Illuminate\Support\Facades\DB;

// Basic window function
$ranked = DB::connection('analytics')
    ->table('sales')
    ->select('product_id', 'revenue')
    ->selectRaw('RANK() OVER (ORDER BY revenue DESC) as revenue_rank')
    ->get();

// Partitioned window function
$categoryRanked = DB::connection('analytics')
    ->table('products')
    ->select('category_id', 'product_id', 'revenue')
    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY revenue DESC) as category_rank')
    ->get();

// Multiple window functions
$analytics = DB::connection('analytics')
    ->table('sales')
    ->select('date', 'revenue')
    ->selectRaw('SUM(revenue) OVER (ORDER BY date ROWS BETWEEN 6 PRECEDING AND CURRENT ROW) as revenue_7d')
    ->selectRaw('AVG(revenue) OVER (ORDER BY date ROWS BETWEEN 29 PRECEDING AND CURRENT ROW) as revenue_30d_avg')
    ->selectRaw('LAG(revenue, 7) OVER (ORDER BY date) as revenue_week_ago')
    ->get();
```

### QUALIFY Clause

QUALIFY filters the results of window functions (like HAVING for GROUP BY):

```php
// Get top 3 products per category
$topProducts = DB::connection('analytics')
    ->table('products')
    ->select('*')
    ->selectRaw('RANK() OVER (PARTITION BY category_id ORDER BY revenue DESC) as rank')
    ->qualify('rank <= 3')
    ->get();

// Get customers with above-average orders
$valuableCustomers = DB::connection('analytics')
    ->table('customers')
    ->select('*')
    ->selectRaw('total_orders')
    ->selectRaw('AVG(total_orders) OVER () as avg_orders')
    ->qualify('total_orders > avg_orders')
    ->get();

// Combine with WHERE and HAVING
$complex = DB::connection('analytics')
    ->table('sales')
    ->select('product_id')
    ->selectRaw('SUM(revenue) as total_revenue')
    ->selectRaw('PERCENT_RANK() OVER (ORDER BY SUM(revenue)) as revenue_percentile')
    ->where('date', '>=', '2024-01-01')
    ->groupBy('product_id')
    ->having('total_revenue', '>', 1000)
    ->qualify('revenue_percentile >= 0.9') // Top 10%
    ->get();
```

### Common Table Expressions (CTEs)

CTEs make complex queries more readable and enable recursive queries:

```php
// Basic CTE
$monthlySales = DB::connection('analytics')
    ->withExpression('monthly_sales', function ($query) {
        $query->from('sales')
            ->selectRaw("DATE_TRUNC('month', date) as month")
            ->selectRaw('SUM(revenue) as revenue')
            ->groupBy('month');
    })
    ->table('monthly_sales')
    ->select('*')
    ->get();

// Multiple CTEs
$analysis = DB::connection('analytics')
    ->withExpression('customer_metrics', function ($query) {
        $query->from('orders')
            ->select('customer_id')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(total) as lifetime_value')
            ->groupBy('customer_id');
    })
    ->withExpression('customer_segments', function ($query) {
        $query->from('customer_metrics')
            ->select('*')
            ->selectRaw("CASE 
                WHEN lifetime_value > 10000 THEN 'VIP'
                WHEN lifetime_value > 1000 THEN 'Regular'
                ELSE 'New'
            END as segment");
    })
    ->table('customer_segments')
    ->select('segment')
    ->selectRaw('COUNT(*) as customer_count')
    ->selectRaw('AVG(lifetime_value) as avg_ltv')
    ->groupBy('segment')
    ->get();

// Recursive CTE for hierarchical data
$hierarchy = DB::connection('analytics')
    ->withRecursiveExpression('category_tree', function ($query) {
        // Anchor member
        $query->from('categories')
            ->where('parent_id', null)
            ->select('id', 'name', 'parent_id')
            ->selectRaw('0 as level')
            ->selectRaw('name as path');
    }, function ($query) {
        // Recursive member
        $query->from('categories as c')
            ->join('category_tree as ct', 'c.parent_id', '=', 'ct.id')
            ->select('c.id', 'c.name', 'c.parent_id')
            ->selectRaw('ct.level + 1')
            ->selectRaw("ct.path || ' > ' || c.name");
    })
    ->table('category_tree')
    ->orderBy('path')
    ->get();
```

### GROUP BY ALL

Automatically group by all non-aggregate columns:

```php
// Traditional approach - must list all columns
$summary = DB::connection('analytics')
    ->table('sales')
    ->select('year', 'quarter', 'month', 'product_category', 'region')
    ->selectRaw('SUM(revenue) as total_revenue')
    ->selectRaw('COUNT(*) as transaction_count')
    ->groupBy('year', 'quarter', 'month', 'product_category', 'region')
    ->get();

// DuckDB approach - GROUP BY ALL
$summary = DB::connection('analytics')
    ->table('sales')
    ->select('year', 'quarter', 'month', 'product_category', 'region')
    ->selectRaw('SUM(revenue) as total_revenue')
    ->selectRaw('COUNT(*) as transaction_count')
    ->groupByAll()
    ->get();
```

### SAMPLE and TABLESAMPLE

Efficiently sample large datasets:

```php
// Random sample of rows
$sample = DB::connection('analytics')
    ->table('events')
    ->sample(1000) // Get 1000 random rows
    ->get();

// Percentage-based sampling
$sample = DB::connection('analytics')
    ->table('large_dataset')
    ->samplePercent(0.1) // Sample 0.1% of rows
    ->get();

// Bernoulli sampling (row-level)
$sample = DB::connection('analytics')
    ->table('users')
    ->tableSample('BERNOULLI', 10) // Each row has 10% chance
    ->get();

// System sampling (block-level, faster)
$sample = DB::connection('analytics')
    ->table('huge_table')
    ->tableSample('SYSTEM', 1) // Sample ~1% of blocks
    ->get();

// Repeatable sampling with seed
$sample = DB::connection('analytics')
    ->table('transactions')
    ->sample(1000, seed: 42) // Same sample every time
    ->get();
```

## Advanced Aggregations

### Aggregate Functions

DuckDB supports advanced aggregate functions:

```php
// Statistical aggregates
$stats = DB::connection('analytics')
    ->table('measurements')
    ->selectRaw('
        AVG(value) as mean,
        MEDIAN(value) as median,
        MODE(value) as mode,
        STDDEV(value) as std_dev,
        VARIANCE(value) as variance,
        QUANTILE(value, 0.25) as q1,
        QUANTILE(value, 0.75) as q3,
        MAD(value) as median_absolute_deviation
    ')
    ->first();

// Approximate aggregates for large datasets
$approx = DB::connection('analytics')
    ->table('huge_table')
    ->selectRaw('
        APPROX_DISTINCT(user_id) as unique_users,
        APPROX_QUANTILE(revenue, 0.5) as median_revenue,
        APPROX_TOP_K(product_id, 10) as top_products
    ')
    ->first();

// List aggregates
$grouped = DB::connection('analytics')
    ->table('orders')
    ->select('customer_id')
    ->selectRaw('LIST(order_id) as order_ids')
    ->selectRaw('LIST(order_date ORDER BY order_date) as order_dates')
    ->selectRaw('LIST_DISTINCT(product_category) as categories_purchased')
    ->groupBy('customer_id')
    ->get();
```

### Grouping Sets, Rollup, and Cube

Create multiple grouping levels in one query:

```php
// GROUPING SETS
$multiLevel = DB::connection('analytics')
    ->table('sales')
    ->selectRaw('year, month, region, SUM(revenue) as revenue')
    ->groupBySets([
        ['year', 'month'],
        ['year', 'region'],
        ['region'],
        [] // Grand total
    ])
    ->get();

// ROLLUP - hierarchical grouping
$rollup = DB::connection('analytics')
    ->table('sales')
    ->selectRaw('year, quarter, month, SUM(revenue) as revenue')
    ->groupByRollup(['year', 'quarter', 'month'])
    ->get();

// CUBE - all combinations
$cube = DB::connection('analytics')
    ->table('sales')
    ->selectRaw('category, subcategory, brand, SUM(revenue) as revenue')
    ->groupByCube(['category', 'subcategory', 'brand'])
    ->get();

// Identify grouping levels
$detailed = DB::connection('analytics')
    ->table('sales')
    ->selectRaw('
        year,
        month,
        SUM(revenue) as revenue,
        GROUPING(year) as is_year_total,
        GROUPING(month) as is_month_total
    ')
    ->groupByRollup(['year', 'month'])
    ->get();
```

## Pivot and Unpivot

Transform data between wide and long formats:

```php
// PIVOT - long to wide
$pivoted = DB::connection('analytics')
    ->table('sales')
    ->pivot(
        'SUM(revenue)',
        'month',
        ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
    )
    ->groupBy('product_id')
    ->get();

// Dynamic PIVOT
$months = DB::connection('analytics')
    ->table('sales')
    ->distinct()
    ->pluck('month');

$pivoted = DB::connection('analytics')
    ->table('sales')
    ->pivot('SUM(revenue)', 'month', $months)
    ->groupBy('product_id')
    ->get();

// UNPIVOT - wide to long
$unpivoted = DB::connection('analytics')
    ->table('monthly_metrics')
    ->unpivot(
        'revenue',
        'month',
        ['jan_revenue', 'feb_revenue', 'mar_revenue']
    )
    ->get();
```

## Array and List Operations

Work with array/list columns:

```php
// Array operations
$arrayOps = DB::connection('analytics')
    ->table('user_events')
    ->select('user_id')
    ->selectRaw("LIST(event_type) as all_events")
    ->selectRaw("LIST_DISTINCT(event_type) as unique_events")
    ->selectRaw("LIST_COUNT(event_type) as event_count")
    ->selectRaw("LIST_CONTAINS(LIST(event_type), 'purchase') as has_purchased")
    ->groupBy('user_id')
    ->get();

// Array aggregates
$metrics = DB::connection('analytics')
    ->table('product_reviews')
    ->select('product_id')
    ->selectRaw("LIST(rating ORDER BY created_at DESC)[1:5] as recent_ratings")
    ->selectRaw("LIST_AVG(rating) as avg_rating")
    ->selectRaw("LIST_FILTER(rating, x -> x >= 4) as positive_ratings")
    ->groupBy('product_id')
    ->get();

// Unnest arrays
$expanded = DB::connection('analytics')
    ->table('user_tags')
    ->crossJoinUnnest('tags', 'tag')
    ->select('user_id', 'tag')
    ->selectRaw('COUNT(*) OVER (PARTITION BY tag) as tag_popularity')
    ->get();
```

## Date/Time Functions

Advanced temporal operations:

```php
// Date truncation and extraction
$timeSeries = DB::connection('analytics')
    ->table('events')
    ->selectRaw("DATE_TRUNC('hour', created_at) as hour")
    ->selectRaw("EXTRACT(dow FROM created_at) as day_of_week")
    ->selectRaw("EXTRACT(week FROM created_at) as week_number")
    ->selectRaw("COUNT(*) as event_count")
    ->groupBy('hour', 'day_of_week', 'week_number')
    ->get();

// Date arithmetic
$cohorts = DB::connection('analytics')
    ->table('users')
    ->selectRaw("DATE_TRUNC('month', created_at) as cohort_month")
    ->selectRaw("DATE_DIFF('month', created_at, last_active_at) as months_active")
    ->selectRaw("COUNT(*) as user_count")
    ->groupBy('cohort_month', 'months_active')
    ->get();

// Generate date series
$dateSeries = DB::connection('analytics')
    ->tableRaw("generate_series(
        DATE '2024-01-01',
        DATE '2024-12-31',
        INTERVAL '1 day'
    ) as date_series(date)")
    ->leftJoin('sales', 'sales.date', '=', 'date_series.date')
    ->select('date_series.date')
    ->selectRaw('COALESCE(SUM(sales.revenue), 0) as revenue')
    ->groupBy('date_series.date')
    ->get();
```

## Query Optimization

### Query Plans

Analyze query performance:

```php
// Get query plan
$plan = DB::connection('analytics')
    ->table('large_table')
    ->where('status', 'active')
    ->explain();

// Analyze query execution
$analysis = DB::connection('analytics')
    ->table('complex_view')
    ->where('date', '>=', '2024-01-01')
    ->explainAnalyze();

// Profiling information
DB::connection('analytics')->enableQueryLog();

$results = DB::connection('analytics')
    ->table('sales')
    ->select('*')
    ->get();

$queries = DB::connection('analytics')->getQueryLog();
```

### Query Hints

Optimize query execution:

```php
// Force specific join order
$optimized = DB::connection('analytics')
    ->table('fact_sales')
    ->forceIndex('idx_date_product')
    ->join('dim_product', 'fact_sales.product_id', '=', 'dim_product.id')
    ->where('date', '>=', '2024-01-01')
    ->get();

// Parallel execution hints
$parallel = DB::connection('analytics')
    ->table('huge_table')
    ->hint('SET threads TO 16')
    ->whereIn('category', ['A', 'B', 'C'])
    ->get();
```

## Extending the Query Builder

### Custom Macros

Add your own query builder methods:

```php
// In a service provider
use Illuminate\Database\Query\Builder;

Builder::macro('withRunningTotal', function ($column) {
    return $this->selectRaw("SUM({$column}) OVER (ORDER BY created_at) as running_total");
});

Builder::macro('withYearOverYear', function ($column) {
    return $this->selectRaw("
        {$column} as current_value,
        LAG({$column}, 12) OVER (ORDER BY month) as previous_year,
        ({$column} - LAG({$column}, 12) OVER (ORDER BY month)) / LAG({$column}, 12) OVER (ORDER BY month) * 100 as yoy_growth
    ");
});

// Usage
$sales = DB::connection('analytics')
    ->table('monthly_sales')
    ->withRunningTotal('revenue')
    ->withYearOverYear('revenue')
    ->get();
```

### Custom Grammar

Extend DuckDB grammar for specific needs:

```php
namespace App\Database\Query\Grammars;

use ConnerOiSu\LaravelDuckDB\Query\Grammars\DuckDBGrammar;

class CustomDuckDBGrammar extends DuckDBGrammar
{
    // Add custom SQL compilation
    public function compileMedian($query, $column)
    {
        return "MEDIAN({$this->wrap($column)}) as {$this->wrap($column . '_median')}";
    }
    
    // Override existing methods
    protected function compileQualify($query, $expression)
    {
        // Custom QUALIFY handling
        return 'QUALIFY ' . $expression;
    }
}
```

## Best Practices

### 1. Use CTEs for Complex Queries

```php
// Instead of nested subqueries
$bad = DB::raw("
    SELECT * FROM (
        SELECT * FROM (
            SELECT * FROM sales WHERE year = 2024
        ) WHERE revenue > 1000
    ) WHERE region = 'North'
");

// Use CTEs
$good = DB::connection('analytics')
    ->withExpression('filtered_sales', function ($q) {
        $q->from('sales')->where('year', 2024);
    })
    ->withExpression('high_revenue', function ($q) {
        $q->from('filtered_sales')->where('revenue', '>', 1000);
    })
    ->table('high_revenue')
    ->where('region', 'North')
    ->get();
```

### 2. Leverage Window Functions

```php
// Instead of self-joins
$bad = DB::table('sales as s1')
    ->join('sales as s2', function ($join) {
        $join->on('s1.product_id', '=', 's2.product_id')
             ->on('s2.date', '<', 's1.date');
    })
    ->groupBy('s1.id')
    ->select('s1.*')
    ->selectRaw('COUNT(s2.id) + 1 as rank');

// Use window functions
$good = DB::connection('analytics')
    ->table('sales')
    ->select('*')
    ->selectRaw('RANK() OVER (PARTITION BY product_id ORDER BY date) as rank')
    ->get();
```

### 3. Optimize Aggregations

```php
// Pre-filter before aggregating
$optimized = DB::connection('analytics')
    ->table('large_dataset')
    ->where('status', 'active') // Filter first
    ->where('date', '>=', '2024-01-01')
    ->selectRaw('category, SUM(amount) as total')
    ->groupBy('category')
    ->having('total', '>', 1000) // Post-aggregation filter
    ->get();
```

## Next Steps

- Master [File Querying](/concepts/file-querying/) to analyze external data
- Learn about [Batch Operations](/concepts/batch-operations/) for efficient data loading
- Explore [Window Functions Guide](/guides/window-functions/) for advanced analytics
- See [Performance Optimization](/guides/performance/) tips