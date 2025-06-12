<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DuckDB Database Connection Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your DuckDB connection for your application.
    | DuckDB is an embedded OLAP database designed for analytical workloads.
    |
    */

    'driver' => 'duckdb',
    'database' => env('DUCKDB_DATABASE', ':memory:'),
    'read_only' => env('DUCKDB_READ_ONLY', false),
    'threads' => env('DUCKDB_THREADS', null),
    'memory_limit' => env('DUCKDB_MEMORY_LIMIT', '4GB'),
    'max_memory' => env('DUCKDB_MAX_MEMORY', '80%'),
    'temp_directory' => env('DUCKDB_TEMP_DIRECTORY', null),
    'access_mode' => env('DUCKDB_ACCESS_MODE', 'automatic'), // automatic, read_only, read_write
    
    /*
    |--------------------------------------------------------------------------
    | DuckDB Extensions
    |--------------------------------------------------------------------------
    |
    | Extensions to automatically install and load when connecting.
    | Available extensions: httpfs, parquet, json, csv, xlsx, icu, fts, spatial
    |
    */
    'extensions' => [
        'httpfs',      // For remote file access (S3, HTTP, etc.)
        'parquet',     // For Parquet file support
        'json',        // For JSON processing
        'csv',         // For CSV files
        'icu',         // For internationalization
        'fts',         // For full-text search
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | DuckDB-specific performance optimization settings.
    |
    */
    'performance' => [
        'enable_optimizer' => env('DUCKDB_ENABLE_OPTIMIZER', true),
        'enable_profiling' => env('DUCKDB_ENABLE_PROFILING', false),
        'enable_progress_bar' => env('DUCKDB_ENABLE_PROGRESS_BAR', false),
        'preserve_insertion_order' => env('DUCKDB_PRESERVE_INSERTION_ORDER', true),
        'experimental_parallel_csv' => env('DUCKDB_EXPERIMENTAL_PARALLEL_CSV', true),
        'force_compression' => env('DUCKDB_FORCE_COMPRESSION', 'auto'), // auto, uncompressed, rle, dictionary, pfor, bitpacking, fsst
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cloud Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for querying files from cloud storage providers.
    |
    */
    'cloud_storage' => [
        // AWS S3 Configuration
        's3_region' => env('AWS_DEFAULT_REGION'),
        's3_access_key_id' => env('AWS_ACCESS_KEY_ID'),
        's3_secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        's3_endpoint' => env('AWS_ENDPOINT'),
        's3_use_ssl' => env('AWS_USE_SSL', true),
        's3_url_style' => env('AWS_URL_STYLE', 'vhost'), // vhost, path
        
        // Azure Blob Storage Configuration
        'azure_account_name' => env('AZURE_ACCOUNT_NAME'),
        'azure_account_key' => env('AZURE_ACCOUNT_KEY'),
        'azure_connection_string' => env('AZURE_CONNECTION_STRING'),
        
        // Google Cloud Storage Configuration
        'gcs_access_key_id' => env('GCS_ACCESS_KEY_ID'),
        'gcs_secret' => env('GCS_SECRET'),
        'gcs_project_id' => env('GCS_PROJECT_ID'),
        
        // Generic HTTP settings
        'http_timeout' => env('DUCKDB_HTTP_TIMEOUT', 30000), // milliseconds
        'http_retries' => env('DUCKDB_HTTP_RETRIES', 3),
        'http_retry_wait_ms' => env('DUCKDB_HTTP_RETRY_WAIT_MS', 100),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | File Format Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for file format handling.
    |
    */
    'file_formats' => [
        'csv' => [
            'auto_detect' => true,
            'header' => true,
            'delimiter' => ',',
            'quote' => '"',
            'escape' => '"',
            'null_str' => '',
            'skip_rows' => 0,
            'max_line_size' => 2097152, // 2MB
        ],
        'parquet' => [
            'binary_as_string' => false,
            'file_row_number' => false,
            'compression' => 'snappy', // uncompressed, snappy, gzip, zstd
        ],
        'json' => [
            'auto_detect' => true,
            'format' => 'auto', // auto, unstructured, newline_delimited, array
            'maximum_depth' => -1,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Query Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for analytical query caching.
    |
    */
    'cache' => [
        'enabled' => env('DUCKDB_CACHE_ENABLED', true),
        'default_ttl' => env('DUCKDB_CACHE_TTL', 3600), // 1 hour
        'max_cache_size' => env('DUCKDB_MAX_CACHE_SIZE', '1GB'),
        'cache_on_write' => env('DUCKDB_CACHE_ON_WRITE', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Batch Operation Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for bulk operations and analytical processing.
    |
    */
    'batch' => [
        'default_chunk_size' => env('DUCKDB_DEFAULT_CHUNK_SIZE', 10000),
        'max_chunk_size' => env('DUCKDB_MAX_CHUNK_SIZE', 100000),
        'parallel_processing' => env('DUCKDB_PARALLEL_PROCESSING', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Additional Connection Examples
    |--------------------------------------------------------------------------
    |
    | You can define these in your main database config as needed.
    |
    */
    
    // 'analytics' => [
    //     'driver' => 'duckdb',
    //     'database' => env('DUCKDB_ANALYTICS_DATABASE', storage_path('analytics.duckdb')),
    //     'read_only' => false,
    //     'memory_limit' => '8GB',
    //     'extensions' => ['httpfs', 'parquet', 'json', 'csv', 'icu', 'fts'],
    // ],
    
    // 'readonly' => [
    //     'driver' => 'duckdb',
    //     'database' => env('DUCKDB_READONLY_DATABASE', storage_path('data.duckdb')),
    //     'read_only' => true,
    //     'memory_limit' => '2GB',
    //     'extensions' => ['parquet', 'json', 'csv'],
    // ],
];