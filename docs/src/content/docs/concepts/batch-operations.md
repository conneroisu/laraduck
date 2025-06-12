---
title: Batch Operations
description: Optimize data loading and processing with batch operations
---

# Batch Operations

Batch operations are essential for analytical workloads where you need to process large volumes of data efficiently. Laravel DuckDB provides specialized methods for bulk data operations that are orders of magnitude faster than traditional row-by-row processing.

## Why Batch Operations Matter

### OLTP vs OLAP Approach

**Traditional OLTP (Row-by-row):**

```php
// ❌ Slow for large datasets
foreach ($records as $record) {
    Sale::create($record); // Individual INSERT statements
}
// 10,000 records = ~30 seconds
```

**OLAP Batch Operations:**

```php
// ✅ Optimized for bulk data
Sale::batchInsert($records); // Single bulk INSERT
// 10,000 records = ~0.5 seconds
```

### Performance Comparison

| Operation           | Row-by-row | Batch | Improvement |
| ------------------- | ---------- | ----- | ----------- |
| Insert 10K records  | 30s        | 0.5s  | 60x faster  |
| Insert 100K records | 300s       | 2s    | 150x faster |
| Insert 1M records   | 3000s      | 15s   | 200x faster |

## Basic Batch Insert

### Using batchInsert()

```php
use App\Models\Analytics\Sale;

// Basic batch insert
$records = [
    ['product_id' => 1, 'quantity' => 5, 'price' => 99.99],
    ['product_id' => 2, 'quantity' => 3, 'price' => 149.99],
    // ... thousands more
];

Sale::batchInsert($records);

// With custom chunk size
Sale::batchInsert($records, chunkSize: 5000);

// With progress callback
Sale::batchInsert($records, chunkSize: 10000, function ($inserted, $total) {
    $this->info("Progress: {$inserted}/{$total} (" . round($inserted/$total * 100) . "%)");
});
```

### Using insert()

```php
// Laravel's built-in insert (also optimized in DuckDB)
Sale::insert($records);

// For very large datasets, chunk manually
collect($records)->chunk(10000)->each(function ($chunk) {
    Sale::insert($chunk->toArray());
});
```

## Advanced Batch Operations

### Batch Insert with Transformation

```php
// Transform data during insert
Sale::batchInsert($rawRecords, transform: function ($record) {
    return [
        'product_id' => $record['sku'],
        'quantity' => (int) $record['qty'],
        'price' => (float) str_replace('$', '', $record['price']),
        'created_at' => Carbon::parse($record['date']),
    ];
});

// With validation
Sale::batchInsert($records, validate: function ($record) {
    return $record['quantity'] > 0 && $record['price'] > 0;
});

// Combined transform and validate
Sale::batchInsert($records,
    transform: fn($r) => [
        'product_id' => $r['id'],
        'revenue' => $r['qty'] * $r['price']
    ],
    validate: fn($r) => $r['revenue'] > 0
);
```

### Batch Update

```php
// Bulk update using CASE statements
DB::connection('analytics')->update("
    UPDATE sales
    SET status = CASE
        WHEN revenue > 1000 THEN 'high_value'
        WHEN revenue > 100 THEN 'medium_value'
        ELSE 'low_value'
    END
    WHERE created_at >= ?
", ['2024-01-01']);

// Batch update from another table
DB::connection('analytics')->update("
    UPDATE customers c
    SET total_purchases = (
        SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id
    ),
    last_purchase_date = (
        SELECT MAX(created_at) FROM orders o WHERE o.customer_id = c.id
    )
");

// Using Laravel's upsert for batch updates
$updates = [
    ['id' => 1, 'status' => 'active', 'updated_at' => now()],
    ['id' => 2, 'status' => 'inactive', 'updated_at' => now()],
    // ...
];

Customer::upsert($updates, ['id'], ['status', 'updated_at']);
```

### Batch Delete

