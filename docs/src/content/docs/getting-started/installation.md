---
title: Installation
description: Step-by-step guide to install Laravel DuckDB in your project
---

# Installation

This guide will walk you through installing Laravel DuckDB in your Laravel application.

## Requirements

Before installing Laravel DuckDB, ensure your environment meets these requirements:

- PHP 8.1 or higher
- Laravel 10.0 or higher
- FFI extension enabled (for DuckDB bindings)
- 64-bit system (Linux, macOS, or Windows)

### Checking FFI Support

```bash
php -m | grep FFI
```

If FFI is not listed, you'll need to enable it in your `php.ini`:
```ini
extension=ffi
ffi.enable=true
```

## Installation Steps

### 1. Install via Composer

```bash
composer require laraduck/laraduck
```

### 2. Install DuckDB PHP Extension

The DuckDB PHP extension provides the native bindings:

```bash
composer require saturio/duckdb
```

> **Note**: If the package is not available, you can use the mock implementation for testing purposes.

### 3. Publish Configuration

Publish the package configuration file:

```bash
php artisan vendor:publish --tag=laraduck-config
```

This creates `config/laraduck.php` with default settings.

### 4. Configure Database Connection

Add a DuckDB connection to your `config/database.php`:

```php
'connections' => [
    // Your existing connections...
    
    'analytics' => [
        'driver' => 'duckdb',
        'database' => storage_path('analytics.duckdb'),
        'read_only' => false,
        'threads' => null, // Uses all available cores
        'memory_limit' => '4GB',
        'temp_directory' => storage_path('duckdb-temp'),
        'extensions' => [
            'parquet',
            'json',
        ],
    ],
],
```

### 5. Create Storage Directories

```bash
mkdir -p storage/duckdb-temp
chmod 755 storage/duckdb-temp
```

## Configuration Options

### Basic Configuration

```php
'analytics' => [
    'driver' => 'duckdb',
    'database' => ':memory:', // In-memory database
    // OR
    'database' => storage_path('analytics.duckdb'), // Persistent database
]
```

### Advanced Configuration

```php
'analytics' => [
    'driver' => 'duckdb',
    'database' => storage_path('analytics.duckdb'),
    
    // Performance settings
    'threads' => 8,                    // Number of threads
    'memory_limit' => '8GB',          // Memory limit
    'temp_directory' => '/tmp/duckdb', // Temp file location
    
    // Access mode
    'read_only' => false,             // Read-write mode
    'access_mode' => 'automatic',     // automatic, read_only, read_write
    
    // Extensions to load
    'extensions' => [
        'parquet',                    // Parquet file support
        'json',                       // JSON file support
        'httpfs',                     // HTTP/S3 file access
        'postgres_scanner',           // PostgreSQL scanner
    ],
    
    // Additional settings
    'settings' => [
        'default_order' => 'DESC',
        'enable_progress_bar' => true,
        'preserve_insertion_order' => false,
    ],
]
```

## Using Multiple Connections

You can configure multiple DuckDB connections for different use cases:

```php
'connections' => [
    // High-performance analytics
    'analytics' => [
        'driver' => 'duckdb',
        'database' => storage_path('analytics.duckdb'),
        'memory_limit' => '16GB',
        'threads' => 16,
    ],
    
    // Read-only reporting
    'reporting' => [
        'driver' => 'duckdb',
        'database' => storage_path('reporting.duckdb'),
        'read_only' => true,
        'memory_limit' => '4GB',
    ],
    
    // In-memory processing
    'processing' => [
        'driver' => 'duckdb',
        'database' => ':memory:',
        'memory_limit' => '8GB',
    ],
],
```

## Environment Configuration

Configure connections using environment variables:

```env
# .env
ANALYTICS_DB_PATH=storage/analytics.duckdb
ANALYTICS_DB_MEMORY=8GB
ANALYTICS_DB_THREADS=8
ANALYTICS_DB_READONLY=false
```

```php
// config/database.php
'analytics' => [
    'driver' => 'duckdb',
    'database' => env('ANALYTICS_DB_PATH', storage_path('analytics.duckdb')),
    'memory_limit' => env('ANALYTICS_DB_MEMORY', '4GB'),
    'threads' => env('ANALYTICS_DB_THREADS'),
    'read_only' => env('ANALYTICS_DB_READONLY', false),
],
```

## Verifying Installation

### 1. Check Service Provider

Ensure the service provider is registered (Laravel auto-discovers it):

```php
// config/app.php (only if not auto-discovered)
'providers' => [
    // ...
    ConnerOiSu\LaravelDuckDB\DuckDBServiceProvider::class,
],
```

### 2. Test Connection

```php
use Illuminate\Support\Facades\DB;

// Test the connection
try {
    $version = DB::connection('analytics')
        ->select("SELECT version() as version")[0]->version;
    
    echo "DuckDB Version: {$version}";
} catch (\Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

### 3. Run Diagnostics

```bash
php artisan laraduck:diagnose
```

This command checks:
- FFI extension availability
- DuckDB binary compatibility
- File permissions
- Connection settings

## Docker Installation

If using Docker, add DuckDB to your Dockerfile:

```dockerfile
FROM php:8.2-fpm

# Install FFI extension
RUN docker-php-ext-configure ffi --with-ffi \
    && docker-php-ext-install ffi

# Install DuckDB (example)
RUN apt-get update && apt-get install -y \
    wget \
    && wget https://github.com/duckdb/duckdb/releases/download/v0.9.2/libduckdb-linux-amd64.zip \
    && unzip libduckdb-linux-amd64.zip -d /usr/local/lib/ \
    && rm libduckdb-linux-amd64.zip

# Your application setup...
```

## Troubleshooting

### FFI Not Available

If FFI is not available:
1. Check PHP was compiled with `--with-ffi`
2. Enable in `php.ini`: `ffi.enable=true`
3. Restart PHP-FPM/web server

### Permission Issues

```bash
# Fix storage permissions
chmod -R 775 storage
chown -R www-data:www-data storage
```

### Memory Errors

If you encounter memory errors:
1. Increase PHP memory limit
2. Reduce DuckDB memory limit
3. Use chunked processing for large datasets

### Extension Loading Failed

If extensions fail to load:
```php
// Manually load extensions
DB::connection('analytics')->statement("INSTALL 'parquet'");
DB::connection('analytics')->statement("LOAD 'parquet'");
```

## Next Steps

Now that you have Laravel DuckDB installed, proceed to the [Quick Start Guide](/getting-started/quick-start/) to build your first analytical query.