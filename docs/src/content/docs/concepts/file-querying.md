---
title: File Querying
description: Query Parquet, CSV, JSON, and other files directly without importing
---

# File Querying

One of DuckDB's most powerful features is the ability to query files directly without importing them into a database. Laravel DuckDB makes this seamless through Eloquent models and the query builder.

## Overview

File querying allows you to:

- Query data files as if they were database tables
- Join files with database tables
- Process files larger than memory
- Analyze data without ETL pipelines
- Work with data lakes directly

## Supported File Formats

### Core Formats

| Format  | Extension         | Use Case              | Features                           |
| ------- | ----------------- | --------------------- | ---------------------------------- |
| Parquet | `.parquet`        | Analytics, Data Lakes | Columnar, compressed, schema-aware |
| CSV     | `.csv`, `.tsv`    | Data exchange         | Human-readable, widely supported   |
| JSON    | `.json`, `.jsonl` | APIs, Logs            | Flexible schema, nested data       |

### Additional Formats (with extensions)

| Format | Extension | Use Case         | Required Extension |
| ------ | --------- | ---------------- | ------------------ |
| Excel  | `.xlsx`   | Business data    | `excel`            |
| ORC    | `.orc`    | Hadoop ecosystem | `orc`              |
| Avro   | `.avro`   | Streaming        | `avro`             |

## Basic File Querying

### Using Eloquent Models

```php
use App\Models\Analytics\Sale;

// Query single Parquet file
$sales = Sale::fromParquetFile('/data/sales_2024.parquet')
    ->where('region', 'North America')
    ->sum('revenue');

// Query multiple CSV files with glob pattern
$allSales = Sale::fromCsvFile('/data/sales/*.csv')
    ->whereYear('created_at', 2024)
    ->get();

// Query JSON files
$events = Event::fromJsonFile('s3://logs/events-*.json')
    ->where('event_type', 'purchase')
    ->count();

// Query from URL
$remoteData = Sale::fromCsvFile('https://example.com/data/sales.csv')
    ->get();
```

### Using Query Builder

```php
use Illuminate\Support\Facades\DB;

// Direct file query
$results = DB::connection('analytics')
    ->table('/data/sales_2024.parquet')
    ->where('amount', '>', 1000)
    ->get();

// Query with explicit format
$results = DB::connection('analytics')
    ->tableFromFile('/data/sales.csv', 'csv')
    ->where('status', 'completed')
    ->get();

// Query S3 files
$s3Results = DB::connection('analytics')
    ->table('s3://bucket/path/to/data.parquet')
    ->select('customer_id', 'total')
    ->get();
```

## Glob Patterns and Multiple Files

### Pattern Matching

```php
// Single wildcard - matches any characters
$files = Sale::fromParquetFile('/data/sales_*.parquet')
    ->get(); // Matches: sales_2023.parquet, sales_2024.parquet

// Multiple wildcards
$files = Sale::fromCsvFile('/data/*/sales_*.csv')
    ->get(); // Matches: /data/2023/sales_jan.csv, /data/2024/sales_feb.csv

// Character sets
$files = Sale::fromParquetFile('/data/sales_202[34]_*.parquet')
    ->get(); // Matches only 2023 and 2024 files

// Recursive glob
$files = Sale::fromCsvFile('/data/**/sales.csv')
    ->get(); // Matches sales.csv in any subdirectory
```

### Union Multiple File Types

```php
// Query different file formats together
$combined = DB::connection('analytics')
    ->tableFromFiles([
        '/data/historical.parquet',
        '/data/current.csv',
        's3://bucket/recent.json'
    ])
    ->select('id', 'amount', 'date')
    ->get();

// Using Eloquent
$allData = Sale::fromFiles([
    '/archive/2022/*.parquet',
    '/current/2023/*.csv',
    '/pending/2024/*.json'
])->sum('revenue');
```

## CSV File Options

### Reading CSV Files

