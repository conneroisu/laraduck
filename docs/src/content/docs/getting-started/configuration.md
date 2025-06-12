---
title: Configuration
description: Comprehensive guide to configuring Laravel DuckDB for your needs
---

# Configuration

Laravel DuckDB offers extensive configuration options to optimize performance and functionality for your specific use case.

## Configuration File

After installing the package, publish the configuration file:

```bash
php artisan vendor:publish --tag=laraduck-config
```

This creates `config/laraduck.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default DuckDB Connection
    |--------------------------------------------------------------------------
    |
    | This option controls the default DuckDB connection that gets used when
    | using the DuckDB facade or analytical models without specifying a
    | connection explicitly.
    |
    */
    'default' => env('DUCKDB_CONNECTION', 'analytics'),

    /*
    |--------------------------------------------------------------------------
    | DuckDB Extensions
    |--------------------------------------------------------------------------
    |
    | List of DuckDB extensions to automatically load. These extensions
    | provide additional functionality like file format support.
    |
    */
    'extensions' => [
        'parquet',
        'json',
        'csv',
        'httpfs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default DuckDB settings that apply to all connections unless
    | overridden in the specific connection configuration.
    |
    */
    'settings' => [
        'threads' => env('DUCKDB_THREADS', null),
        'memory_limit' => env('DUCKDB_MEMORY_LIMIT', '4GB'),
        'temp_directory' => env('DUCKDB_TEMP_DIR', storage_path('duckdb-temp')),
        'enable_progress_bar' => env('DUCKDB_PROGRESS_BAR', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | S3 Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for accessing S3-compatible storage. Required when
    | querying files from S3 buckets.
    |
    */
    's3' => [
        'access_key_id' => env('AWS_ACCESS_KEY_ID'),
        'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_ssl' => env('AWS_USE_SSL', true),
    ],
];
```

## Database Connection Configuration

Configure DuckDB connections in `config/database.php`:

### Basic Configuration

```php
'connections' => [
    'analytics' => [
        'driver' => 'duckdb',
        'database' => storage_path('analytics.duckdb'),
    ],
],
```

### Full Configuration Options

```php
'analytics' => [
    'driver' => 'duckdb',
    
    // Database location
    'database' => storage_path('analytics.duckdb'), // File path
    // OR
    'database' => ':memory:', // In-memory database
    
    // Performance settings
    'threads' => 8,                           // Number of threads (null = auto)
    'memory_limit' => '8GB',                  // Maximum memory usage
    'temp_directory' => '/tmp/duckdb',        // Temporary file location
    'max_memory' => '8GB',                    // Alternative to memory_limit
    
    // Access control
    'read_only' => false,                     // Read-only mode
    'access_mode' => 'automatic',             // automatic|read_only|read_write
    
    // Extensions to load
    'extensions' => [
        'parquet',                            // Parquet file support
        'json',                               // JSON file support
        'httpfs',                             // HTTP/S3 file access
        'postgres_scanner',                   // PostgreSQL federation
        'sqlite_scanner',                     // SQLite federation
        'mysql_scanner',                      // MySQL federation
    ],
    
    // Connection settings
    'settings' => [
        // Performance
        'threads' => 8,
        'memory_limit' => '8GB',
        'temp_directory' => '/tmp/duckdb',
        'max_memory' => '8GB',
        'worker_threads' => 8,
        
        // Query execution
        'default_order' => 'ASC',
        'default_null_order' => 'NULLS FIRST',
        'enable_progress_bar' => true,
        'enable_profiling' => false,
        'explain_output' => true,
        'log_query_path' => null,
        
        // Data handling
        'preserve_insertion_order' => false,
        'checkpoint_threshold' => '16MB',
        'force_checkpoint' => false,
        
        // File formats
        'parquet_compression' => 'snappy',    // snappy|gzip|zstd
        'csv_delimiter' => ',',
        'csv_header' => true,
        'csv_quote' => '"',
        'csv_escape' => '"',
        'csv_null' => '',
        
        // S3 configuration
        's3_region' => 'us-east-1',
        's3_access_key_id' => env('AWS_ACCESS_KEY_ID'),
        's3_secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        's3_endpoint' => env('AWS_ENDPOINT'),
        's3_use_ssl' => true,
        's3_url_style' => 'path',             // path|virtual
        
        // Advanced
        'enable_external_access' => true,
        'allow_unsigned_extensions' => false,
        'custom_user_agent' => 'Laravel-DuckDB',
    ],
    
    // Connection pooling (for web applications)
    'pool' => [
        'min' => 1,
        'max' => 10,
    ],
];
```