```php
// Delete with conditions
Sale::where('created_at', '<', now()->subYears(2))
    ->whereNull('archived_at')
    ->delete();

// Batch delete with IDs
$idsToDelete = [1, 2, 3, /* ... thousands more */];

// Chunk for very large sets
collect($idsToDelete)->chunk(1000)->each(function ($chunk) {
    Sale::whereIn('id', $chunk)->delete();
});

// Soft delete in batches
Sale::where('status', 'cancelled')
    ->update(['deleted_at' => now()]);
```

## Loading from Files

### CSV Import

```php
// Direct CSV import
Sale::insertFromCsv('/imports/sales.csv', [
    'delimiter' => ',',
    'header' => true,
    'dateformat' => '%Y-%m-%d',
    'timestampformat' => '%Y-%m-%d %H:%M:%S',
    'null' => 'NULL'
]);

// With column mapping
Sale::insertFromCsv('/imports/legacy_sales.csv', [
    'columns' => [
        'order_num' => 'order_id',
        'prod_code' => 'product_id',
        'qty' => 'quantity',
        'amt' => 'amount'
    ]
]);

// With data transformation
DB::connection('analytics')->statement("
    INSERT INTO sales (product_id, quantity, price, created_at)
    SELECT
        CAST(product_code AS INTEGER),
        CAST(qty AS INTEGER),
        CAST(REPLACE(price, '$', '') AS DECIMAL(10,2)),
        strptime(order_date, '%m/%d/%Y')
    FROM read_csv_auto('/imports/sales.csv')
    WHERE qty > 0
");
```

### Parquet Import

```php
// Direct Parquet import
Sale::insertFromParquet('s3://data-lake/sales/2024/*.parquet');

// With filtering
Sale::insertFromParquet('/data/sales.parquet', [
    'where' => "region = 'North America' AND status = 'completed'"
]);

// Selective column import
DB::connection('analytics')->statement("
    INSERT INTO sales (product_id, quantity, revenue)
    SELECT product_id, quantity, revenue
    FROM 's3://data-lake/sales.parquet'
    WHERE year = 2024
");
```

### JSON Import

```php
// JSON array import
Sale::insertFromJson('/imports/sales.json');

// JSON Lines import
Sale::insertFromJson('/logs/sales.jsonl', [
    'format' => 'lines'
]);

// With nested field extraction
DB::connection('analytics')->statement("
    INSERT INTO sales (product_id, quantity, customer_name)
    SELECT
        data->>'$.product.id',
        data->>'$.order.quantity',
        data->>'$.customer.name'
    FROM read_json_auto('/imports/nested_sales.json')
");
```

## Chunking Large Datasets

### Memory-Efficient Processing

```php
// Process large query results in chunks
Sale::query()
    ->where('year', 2023)
    ->chunk(10000, function ($sales) {
        // Process chunk
        $transformed = $sales->map(function ($sale) {
            return [
                'sale_id' => $sale->id,
                'revenue' => $sale->quantity * $sale->price,
                'margin' => $sale->revenue - $sale->cost,
            ];
        });

        // Insert transformed data
        SaleMetric::insert($transformed->toArray());
    });

// Using cursor for lower memory usage
Sale::query()
    ->where('status', 'pending')
    ->cursor()
    ->chunk(5000)
    ->each(function ($chunk) {
        ProcessSalesJob::dispatch($chunk);
    });

// Lazy collection for huge datasets
Sale::query()
    ->lazy(1000)
    ->each(function ($sale) {
        // Process one record at a time
        // Memory usage stays constant
    });
```

### Parallel Processing

```php
// Split large dataset for parallel processing
$totalRecords = Sale::count();
$chunks = 10;
$chunkSize = ceil($totalRecords / $chunks);

$jobs = collect(range(0, $chunks - 1))->map(function ($i) use ($chunkSize) {
    return new ProcessSalesChunk($i * $chunkSize, $chunkSize);
});

Bus::batch($jobs)->dispatch();

// In ProcessSalesChunk job
public function handle()
{
    Sale::query()
        ->offset($this->offset)
        ->limit($this->limit)
        ->each(function ($sale) {
            // Process sale
        });
}
```