```php
// Basic CSV with auto-detection
$data = Customer::fromCsvFile('/data/customers.csv')->get();

// CSV with specific options
$data = Customer::fromCsvFile('/data/customers.csv', [
    'delimiter' => '|',
    'header' => true,
    'quote' => '"',
    'escape' => '\\',
    'dateformat' => '%Y-%m-%d',
    'timestampformat' => '%Y-%m-%d %H:%M:%S',
    'null_padding' => true,
    'skip' => 1, // Skip first row
    'columns' => ['id', 'name', 'email', 'created_at']
])->get();

// TSV files
$data = Customer::fromCsvFile('/data/customers.tsv', [
    'delimiter' => '\t'
])->get();

// CSV without headers
$data = DB::connection('analytics')
    ->tableFromFile('/data/no_headers.csv', 'csv', [
        'header' => false,
        'columns' => ['col1', 'col2', 'col3']
    ])
    ->get();
```

### Writing CSV Files

```php
// Export query results to CSV
Sale::whereYear('created_at', 2024)
    ->toCsvFile('/exports/sales_2024.csv');

// With options
Sale::whereYear('created_at', 2024)
    ->toCsvFile('/exports/sales_2024.csv', [
        'delimiter' => '|',
        'header' => true,
        'quote' => '"',
        'force_quote' => ['description', 'notes']
    ]);

// Partitioned CSV export
Sale::whereYear('created_at', 2024)
    ->toCsvFile('/exports/sales/', [
        'partition_by' => ['year', 'month'],
        'overwrite' => true
    ]);
```

## Parquet File Operations

### Reading Parquet Files

```php
// Basic Parquet query
$data = Order::fromParquetFile('s3://data-lake/orders/*.parquet')
    ->where('status', 'shipped')
    ->get();

// Read specific columns (column pruning)
$data = Order::fromParquetFile('/data/large_orders.parquet')
    ->select(['order_id', 'customer_id', 'total'])
    ->get(); // Only reads selected columns from disk

// Filter pushdown (predicate pushdown)
$data = Order::fromParquetFile('/data/orders_2024.parquet')
    ->where('created_at', '>=', '2024-06-01')
    ->get(); // Filter applied at file level

// Read Parquet metadata
$metadata = DB::connection('analytics')
    ->select("SELECT * FROM parquet_metadata('/data/file.parquet')");

// Read Parquet schema
$schema = DB::connection('analytics')
    ->select("SELECT * FROM parquet_schema('/data/file.parquet')");
```

### Writing Parquet Files

```php
// Basic export
Order::whereMonth('created_at', 6)
    ->toParquetFile('/exports/orders_june.parquet');

// With compression options
Order::whereMonth('created_at', 6)
    ->toParquetFile('/exports/orders_june.parquet', [
        'compression' => 'snappy', // snappy, gzip, zstd, lz4
        'row_group_size' => 100000
    ]);

// Partitioned Parquet files
Order::whereYear('created_at', 2024)
    ->toParquetFile('s3://data-lake/orders/', [
        'partition_by' => ['year', 'month', 'region'],
        'overwrite' => true,
        'compression' => 'zstd'
    ]);
```

## JSON File Operations

### Reading JSON Files

```php
// JSON array format
$data = Event::fromJsonFile('/logs/events.json')
    ->where('severity', 'error')
    ->get();

// JSON Lines format (one JSON object per line)
$data = Event::fromJsonFile('/logs/events.jsonl', [
    'format' => 'lines'
])->get();

// Nested JSON with path extraction
$data = DB::connection('analytics')
    ->tableFromFile('/data/nested.json', 'json')
    ->selectRaw("data->>'$.user.id' as user_id")
    ->selectRaw("data->>'$.event.type' as event_type")
    ->selectRaw("data->>'$.metadata.timestamp' as timestamp")
    ->get();

// Auto-detect JSON structure
$data = Product::fromJsonFile('/api/products.json', [
    'format' => 'auto', // auto, array, lines
    'records' => true   // Treat as records
])->get();
```