## Environment Variables

Configure via `.env` for different environments:

```env
# Basic configuration
DUCKDB_CONNECTION=analytics
DUCKDB_DATABASE=storage/analytics.duckdb
DUCKDB_MEMORY_LIMIT=8GB
DUCKDB_THREADS=8

# Performance tuning
DUCKDB_TEMP_DIR=/tmp/duckdb
DUCKDB_CHECKPOINT_THRESHOLD=16MB
DUCKDB_WORKER_THREADS=8

# S3 configuration
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_ENDPOINT=https://s3.amazonaws.com
AWS_USE_SSL=true

# File formats
DUCKDB_PARQUET_COMPRESSION=snappy
DUCKDB_CSV_DELIMITER=,
DUCKDB_CSV_HEADER=true

# Development settings
DUCKDB_ENABLE_PROGRESS_BAR=true
DUCKDB_ENABLE_PROFILING=true
DUCKDB_LOG_QUERY_PATH=storage/logs/duckdb-queries.log
```

## Memory Configuration

### Understanding Memory Limits

DuckDB uses memory for:
- Query execution
- Result sets
- Temporary data during sorts/joins
- Buffer pool for data pages

### Memory Configuration Strategies

```php
// Development: Conservative memory usage
'development' => [
    'driver' => 'duckdb',
    'database' => ':memory:',
    'memory_limit' => '2GB',
    'threads' => 2,
],

// Production: Optimized for performance
'production' => [
    'driver' => 'duckdb',
    'database' => storage_path('analytics.duckdb'),
    'memory_limit' => '16GB',
    'threads' => null, // Use all cores
    'temp_directory' => '/mnt/fast-ssd/duckdb-temp',
],

// Batch processing: Maximum resources
'batch' => [
    'driver' => 'duckdb',
    'database' => storage_path('batch.duckdb'),
    'memory_limit' => '32GB',
    'threads' => 16,
    'settings' => [
        'checkpoint_threshold' => '256MB',
        'preserve_insertion_order' => false,
    ],
],
```

### Memory Limit Guidelines

| Use Case | Memory Limit | Threads |
|----------|-------------|---------|
| Development | 1-2 GB | 2-4 |
| Small datasets (<1GB) | 2-4 GB | 4 |
| Medium datasets (1-10GB) | 8-16 GB | 8 |
| Large datasets (10-100GB) | 16-64 GB | 16+ |
| Data warehouse | 64GB+ | All cores |

## Extension Configuration

### Loading Extensions

Extensions can be loaded globally or per-connection:

```php
// Global configuration
'extensions' => [
    'parquet',
    'json',
    'httpfs',
],

// Per-connection
'analytics' => [
    'driver' => 'duckdb',
    'extensions' => [
        'parquet',
        'json',
        'httpfs',
        'postgres_scanner',
    ],
],
```

### Extension-Specific Settings

```php
'settings' => [
    // Parquet settings
    'parquet_compression' => 'snappy',
    'parquet_row_group_size' => 122880,
    'parquet_page_size' => 65536,
    
    // CSV settings
    'csv_delimiter' => ',',
    'csv_header' => true,
    'csv_quote' => '"',
    'csv_escape' => '"',
    'csv_null' => '\\N',
    'csv_dateformat' => '%Y-%m-%d',
    'csv_timestampformat' => '%Y-%m-%d %H:%M:%S',
    
    // JSON settings
    'json_format' => 'auto', // auto|records|lines
],
```

## S3 Configuration

### Basic S3 Setup

```php
's3' => [
    'access_key_id' => env('AWS_ACCESS_KEY_ID'),
    'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_ssl' => env('AWS_USE_SSL', true),
],
```