## Copy Operations

### COPY TO (Export)

```php
// Export to CSV
DB::connection('analytics')->statement("
    COPY (SELECT * FROM sales WHERE year = 2024)
    TO '/exports/sales_2024.csv' (FORMAT CSV, HEADER)
");

// Export to Parquet with partitioning
DB::connection('analytics')->statement("
    COPY (SELECT * FROM sales)
    TO 's3://bucket/sales' (
        FORMAT PARQUET,
        PARTITION_BY (year, month),
        OVERWRITE_OR_IGNORE
    )
");

// Using model helper
Sale::query()
    ->where('year', 2024)
    ->copyTo('/exports/sales_2024.parquet', [
        'format' => 'parquet',
        'compression' => 'snappy'
    ]);
```

### COPY FROM (Import)

```php
// Import from CSV
DB::connection('analytics')->statement("
    COPY sales FROM '/imports/sales.csv' (
        FORMAT CSV,
        HEADER,
        DELIMITER '|',
        NULL 'NULL'
    )
");

// Import from multiple files
DB::connection('analytics')->statement("
    COPY sales FROM '/imports/sales_*.csv' (
        FORMAT CSV,
        HEADER
    )
");

// Using model helper
Sale::copyFrom('/imports/sales.parquet', [
    'format' => 'parquet',
    'columns' => ['product_id', 'quantity', 'price']
]);
```

## Transaction Management

### Batch Operations in Transactions

```php
DB::connection('analytics')->transaction(function () {
    // Multiple batch operations in a single transaction
    Sale::truncate();
    Sale::insertFromParquet('/data/sales_2024.parquet');
    Sale::insertFromCsv('/data/sales_current.csv');

    // Verify data
    $count = Sale::count();
    if ($count < 1000) {
        throw new \Exception('Data validation failed');
    }
});

// Manual transaction control
DB::connection('analytics')->beginTransaction();

try {
    Sale::batchInsert($records1);
    Product::batchInsert($records2);
    Customer::batchInsert($records3);

    DB::connection('analytics')->commit();
} catch (\Exception $e) {
    DB::connection('analytics')->rollBack();
    throw $e;
}
```

### Checkpoint Management

```php
// Force checkpoint after large batch operation
Sale::batchInsert($millionRecords);
DB::connection('analytics')->statement('CHECKPOINT');

// Configure automatic checkpointing
DB::connection('analytics')->statement("
    SET checkpoint_threshold = '1GB'
");
```

## Performance Optimization

### Disable Constraints

```php
// Temporarily disable constraints for bulk loading
DB::connection('analytics')->statement('SET foreign_key_checks = false');
DB::connection('analytics')->statement('SET unique_checks = false');

Sale::batchInsert($records);

DB::connection('analytics')->statement('SET foreign_key_checks = true');
DB::connection('analytics')->statement('SET unique_checks = true');
```

### Optimize for Loading

```php
// Create table optimized for bulk loading
Schema::connection('analytics')->create('sales_staging', function ($table) {
    $table->integer('product_id');
    $table->integer('quantity');
    $table->decimal('price', 10, 2);
    // No indexes during load
});

// Load data
Sale::batchInsert($records);

// Add indexes after loading
Schema::connection('analytics')->table('sales_staging', function ($table) {
    $table->index('product_id');
    $table->index(['created_at', 'product_id']);
});
```

### Memory Settings

```php
// Configure for large batch operations
DB::connection('analytics')->statement("SET memory_limit = '8GB'");
DB::connection('analytics')->statement("SET threads = 8");

// Perform batch operation
Sale::batchInsert($hugeDataset);
```

## Monitoring and Logging

