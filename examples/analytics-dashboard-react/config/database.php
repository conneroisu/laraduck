<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_MYSQL_URL'),
            'host' => env('DB_MYSQL_HOST', '127.0.0.1'),
            'port' => env('DB_MYSQL_PORT', '3306'),
            'database' => env('DB_MYSQL_DATABASE', 'laravel'),
            'username' => env('DB_MYSQL_USERNAME', 'root'),
            'password' => env('DB_MYSQL_PASSWORD', ''),
            'unix_socket' => env('DB_MYSQL_SOCKET', ''),
            'charset' => env('DB_MYSQL_CHARSET', 'utf8mb4'),
            'collation' => env('DB_MYSQL_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'duckdb' => [
            'driver' => 'duckdb',
            'database' => env('DB_DATABASE', database_path('analytics.duckdb')),
            'read_only' => env('DB_READ_ONLY', false),
            'memory_limit' => env('DB_MEMORY_LIMIT', '4GB'),
            'threads' => env('DB_THREADS', 4),
            'extensions' => [
                // 'httpfs',  // For S3/HTTP file access
                // 'parquet', // Parquet support (built-in)
            ],
            'settings' => [
                'default_order' => 'DESC',
                'preserve_insertion_order' => false,
            ],
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];