### Using Alternative S3 Providers

```php
// MinIO
's3' => [
    'endpoint' => 'http://localhost:9000',
    'access_key_id' => 'minioadmin',
    'secret_access_key' => 'minioadmin',
    'use_ssl' => false,
    'url_style' => 'path',
],

// DigitalOcean Spaces
's3' => [
    'endpoint' => 'https://nyc3.digitaloceanspaces.com',
    'region' => 'nyc3',
    'access_key_id' => env('DO_SPACES_KEY'),
    'secret_access_key' => env('DO_SPACES_SECRET'),
],

// Backblaze B2
's3' => [
    'endpoint' => 'https://s3.us-west-002.backblazeb2.com',
    'region' => 'us-west-002',
    'access_key_id' => env('B2_KEY_ID'),
    'secret_access_key' => env('B2_APPLICATION_KEY'),
],
```

## Performance Optimization

### Query Performance Settings

```php
'settings' => [
    // Parallel execution
    'threads' => 16,
    'worker_threads' => 16,
    
    // Memory allocation
    'memory_limit' => '32GB',
    'temp_directory' => '/mnt/nvme/duckdb-temp', // Fast SSD
    
    // Query optimization
    'enable_optimizer' => true,
    'optimizer_search_depth' => 25,
    'prefer_range_joins' => false,
    
    // Statistics
    'enable_profiling' => false, // Disable in production
    'enable_progress_bar' => false, // Disable in production
],
```

### Bulk Loading Settings

```php
'bulk_loading' => [
    'driver' => 'duckdb',
    'settings' => [
        // Disable constraints during load
        'checkpoint_threshold' => '256MB',
        'wal_autocheckpoint' => '256MB',
        'force_checkpoint' => false,
        
        // Optimize for inserts
        'preserve_insertion_order' => false,
        'default_order' => 'ASC',
        
        // Use all available resources
        'threads' => null,
        'memory_limit' => '64GB',
    ],
],
```

## Security Configuration

### Read-Only Connections

```php
'reporting' => [
    'driver' => 'duckdb',
    'database' => storage_path('analytics.duckdb'),
    'read_only' => true,
    'access_mode' => 'read_only',
],
```

### Restricting File Access

```php
'settings' => [
    // Disable external file access
    'enable_external_access' => false,
    
    // Disable HTTP/S3 access
    'extensions' => [
        'parquet',
        'json',
        // 'httpfs', // Commented out
    ],
    
    // Restrict file paths
    'allowed_paths' => [
        storage_path('uploads'),
        storage_path('exports'),
    ],
],
```

## Connection Pooling

For web applications with multiple concurrent requests:

```php
'analytics' => [
    'driver' => 'duckdb',
    'database' => storage_path('analytics.duckdb'),
    'pool' => [
        'min' => 2,        // Minimum connections
        'max' => 10,       // Maximum connections
        'idle' => 300,     // Idle timeout (seconds)
        'lifetime' => 3600, // Connection lifetime
    ],
],
```

## Debugging Configuration

### Development Settings

```php
'development' => [
    'driver' => 'duckdb',
    'settings' => [
        // Enable debugging features
        'enable_profiling' => true,
        'enable_progress_bar' => true,
        'explain_output' => true,
        'log_query_path' => storage_path('logs/duckdb.log'),
        
        // Detailed error messages
        'errors_as_json' => false,
        'print_errors' => true,
    ],
],
```

### Query Logging

```php
// config/logging.php
'channels' => [
    'duckdb' => [
        'driver' => 'daily',
        'path' => storage_path('logs/duckdb.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],

// In your connection config
'settings' => [
    'log_query_path' => storage_path('logs/duckdb-queries.log'),
],
```

## Next Steps

Now that you understand the configuration options:

- Learn about [Analytical Models](/concepts/analytical-models/) for building your data models
- Explore [Query Builder](/concepts/query-builder/) features
- Optimize performance with our [Performance Guide](/guides/performance/)
- Set up [File Querying](/concepts/file-querying/) for external data sources