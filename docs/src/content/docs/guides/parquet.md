---
title: Working with Parquet Files
description: Learn how to efficiently work with Parquet files using LaraDuck
---

Parquet is a columnar storage format that DuckDB excels at reading. LaraDuck makes it easy to query Parquet files directly from your Laravel application.

## Reading Parquet Files

```php
use App\Models\DuckModel;

// Query a single Parquet file
$results = DuckModel::fromFile('/path/to/data.parquet')
    ->where('date', '>=', '2024-01-01')
    ->get();

// Query multiple Parquet files with glob patterns
$sales = DuckModel::fromFile('/data/sales_*.parquet')
    ->select('product_id', 'SUM(quantity) as total_quantity')
    ->groupBy('product_id')
    ->get();
```

## Writing Parquet Files

```php
// Export query results to Parquet
DuckModel::query()
    ->where('created_at', '>=', now()->subMonth())
    ->exportToParquet('/exports/monthly_data.parquet');

// Export with compression
DuckModel::query()
    ->exportToParquet('/exports/compressed_data.parquet', [
        'compression' => 'snappy'
    ]);
```

## Partitioned Parquet Files

```php
// Read partitioned dataset
$results = DuckModel::fromFile('/data/partitioned/**/*.parquet')
    ->where('year', 2024)
    ->where('month', 3)
    ->get();

// Write partitioned dataset
DuckModel::query()
    ->partitionBy(['year', 'month'])
    ->exportToParquet('/exports/partitioned/');
```

## Performance Tips

1. **Use column projection**: Only select columns you need
2. **Leverage predicate pushdown**: DuckDB pushes filters to Parquet reader
3. **Use appropriate compression**: Snappy for speed, zstd for size
4. **Partition large datasets**: Improves query performance

## Schema Evolution

```php
// Handle schema changes gracefully
$results = DuckModel::fromFile('/data/*.parquet')
    ->withSchemaEvolution()
    ->get();
```
