---
title: Introduction
description: Learn about Laravel DuckDB and how it can transform your analytical workflows
---

# Introduction to Laravel DuckDB

Laravel DuckDB is a powerful integration that brings DuckDB's analytical capabilities to Laravel's Eloquent ORM. It's designed for developers who need to perform complex analytical queries on large datasets without leaving the comfort of Laravel.

## What is DuckDB?

DuckDB is an embedded analytical database designed to execute analytical SQL queries blazingly fast while embedded in another process. Think of it as "SQLite for analytics" - it requires no server, no configuration, and runs in-process.

### Key Features of DuckDB:
- **Columnar Storage**: Optimized for analytical queries
- **Vectorized Execution**: Processes data in batches for maximum CPU efficiency
- **Advanced SQL**: Window functions, CTEs, QUALIFY clause, and more
- **File Format Support**: Query Parquet, CSV, JSON files directly
- **Zero Dependencies**: Runs embedded in your application

## Why Laravel DuckDB?

Traditional databases like MySQL and PostgreSQL are optimized for transactional workloads (OLTP). They excel at:
- Quick single-row lookups
- Frequent updates and inserts
- Maintaining consistency across concurrent transactions

However, analytical workloads (OLAP) have different requirements:
- Aggregating millions of rows
- Complex joins across large datasets
- Time-series analysis
- Generating reports and dashboards

Laravel DuckDB bridges this gap by providing:

### 1. **Familiar Laravel Syntax**
```php
// Your existing Eloquent knowledge works!
Sales::query()
    ->whereYear('created_at', 2024)
    ->sum('revenue');
```

### 2. **Advanced Analytical Features**
```php
// Window functions with QUALIFY
Sales::query()
    ->select('*')
    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY created_at DESC) as rn')
    ->qualify('rn = 1')  // Latest order per customer
    ->get();
```

### 3. **Direct File Querying**
```php
// Query files without importing
Order::fromParquetFile('s3://data-lake/orders/*.parquet')
    ->whereDate('order_date', '>', now()->subDays(30))
    ->groupBy('status')
    ->selectRaw('status, COUNT(*) as count')
    ->get();
```

### 4. **Blazing Performance**
- 10-100x faster for analytical queries
- Minimal memory footprint
- Efficient CPU utilization

## Use Cases

Laravel DuckDB shines in scenarios like:

### Analytics Dashboards
Build real-time dashboards that aggregate millions of rows without pre-computation:
```php
Dashboard::getMetrics()
    ->calculateRevenue()
    ->calculateGrowth()
    ->getTopProducts()
    ->execute(); // Returns in milliseconds
```

### Data Lake Queries
Query your data lake directly from Laravel:
```php
DataLake::query('s3://bucket/path/to/data/**/*.parquet')
    ->where('event_type', 'purchase')
    ->groupByTime('day')
    ->aggregate();
```

### Log Analysis
Analyze log files without complex ETL pipelines:
```php
LogEntry::fromCsvFile('/var/log/app/*.csv')
    ->whereLevel('error')
    ->groupBy('endpoint')
    ->orderByDesc('count')
    ->get();
```

### Time Series Analysis
Efficient storage and querying of time-series data:
```php
Metrics::timeSeries()
    ->granularity('hour')
    ->movingAverage(24)
    ->detect_anomalies()
    ->get();
```

## Architecture Overview

Laravel DuckDB extends Laravel's database layer:

```
┌─────────────────┐
│  Your Laravel   │
│   Application   │
└────────┬────────┘
         │
┌────────▼────────┐
│ Eloquent Models │
│  & Query Builder│
└────────┬────────┘
         │
┌────────▼────────┐
│  Laravel DuckDB │
│   Driver Layer  │
└────────┬────────┘
         │
┌────────▼────────┐
│  DuckDB Engine  │
│   (Embedded)    │
└─────────────────┘
```

## When to Use Laravel DuckDB

✅ **Use Laravel DuckDB when:**
- Building analytics dashboards
- Generating complex reports
- Analyzing time-series data
- Querying data lakes or files
- Performing ETL operations
- Need fast aggregations

❌ **Keep using MySQL/PostgreSQL for:**
- User authentication
- Session management
- Real-time transactions
- Frequent updates
- Strong consistency requirements

## What's Next?

Ready to get started? Head to the [Installation Guide](/getting-started/installation/) to set up Laravel DuckDB in your project.

For a quick overview of what you can build, check out our [Quick Start Guide](/getting-started/quick-start/) which walks through building a simple analytics dashboard.