### Writing JSON Files

```php
// Export to JSON array
Customer::where('status', 'active')
    ->toJsonFile('/exports/active_customers.json');

// Export to JSON Lines
Event::whereDate('created_at', today())
    ->toJsonFile('/exports/events_today.jsonl', [
        'format' => 'lines'
    ]);

// Pretty printed JSON
Product::all()
    ->toJsonFile('/exports/products.json', [
        'pretty' => true,
        'indent' => 2
    ]);
```

## S3 and Cloud Storage

### Configuration

```php
// config/database.php
'analytics' => [
    'driver' => 'duckdb',
    'settings' => [
        's3_region' => 'us-east-1',
        's3_access_key_id' => env('AWS_ACCESS_KEY_ID'),
        's3_secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        's3_endpoint' => env('AWS_ENDPOINT'),
        's3_use_ssl' => true,
        's3_url_style' => 'path', // or 'virtual'
    ],
];
```

### Querying S3 Files

```php
// Query S3 bucket
$s3Data = Sale::fromParquetFile('s3://my-bucket/sales/year=2024/month=*/*.parquet')
    ->where('region', 'US')
    ->sum('revenue');

// Query with credentials per request
$s3Data = DB::connection('analytics')
    ->statement("SET s3_access_key_id='KEY'");

DB::connection('analytics')
    ->statement("SET s3_secret_access_key='SECRET'");

$results = Sale::fromParquetFile('s3://private-bucket/data.parquet')
    ->get();

// Query public S3 files
$publicData = Sale::fromCsvFile('s3://public-bucket/open-data.csv')
    ->get();

// MinIO or S3-compatible storage
DB::connection('analytics')
    ->statement("SET s3_endpoint='localhost:9000'");

$minioData = Sale::fromParquetFile('s3://local-bucket/data.parquet')
    ->get();
```

## HTTP/HTTPS Files

```php
// Query files over HTTP
$httpData = Product::fromCsvFile('https://example.com/data/products.csv')
    ->get();

// With authentication
DB::connection('analytics')
    ->statement("SET http_auth_type='basic'");
DB::connection('analytics')
    ->statement("SET http_auth_user='username'");
DB::connection('analytics')
    ->statement("SET http_auth_pass='password'");

$secureData = Sale::fromJsonFile('https://api.example.com/sales.json')
    ->get();

// Query API endpoints that return files
$apiData = Customer::fromJsonFile('https://api.example.com/customers?format=json&limit=1000')
    ->where('country', 'US')
    ->get();
```

## Combining Files and Tables

### Joining Files with Tables

```php
// Join Parquet file with database table
$enriched = DB::connection('analytics')
    ->table('customers as c')
    ->join('/data/purchases.parquet as p', 'c.id', '=', 'p.customer_id')
    ->select('c.name', 'c.email', 'p.amount', 'p.date')
    ->where('p.amount', '>', 100)
    ->get();

// Using Eloquent relationships with files
class Customer extends AnalyticalModel
{
    public function purchases()
    {
        return $this->hasMany(Purchase::class)
            ->fromParquetFile('/data/purchases/*.parquet');
    }
}

$customers = Customer::with('purchases')->get();
```

### Creating Views from Files

```php
// Create a view that queries files
DB::connection('analytics')->statement("
    CREATE OR REPLACE VIEW sales_view AS
    SELECT * FROM '/data/sales/*.parquet'
    WHERE year >= 2023
");

// Query the view normally
$recentSales = DB::connection('analytics')
    ->table('sales_view')
    ->where('region', 'Europe')
    ->get();
```

## Performance Optimization

### File Format Selection

