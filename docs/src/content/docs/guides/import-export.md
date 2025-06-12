---
title: Data Import/Export
description: Efficiently import and export data with Laravel DuckDB
---

# Data Import/Export Guide

Laravel DuckDB provides powerful tools for importing and exporting data in various formats. This guide covers best practices for ETL operations, data migration, and integration with data lakes.

## Import Operations

### CSV Import

```php
use App\Models\Analytics\Sale;

// Basic CSV import
Sale::insertFromCsv('/data/sales.csv');

// With options
Sale::insertFromCsv('/data/sales.csv', [
    'delimiter' => ',',
    'header' => true,
    'dateformat' => '%Y-%m-%d',
    'timestampformat' => '%Y-%m-%d %H:%M:%S',
    'null' => 'NULL',
    'skip' => 1,
    'columns' => ['order_id', 'product_id', 'quantity', 'price']
]);

// Import with transformation
DB::connection('analytics')->statement("
    INSERT INTO sales (product_id, quantity, revenue, created_at)
    SELECT
        CAST(product_code AS INTEGER),
        CAST(qty AS INTEGER),
        CAST(qty AS INTEGER) * CAST(REPLACE(price, '$', '') AS DECIMAL(10,2)),
        strptime(order_date, '%m/%d/%Y')
    FROM read_csv_auto('/data/sales.csv')
    WHERE qty > 0
");
```

### Parquet Import

```php
// Import from local Parquet file
Sale::insertFromParquet('/data/sales.parquet');

// Import from S3
Sale::insertFromParquet('s3://data-lake/sales/year=2024/*.parquet');

// Selective import with filtering
DB::connection('analytics')->statement("
    INSERT INTO sales
    SELECT * FROM 's3://data-lake/sales.parquet'
    WHERE region = 'North America'
    AND order_date >= '2024-01-01'
");
```

### JSON Import

```php
// JSON array format
Sale::insertFromJson('/data/sales.json');

// JSON Lines format
Sale::insertFromJson('/data/sales.jsonl', [
    'format' => 'lines'
]);

// Nested JSON extraction
DB::connection('analytics')->statement("
    INSERT INTO sales (order_id, customer_id, total, items)
    SELECT
        data->>'$.orderId',
        data->>'$.customer.id',
        CAST(data->>'$.total' AS DECIMAL(10,2)),
        data->'$.items'
    FROM read_json_auto('/data/nested_orders.json')
");
```

## Export Operations

### CSV Export

```php
// Basic export
Sale::whereYear('created_at', 2024)
    ->toCsvFile('/exports/sales_2024.csv');

// With compression
Sale::whereYear('created_at', 2024)
    ->toCsvFile('/exports/sales_2024.csv.gz', [
        'compression' => 'gzip',
        'delimiter' => ',',
        'header' => true
    ]);

// Partitioned export
Sale::whereYear('created_at', 2024)
    ->toCsvFile('/exports/sales/', [
        'partition_by' => ['year', 'month'],
        'filename_pattern' => 'sales_{year}_{month}.csv'
    ]);
```

### Parquet Export

```php
// Export to Parquet with compression
Sale::whereYear('created_at', 2024)
    ->toParquetFile('/exports/sales_2024.parquet', [
        'compression' => 'snappy', // snappy, gzip, zstd, lz4
        'row_group_size' => 100000
    ]);

// Export to S3 with partitioning
Sale::query()
    ->toParquetFile('s3://data-lake/sales/', [
        'partition_by' => ['year', 'month', 'region'],
        'overwrite' => true,
        'compression' => 'zstd'
    ]);
```

## COPY Operations

### COPY TO (Export)

```php
// Export query results
DB::connection('analytics')->statement("
    COPY (
        SELECT
            customer_id,
            SUM(order_total) as lifetime_value,
            COUNT(*) as order_count,
            MAX(order_date) as last_order_date
        FROM orders
        GROUP BY customer_id
        HAVING lifetime_value > 1000
    ) TO '/exports/high_value_customers.parquet' (FORMAT PARQUET)
");

// Export with partitioning
DB::connection('analytics')->statement("
    COPY sales TO 's3://data-lake/sales' (
        FORMAT PARQUET,
        PARTITION_BY (year, month),
        OVERWRITE_OR_IGNORE,
        COMPRESSION ZSTD
    )
");
```

