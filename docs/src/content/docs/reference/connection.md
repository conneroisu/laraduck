---
title: Connection Configuration
description: DuckDB connection configuration reference for LaraDuck
---

## Connection Configuration

LaraDuck uses Laravel's database configuration system. Configure your DuckDB connection in `config/database.php`:

```php
'connections' => [
    'duckdb' => [
        'driver' => 'duckdb',
        'database' => storage_path('database/duck.db'),
        'read_only' => false,
        'memory_limit' => '512MB',
        'threads' => 4,
    ],
],
```

## Configuration Options

### Basic Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `driver` | string | `'duckdb'` | Database driver (must be 'duckdb') |
| `database` | string | `:memory:` | Path to database file or `:memory:` |
| `read_only` | bool | `false` | Open database in read-only mode |

### Performance Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `memory_limit` | string | `'256MB'` | Memory limit for query execution |
| `threads` | int | `null` | Number of threads (null = auto) |
| `temp_directory` | string | `null` | Directory for temporary files |

### Advanced Options

```php
'duckdb' => [
    'driver' => 'duckdb',
    'database' => storage_path('database/duck.db'),
    'options' => [
        'access_mode' => 'READ_WRITE',
        'checkpoint_wal_size' => '1GB',
        'use_temporary_directory' => true,
        'temporary_directory' => '/tmp/duckdb',
        'collation' => 'NOCASE',
        'default_order' => 'DESC',
        'enable_external_access' => true,
        'enable_object_cache' => true,
        'maximum_object_cache_size' => '100MB',
    ],
],
```

## Multiple Connections

```php
'connections' => [
    'duckdb_analytics' => [
        'driver' => 'duckdb',
        'database' => storage_path('analytics.db'),
        'memory_limit' => '1GB',
    ],
    'duckdb_readonly' => [
        'driver' => 'duckdb',
        'database' => storage_path('analytics.db'),
        'read_only' => true,
    ],
],
```

## In-Memory Database

```php
'duckdb_memory' => [
    'driver' => 'duckdb',
    'database' => ':memory:',
    'memory_limit' => '2GB',
],
```

## Connection Usage

```php
// Use specific connection
$results = DB::connection('duckdb')->select('SELECT * FROM users');

// Set connection on model
class AnalyticsModel extends DuckModel
{
    protected $connection = 'duckdb_analytics';
}

// Dynamic connection
$model = new AnalyticsModel();
$model->setConnection('duckdb_readonly');
```