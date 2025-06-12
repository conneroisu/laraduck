---
title: Model Traits
description: LaraDuck model traits for enhanced functionality
---

## Available Traits

LaraDuck provides several traits to enhance your Eloquent models with DuckDB-specific functionality.

## HasDuckDB Trait

The base trait that enables DuckDB functionality on your models.

```php
use LaraDuck\Traits\HasDuckDB;

class AnalyticsModel extends Model
{
    use HasDuckDB;

    protected $connection = 'duckdb';
}
```

### Features

- DuckDB-specific query builder
- Support for DuckDB data types
- Optimized batch operations

## QueriesFiles Trait

Enables direct querying of files without importing data.

```php
use LaraDuck\Traits\QueriesFiles;

class LogAnalysis extends Model
{
    use HasDuckDB, QueriesFiles;

    public function analyzeAccessLogs()
    {
        return $this->fromFile('/var/log/access_*.log')
            ->whereRaw("status_code >= 400")
            ->groupBy('status_code')
            ->selectRaw('status_code, COUNT(*) as error_count')
            ->get();
    }
}
```

### Methods

```php
// Query single file
Model::fromFile('/path/to/data.csv');

// Query multiple files with glob
Model::fromFile('/data/sales_*.parquet');

// Query with options
Model::fromFile('/data.csv', [
    'header' => true,
    'delimiter' => '|',
    'skip' => 1
]);

// Query remote files
Model::fromFile('s3://bucket/data.parquet');
```

## HasWindowFunctions Trait

Provides fluent interface for window functions.

```php
use LaraDuck\Traits\HasWindowFunctions;

class SalesModel extends Model
{
    use HasDuckDB, HasWindowFunctions;

    public function withRankings()
    {
        return $this->addWindow('rank', 'ROW_NUMBER()', [
            'partition' => 'category',
            'order' => 'revenue DESC'
        ]);
    }
}
```

### Methods

```php
// Add window function
Model::addWindow('running_total', 'SUM(amount)', [
    'order' => 'date',
    'frame' => 'UNBOUNDED PRECEDING'
]);

// Multiple windows
Model::addWindow('rank', 'RANK()', ['partition' => 'dept'])
     ->addWindow('dense_rank', 'DENSE_RANK()', ['partition' => 'dept']);

// Named windows
Model::withNamedWindow('w', 'PARTITION BY category ORDER BY date')
     ->selectRaw('SUM(amount) OVER w');
```

## ExportsData Trait

Enables exporting query results to various formats.

```php
use LaraDuck\Traits\ExportsData;

class ReportModel extends Model
{
    use HasDuckDB, ExportsData;

    public function exportMonthlyReport()
    {
        return $this->where('month', date('Y-m'))
            ->exportToParquet('/exports/monthly_report.parquet');
    }
}
```

### Methods

```php
// Export to Parquet
Model::query()->exportToParquet('/path/to/output.parquet', [
    'compression' => 'snappy'
]);

// Export to CSV
Model::query()->exportToCsv('/path/to/output.csv', [
    'header' => true,
    'delimiter' => ','
]);

// Export to JSON
Model::query()->exportToJson('/path/to/output.json', [
    'array' => true
]);

// Export partitioned data
Model::query()
    ->partitionBy(['year', 'month'])
    ->exportToParquet('/data/partitioned/');
```

## HasArrayColumns Trait

Simplifies working with array/list columns.

```php
use LaraDuck\Traits\HasArrayColumns;

class UserPreferences extends Model
{
    use HasDuckDB, HasArrayColumns;

    protected $arrayCasts = [
        'tags' => 'string',
        'scores' => 'integer',
        'metadata' => 'json'
    ];
}
```

### Methods

```php
// Array operations
Model::whereArrayContains('tags', 'premium');
Model::whereArrayOverlaps('tags', ['vip', 'premium']);
Model::whereArrayLength('scores', '>', 5);

// Array aggregation
Model::selectArrayAgg('tag', 'all_tags')
     ->groupBy('user_id');

// Array manipulation
Model::appendToArray('tags', 'new-tag');
Model::removeFromArray('tags', 'old-tag');
```

## HasStructColumns Trait

Work with struct/nested data types.

```php
use LaraDuck\Traits\HasStructColumns;

class OrderModel extends Model
{
    use HasDuckDB, HasStructColumns;

    protected $structs = [
        'shipping_address' => [
            'street' => 'string',
            'city' => 'string',
            'state' => 'string',
            'zip' => 'string'
        ]
    ];
}
```

### Methods

```php
// Access struct fields
Model::select('shipping_address.city as city');

// Query struct fields
Model::where('shipping_address.state', 'CA');

// Create structs
Model::selectStruct([
    'name' => 'customer_name',
    'email' => 'customer_email'
], 'customer_info');
```

## HasApproximateAggregates Trait

Use approximate algorithms for faster aggregations.

```php
use LaraDuck\Traits\HasApproximateAggregates;

class EventModel extends Model
{
    use HasDuckDB, HasApproximateAggregates;

    public function uniqueUsersApprox()
    {
        return $this->approxCountDistinct('user_id');
    }
}
```

### Methods

```php
// Approximate count distinct
Model::approxCountDistinct('user_id');

// Approximate quantiles
Model::approxQuantile('response_time', 0.95);

// Approximate median
Model::approxMedian('price');

// HyperLogLog
Model::selectRaw('approx_count_distinct_hll(user_id) as unique_users');
```

## Combining Traits

```php
class AnalyticsModel extends Model
{
    use HasDuckDB,
        QueriesFiles,
        HasWindowFunctions,
        ExportsData,
        HasArrayColumns;

    protected $connection = 'duckdb';

    public function processLogs()
    {
        return $this->fromFile('/logs/*.parquet')
            ->whereArrayContains('tags', 'error')
            ->addWindow('error_rank', 'RANK()', [
                'partition' => 'error_type',
                'order' => 'count DESC'
            ])
            ->exportToParquet('/analysis/errors.parquet');
    }
}
```
