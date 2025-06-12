---
title: Time Series Analysis
description: Perform time series analysis with LaraDuck's analytical capabilities
---

LaraDuck leverages DuckDB's powerful time series functions to make temporal data analysis straightforward in Laravel.

## Time Series Aggregations

```php
use App\Models\Analytics\MetricModel;

// Rolling averages
$movingAvg = MetricModel::query()
    ->select('timestamp', 'value')
    ->selectRaw('AVG(value) OVER (ORDER BY timestamp ROWS BETWEEN 6 PRECEDING AND CURRENT ROW) as moving_avg')
    ->orderBy('timestamp')
    ->get();

// Time bucketing
$hourlyStats = MetricModel::query()
    ->selectRaw("time_bucket(INTERVAL '1 hour', timestamp) as hour")
    ->selectRaw('COUNT(*) as count, AVG(value) as avg_value')
    ->groupBy('hour')
    ->orderBy('hour')
    ->get();
```

## Date/Time Functions

```php
// Extract date parts
$monthly = MetricModel::query()
    ->selectRaw('EXTRACT(YEAR FROM timestamp) as year')
    ->selectRaw('EXTRACT(MONTH FROM timestamp) as month')
    ->selectRaw('SUM(value) as total')
    ->groupBy(['year', 'month'])
    ->get();

// Date arithmetic
$recent = MetricModel::query()
    ->whereRaw("timestamp >= CURRENT_TIMESTAMP - INTERVAL '7 days'")
    ->get();
```

## Window Functions for Time Series

```php
// Lag and lead analysis
$changes = MetricModel::query()
    ->select('timestamp', 'value')
    ->selectRaw('LAG(value) OVER (ORDER BY timestamp) as previous_value')
    ->selectRaw('value - LAG(value) OVER (ORDER BY timestamp) as change')
    ->get();

// Cumulative calculations
$cumulative = MetricModel::query()
    ->select('timestamp', 'value')
    ->selectRaw('SUM(value) OVER (ORDER BY timestamp) as running_total')
    ->get();
```

## Resampling and Interpolation

```php
// Generate continuous time series
$resampled = MetricModel::query()
    ->fromRaw("generate_series(
        TIMESTAMP '2024-01-01', 
        TIMESTAMP '2024-12-31', 
        INTERVAL '1 day'
    ) as t(timestamp)")
    ->leftJoin('metrics', 'metrics.timestamp', '=', 't.timestamp')
    ->select('t.timestamp', 'metrics.value')
    ->get();

// Fill missing values
$filled = MetricModel::query()
    ->selectRaw('timestamp, COALESCE(value, LAG(value) OVER (ORDER BY timestamp)) as value')
    ->get();
```

## Time-based Partitioning

```php
// Partition by time for performance
MetricModel::query()
    ->partitionBy('DATE_TRUNC(\'month\', timestamp)')
    ->exportToParquet('/data/metrics/');
```

## Seasonal Analysis

```php
// Year-over-year comparison
$yoy = MetricModel::query()
    ->selectRaw('DATE_TRUNC(\'month\', timestamp) as month')
    ->selectRaw('SUM(value) as current_year')
    ->selectRaw('LAG(SUM(value), 12) OVER (ORDER BY DATE_TRUNC(\'month\', timestamp)) as previous_year')
    ->groupBy('month')
    ->havingRaw('previous_year IS NOT NULL')
    ->get();
```