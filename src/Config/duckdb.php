<?php

return [
    'driver' => 'duckdb',
    'database' => env('DUCKDB_DATABASE', ':memory:'),
    'read_only' => env('DUCKDB_READ_ONLY', false),
    'threads' => env('DUCKDB_THREADS', null),
    'memory_limit' => env('DUCKDB_MEMORY_LIMIT', null),
    'extensions' => [
        'httpfs',
        'parquet',
        'json',
    ],
    'settings' => [
        's3_region' => env('AWS_DEFAULT_REGION'),
        's3_access_key_id' => env('AWS_ACCESS_KEY_ID'),
        's3_secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        's3_endpoint' => env('AWS_ENDPOINT'),
        's3_use_ssl' => env('AWS_USE_SSL', true),
        'azure_account_name' => env('AZURE_ACCOUNT_NAME'),
        'azure_account_key' => env('AZURE_ACCOUNT_KEY'),
        'azure_connection_string' => env('AZURE_CONNECTION_STRING'),
        'gcs_access_key_id' => env('GCS_ACCESS_KEY_ID'),
        'gcs_secret' => env('GCS_SECRET'),
    ],
];