### COPY FROM (Import)

```php
// Import from CSV
DB::connection('analytics')->statement("
    COPY sales FROM '/imports/sales_*.csv' (
        FORMAT CSV,
        HEADER,
        DELIMITER '|',
        DATEFORMAT '%Y-%m-%d',
        TIMESTAMPFORMAT '%Y-%m-%d %H:%M:%S.%f'
    )
");

// Import from multiple sources
DB::connection('analytics')->statement("
    COPY sales FROM '/imports/sales_*.csv', 's3://backup/sales_*.parquet' (
        FORMAT AUTO,
        UNION_BY_NAME
    )
");
```

## ETL Pipelines

### Building an ETL Pipeline

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class DailyETLPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        // 1. Extract from source systems
        $this->extractFromSources();

        // 2. Transform data
        $this->transformData();

        // 3. Load into analytics database
        $this->loadData();

        // 4. Update materialized views
        $this->refreshMaterializedViews();

        // 5. Export to data lake
        $this->exportToDataLake();
    }

    private function extractFromSources()
    {
        // Extract from MySQL
        DB::connection('analytics')->statement("
            CREATE OR REPLACE TABLE staging_orders AS
            SELECT * FROM mysql_scan('mysql://user:pass@host:3306/db', 'orders')
            WHERE created_at >= CURRENT_DATE - INTERVAL '1 day'
        ");

        // Extract from PostgreSQL
        DB::connection('analytics')->statement("
            CREATE OR REPLACE TABLE staging_customers AS
            SELECT * FROM postgres_scan('postgresql://user:pass@host:5432/db', 'customers')
            WHERE updated_at >= CURRENT_DATE - INTERVAL '1 day'
        ");

        // Extract from APIs
        $apiData = Http::get('https://api.example.com/products')->json();
        Product::insertFromJson($apiData);
    }

    private function transformData()
    {
        // Clean and transform data
        DB::connection('analytics')->statement("
            CREATE OR REPLACE TABLE transformed_orders AS
            SELECT
                o.id as order_id,
                o.customer_id,
                c.segment as customer_segment,
                o.total as order_total,
                o.created_at as order_date,
                DATE_TRUNC('month', o.created_at) as order_month,
                EXTRACT(year FROM o.created_at) as order_year
            FROM staging_orders o
            LEFT JOIN staging_customers c ON o.customer_id = c.id
            WHERE o.total > 0
            AND o.status != 'cancelled'
        ");
    }

    private function loadData()
    {
        // Load into fact tables
        DB::connection('analytics')->statement("
            INSERT INTO fact_sales
            SELECT * FROM transformed_orders
            ON CONFLICT (order_id) DO UPDATE SET
                order_total = EXCLUDED.order_total,
                updated_at = CURRENT_TIMESTAMP
        ");

        // Update dimension tables
        DB::connection('analytics')->statement("
            INSERT OR REPLACE INTO dim_customer
            SELECT DISTINCT
                customer_id,
                customer_segment,
                CURRENT_TIMESTAMP as last_seen
            FROM transformed_orders
        ");
    }

    private function exportToDataLake()
    {
        // Export to S3 data lake
        DB::connection('analytics')->statement("
            COPY fact_sales TO 's3://data-lake/sales/' (
                FORMAT PARQUET,
                PARTITION_BY (order_year, order_month),
                OVERWRITE_OR_IGNORE,
                COMPRESSION SNAPPY
            )
            WHERE order_date >= CURRENT_DATE - INTERVAL '1 day'
        ");
    }
}
```

### Incremental Loading

```php
class IncrementalLoader
{
    public function loadIncrementally($source, $target, $trackingColumn = 'updated_at')
    {
        // Get last loaded timestamp
        $lastLoaded = DB::connection('analytics')
            ->table('etl_tracking')
            ->where('table_name', $target)
            ->value('last_loaded_at') ?? '1900-01-01';

        // Load only new/updated records
        $query = "
            INSERT INTO {$target}
            SELECT * FROM '{$source}'
            WHERE {$trackingColumn} > '{$lastLoaded}'
        ";

        $startTime = now();
        DB::connection('analytics')->statement($query);

        // Update tracking
        DB::connection('analytics')->table('etl_tracking')
            ->updateOrInsert(
                ['table_name' => $target],
                [
                    'last_loaded_at' => $startTime,
                    'records_loaded' => DB::connection('analytics')
                        ->select("SELECT COUNT(*) as cnt FROM {$target} WHERE {$trackingColumn} > ?", [$lastLoaded])[0]->cnt,
                    'updated_at' => now()
                ]
            );
    }
}
```

## Data Migration

### Migrating from MySQL to DuckDB

```php
class MySQLToDuckDBMigrator
{
    public function migrate($mysqlTable, $duckdbTable, $batchSize = 10000)
    {
        // Get total records
        $total = DB::connection('mysql')
            ->table($mysqlTable)
            ->count();

        // Create table structure in DuckDB
        $this->createTableStructure($mysqlTable, $duckdbTable);

        // Migrate in batches
        DB::connection('mysql')
            ->table($mysqlTable)
            ->orderBy('id')
            ->chunk($batchSize, function ($records) use ($duckdbTable) {
                $data = $records->map(function ($record) {
                    return (array) $record;
                })->toArray();

                DB::connection('analytics')
                    ->table($duckdbTable)
                    ->insert($data);

                $this->logProgress(count($data));
            });
    }

    private function createTableStructure($mysqlTable, $duckdbTable)
    {
        // Get MySQL schema
        $columns = DB::connection('mysql')
            ->select("SHOW COLUMNS FROM {$mysqlTable}");

        // Build DuckDB CREATE TABLE
        $ddl = "CREATE TABLE IF NOT EXISTS {$duckdbTable} (";
        $columnDefs = [];

        foreach ($columns as $column) {
            $duckdbType = $this->mapMySQLTypeToDuckDB($column->Type);
            $nullable = $column->Null === 'YES' ? '' : ' NOT NULL';
            $columnDefs[] = "{$column->Field} {$duckdbType}{$nullable}";
        }

        $ddl .= implode(', ', $columnDefs) . ")";

        DB::connection('analytics')->statement($ddl);
    }
}
```

## Data Lake Integration

### Querying Data Lakes

```php
// Query multiple file formats from S3
$dataLakeQuery = DB::connection('analytics')->table(
    DB::raw("read_parquet('s3://data-lake/sales/year=2024/month=*/day=*/*.parquet')
    UNION ALL
    read_csv_auto('s3://data-lake/legacy/sales_2023.csv')")
)->where('amount', '>', 100);

// Create external table pointing to S3
DB::connection('analytics')->statement("
    CREATE OR REPLACE VIEW sales_lake AS
    SELECT * FROM read_parquet('s3://data-lake/sales/**/*.parquet')
");

// Query the external table
$results = DB::connection('analytics')
    ->table('sales_lake')
    ->whereYear('order_date', 2024)
    ->sum('revenue');
```

### Data Lake Export Patterns

```php
class DataLakeExporter
{
    public function exportToDataLake($model, $options = [])
    {
        $defaults = [
            'format' => 'parquet',
            'compression' => 'snappy',
            'partition_by' => ['year', 'month'],
            'base_path' => 's3://data-lake/',
            'overwrite' => false
        ];

        $options = array_merge($defaults, $options);

        $path = $options['base_path'] . strtolower(class_basename($model)) . '/';

        return $model::query()
            ->when($options['filter'] ?? null, function ($query, $filter) {
                return $query->where($filter);
            })
            ->toParquetFile($path, [
                'partition_by' => $options['partition_by'],
                'compression' => $options['compression'],
                'overwrite' => $options['overwrite']
            ]);
    }
}

// Usage
$exporter = new DataLakeExporter();
$exporter->exportToDataLake(Sale::class, [
    'filter' => ['created_at', '>=', now()->subMonth()],
    'partition_by' => ['year', 'month', 'region']
]);
```

## Performance Optimization

### Parallel Import/Export

```php
// Parallel import from multiple files
$files = [
    '/data/sales_q1.csv',
    '/data/sales_q2.csv',
    '/data/sales_q3.csv',
    '/data/sales_q4.csv'
];

$jobs = collect($files)->map(function ($file) {
    return new ImportFileJob($file);
});

Bus::batch($jobs)->dispatch();

// Parallel export with partitioning
DB::connection('analytics')->statement("
    SET threads = 16;
    COPY sales TO 's3://data-lake/sales/' (
        FORMAT PARQUET,
        PARTITION_BY (year, month, day),
        PARALLEL true
    )
");
```

### Compression Strategies

```php
// Compare compression methods
$compressionMethods = ['none', 'snappy', 'gzip', 'zstd', 'lz4'];

foreach ($compressionMethods as $method) {
    $start = microtime(true);

    Sale::query()
        ->whereYear('created_at', 2024)
        ->toParquetFile("/tmp/sales_{$method}.parquet", [
            'compression' => $method
        ]);

    $duration = microtime(true) - $start;
    $size = filesize("/tmp/sales_{$method}.parquet");

    Log::info("Compression: {$method}", [
        'duration' => $duration,
        'size_mb' => $size / 1024 / 1024,
        'ratio' => $size / $uncompressedSize
    ]);
}
```

## Error Handling

### Robust Import with Error Recovery

```php
class RobustImporter
{
    public function importWithRecovery($source, $target)
    {
        $errorFile = storage_path('import_errors.csv');

        try {
            // Attempt direct import
            DB::connection('analytics')->statement("
                COPY {$target} FROM '{$source}' (
                    FORMAT AUTO,
                    IGNORE_ERRORS true,
                    STORE_REJECTS true,
                    REJECTS_TABLE 'import_rejects'
                )
            ");

            // Check for rejects
            $rejects = DB::connection('analytics')
                ->table('import_rejects')
                ->count();

            if ($rejects > 0) {
                $this->handleRejects($rejects);
            }

        } catch (\Exception $e) {
            // Fallback to row-by-row import with error tracking
            $this->fallbackImport($source, $target, $errorFile);
        }
    }

    private function fallbackImport($source, $target, $errorFile)
    {
        $errors = [];
        $success = 0;

        $handle = fopen($source, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $data = array_combine($headers, $row);
                DB::connection('analytics')->table($target)->insert($data);
                $success++;
            } catch (\Exception $e) {
                $errors[] = array_merge($row, [$e->getMessage()]);
            }
        }

        fclose($handle);

        // Write errors to file
        if (!empty($errors)) {
            $this->writeErrorsToFile($errorFile, $errors);
        }

        Log::info("Import completed", [
            'success' => $success,
            'errors' => count($errors)
        ]);
    }
}
```

## Best Practices

### 1. Choose the Right Format

```php
// Analytics: Use Parquet
$analyticsExport = Sale::query()
    ->toParquetFile('/exports/analytics.parquet', [
        'compression' => 'snappy',
        'statistics' => true
    ]);

// Data exchange: Use CSV
$exchangeExport = Customer::query()
    ->toCsvFile('/exports/customers.csv', [
        'header' => true,
        'delimiter' => ','
    ]);

// Streaming/Logs: Use JSON Lines
$streamExport = Event::query()
    ->toJsonFile('/exports/events.jsonl', [
        'format' => 'lines'
    ]);
```

### 2. Optimize for Your Use Case

```php
// For fast queries: Sort and partition
Sale::query()
    ->orderBy('created_at')
    ->orderBy('customer_id')
    ->toParquetFile('s3://data-lake/sales/', [
        'partition_by' => ['year', 'month'],
        'row_group_size' => 100000
    ]);

// For compression: Use appropriate codec
LargeDataset::query()
    ->toParquetFile('/archive/data.parquet', [
        'compression' => 'zstd', // Best compression ratio
        'compression_level' => 9
    ]);
```

### 3. Monitor Performance

```php
class ImportExportMonitor
{
    public function trackOperation($operation, callable $callback)
    {
        $start = microtime(true);
        $startMemory = memory_get_usage();

        $result = $callback();

        $metrics = [
            'operation' => $operation,
            'duration' => microtime(true) - $start,
            'memory_mb' => (memory_get_usage() - $startMemory) / 1024 / 1024,
            'timestamp' => now()
        ];

        DB::connection('analytics')
            ->table('import_export_metrics')
            ->insert($metrics);

        return $result;
    }
}
```

## Next Steps

- Learn about [Batch Operations](/concepts/batch-operations) for efficient data loading
- Explore [File Querying](/concepts/file-querying) capabilities
- See [Performance Optimization](/guides/performance) for large datasets
- Check out [E-commerce Example](/examples/ecommerce) with import/export