```php
// Parquet: Best for analytics (columnar, compressed)
$analytics = Sale::fromParquetFile('s3://data-lake/sales.parquet')
    ->select('region', 'SUM(revenue)') // Column pruning
    ->where('date', '>=', '2024-01-01') // Predicate pushdown
    ->groupBy('region')
    ->get();

// CSV: Best for data exchange
$exchange = Customer::fromCsvFile('/exports/customers.csv')
    ->get();

// JSON: Best for semi-structured data
$logs = LogEntry::fromJsonFile('/logs/app-*.jsonl')
    ->where('level', 'ERROR')
    ->get();
```

### Partitioning Strategies

```php
// Write partitioned dataset
Order::chunk(100000, function ($orders) {
    $orders->toParquetFile('s3://data-lake/orders/', [
        'partition_by' => ['year', 'month', 'day'],
        'compression' => 'snappy'
    ]);
});

// Query partitioned dataset efficiently
$recentOrders = Order::fromParquetFile(
    's3://data-lake/orders/year=2024/month=06/day=*/*.parquet'
)->get(); // Only reads June 2024 files
```

### Caching File Queries

```php
// Cache file metadata
$cachedQuery = Cache::remember('sales_summary', 3600, function () {
    return Sale::fromParquetFile('s3://data-lake/sales_2024.parquet')
        ->groupBy('region')
        ->selectRaw('region, SUM(revenue) as total')
        ->get();
});

// Create temporary table from file
DB::connection('analytics')->statement("
    CREATE TEMP TABLE temp_sales AS
    SELECT * FROM 's3://data-lake/sales.parquet'
    WHERE date >= '2024-01-01'
");

// Query temp table multiple times
$byRegion = DB::connection('analytics')
    ->table('temp_sales')
    ->groupBy('region')
    ->sum('revenue');
```

## Error Handling

```php
try {
    $data = Sale::fromParquetFile('/invalid/path.parquet')->get();
} catch (\Exception $e) {
    // Handle file not found
    Log::error('File not found: ' . $e->getMessage());
}

// Check if file exists
if (Storage::disk('s3')->exists('data/sales.parquet')) {
    $data = Sale::fromParquetFile('s3://bucket/data/sales.parquet')->get();
}

// Validate file format
$file = '/uploads/data.csv';
$info = DB::connection('analytics')
    ->select("SELECT * FROM read_csv_auto('$file', LIMIT=1)");

if (empty($info)) {
    throw new \Exception('Invalid CSV file');
}
```

## Best Practices

### 1. Choose the Right Format

```php
// Analytics: Use Parquet
$analytics = Order::query()
    ->toParquetFile('/warehouse/orders.parquet', [
        'compression' => 'snappy'
    ]);

// Data exchange: Use CSV
$export = Customer::query()
    ->toCsvFile('/exports/customers.csv');

// APIs/Logs: Use JSON Lines
$logs = Event::query()
    ->toJsonFile('/logs/events.jsonl', [
        'format' => 'lines'
    ]);
```

### 2. Optimize File Layout

```php
// Partition by commonly filtered columns
Sale::query()
    ->toParquetFile('s3://data-lake/sales/', [
        'partition_by' => ['year', 'month', 'region']
    ]);

// Sort data for better compression
Sale::query()
    ->orderBy('created_at')
    ->orderBy('customer_id')
    ->toParquetFile('/data/sales_sorted.parquet');
```

### 3. Use File Metadata

```php
// Check file size before querying
$metadata = DB::connection('analytics')
    ->select("SELECT * FROM parquet_metadata('/large_file.parquet')")[0];

if ($metadata->file_size > 1000000000) { // 1GB
    // Use sampling for large files
    $sample = Sale::fromParquetFile('/large_file.parquet')
        ->sample(10000)
        ->get();
}
```

## Next Steps

- Learn about [Batch Operations](/concepts/batch-operations/) for efficient data loading
- Explore [Performance Optimization](/guides/performance/) for file queries
- See [Data Import/Export Guide](/guides/import-export/) for ETL workflows
- Check out [E-commerce Example](/examples/ecommerce/) using file queries