### Progress Tracking

```php
class BatchImportService
{
    public function import($filePath)
    {
        $totalLines = $this->countFileLines($filePath);
        $processed = 0;

        $this->readCsv($filePath, function ($batch) use (&$processed, $totalLines) {
            Sale::insert($batch);

            $processed += count($batch);
            $progress = round(($processed / $totalLines) * 100);

            Log::info("Import progress: {$progress}%");
            Cache::put('import_progress', $progress, 300);

            event(new ImportProgress($processed, $totalLines));
        });
    }
}
```

### Performance Metrics

```php
// Log batch operation performance
$start = microtime(true);
$startMemory = memory_get_usage();

Sale::batchInsert($records);

$duration = microtime(true) - $start;
$memoryUsed = memory_get_usage() - $startMemory;
$recordsPerSecond = count($records) / $duration;

Log::info('Batch insert completed', [
    'records' => count($records),
    'duration' => round($duration, 2) . 's',
    'memory' => round($memoryUsed / 1024 / 1024, 2) . 'MB',
    'throughput' => round($recordsPerSecond) . ' records/sec'
]);
```

## Error Handling

### Handling Failed Batches

```php
public function batchImportWithRetry($records, $maxRetries = 3)
{
    $failed = [];

    collect($records)->chunk(5000)->each(function ($chunk) use (&$failed, $maxRetries) {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                Sale::insert($chunk->toArray());
                break;
            } catch (\Exception $e) {
                $attempts++;

                if ($attempts >= $maxRetries) {
                    $failed = array_merge($failed, $chunk->toArray());
                    Log::error('Batch insert failed', [
                        'error' => $e->getMessage(),
                        'records' => $chunk->count()
                    ]);
                } else {
                    sleep(pow(2, $attempts)); // Exponential backoff
                }
            }
        }
    });

    if (!empty($failed)) {
        // Handle failed records
        Storage::put('failed_imports.json', json_encode($failed));
    }
}
```

### Validation

```php
// Pre-validate before batch insert
$validator = Validator::make($records, [
    '*.product_id' => 'required|integer',
    '*.quantity' => 'required|integer|min:1',
    '*.price' => 'required|numeric|min:0'
]);

if ($validator->fails()) {
    // Handle validation errors
    $errors = $validator->errors();
    // Log or process errors
} else {
    Sale::batchInsert($records);
}
```

## Best Practices

### 1. Choose Appropriate Chunk Sizes

```php
// Based on available memory
$memoryLimit = ini_get('memory_limit');
$chunkSize = match(true) {
    $memoryLimit >= '2G' => 50000,
    $memoryLimit >= '1G' => 20000,
    $memoryLimit >= '512M' => 10000,
    default => 5000
};

Sale::batchInsert($records, chunkSize: $chunkSize);
```

### 2. Use Staging Tables

```php
// Load into staging first
DB::connection('analytics')->statement("
    CREATE TEMP TABLE sales_staging AS
    SELECT * FROM sales WHERE 1=0
");

Sale::on('analytics')->setTable('sales_staging')->batchInsert($records);

// Validate and move to final table
DB::connection('analytics')->statement("
    INSERT INTO sales
    SELECT * FROM sales_staging
    WHERE quantity > 0 AND price > 0
");
```

### 3. Schedule Large Imports

```php
// Use queued jobs for large imports
class ImportSalesData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        Sale::insertFromParquet('s3://data-lake/sales/*.parquet');

        event(new DataImportCompleted('sales'));
    }
}

// Schedule during off-peak hours
Schedule::job(new ImportSalesData)->dailyAt('02:00');
```

## Next Steps

- Explore [File Querying](/concepts/file-querying/) for direct file operations
- Learn about [Performance Optimization](/guides/performance/)
- See [Import/Export Guide](/guides/import-export/) for ETL workflows
- Check [E-commerce Example](/examples/ecommerce/) for real-world usage
