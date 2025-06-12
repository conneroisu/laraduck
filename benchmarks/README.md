# Laraduck Benchmarks

This directory contains performance benchmarks for the Laraduck DuckDB driver for Laravel.

## Quick Start

### Using Make (Recommended)

```bash
# Run all benchmarks
make benchmark

# Run performance benchmarks only
make benchmark-performance

# Run database comparison benchmarks
make benchmark-comparison
```

### Using Composer Scripts

```bash
# Run all benchmarks
composer benchmark

# Run performance benchmarks only
composer benchmark:performance

# Run database comparison benchmarks
composer benchmark:comparison
```

### Direct PHP Execution

```bash
# Run all benchmarks
php benchmarks/run-benchmarks.php

# Run specific benchmark suite
php benchmarks/run-benchmarks.php performance
php benchmarks/run-benchmarks.php comparison
```

## Benchmark Suites

### Performance Benchmarks

Tests Laraduck-specific features and performance characteristics:

- **Insert Performance**: Single row inserts (10,000 rows)
- **Bulk Insert Performance**: Batch inserts (100,000 rows in batches of 1,000)
- **Select Performance**: Various SELECT queries with conditions
- **Aggregation Performance**: COUNT, SUM, AVG, GROUP BY operations
- **Join Performance**: Complex JOIN queries with aggregations
- **Window Functions**: ROW_NUMBER, moving averages
- **Parquet Operations**: Read/write Parquet files
- **CSV Operations**: Read CSV files
- **JSON Queries**: JSON extraction and filtering

### Comparison Benchmarks

Compares DuckDB performance against other databases (if configured):

- MySQL
- PostgreSQL
- SQLite

Tests include:

- Insert operations (single and bulk)
- Select queries
- Aggregations
- Joins
- Analytical queries (window functions where supported)

## Configuration

To run comparison benchmarks, ensure you have the following database connections configured in your Laravel application:

```php
// config/database.php
'connections' => [
    'duckdb' => [
        'driver' => 'duckdb',
        'database' => ':memory:',
    ],
    'mysql' => [
        // MySQL configuration
    ],
    'pgsql' => [
        // PostgreSQL configuration
    ],
    'sqlite' => [
        // SQLite configuration
    ],
],
```

## Output

Benchmarks generate two output files:

1. **benchmark_results.json**: Detailed performance metrics for Laraduck
2. **comparison_results.json**: Comparison data across different databases

Results include:

- Execution time (milliseconds)
- Memory usage (MB)
- Success/failure status

## Interpreting Results

- **Time**: Lower is better. Measured in milliseconds.
- **Memory**: Lower is better. Measured in megabytes.
- **DuckDB Strengths**: Expect superior performance in analytical queries, aggregations, and file operations.
- **Trade-offs**: DuckDB may show different characteristics for OLTP workloads compared to traditional databases.

## Clean Up

To remove generated benchmark files:

```bash
make clean
```

This removes:

- benchmark_results.json
- comparison_results.json
- Any generated Parquet/CSV files
