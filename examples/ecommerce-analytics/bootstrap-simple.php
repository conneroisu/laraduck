<?php

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Laraduck\EloquentDuckDB\Database\DuckDBConnection;
use Laraduck\EloquentDuckDB\Connectors\DuckDBConnector;

// Create container
$container = new Container;
Container::setInstance($container);

// Register the duckdb driver resolver
Connection::resolverFor('duckdb', function ($connection, $database, $prefix, $config) {
    return new DuckDBConnection($connection, $database, $prefix, $config);
});

// Setup Eloquent
$capsule = new Capsule;
$capsule->setContainer($container);

// Register custom connector
$capsule->getDatabaseManager()->extend('duckdb', function ($config, $name) {
    $config['name'] = $name;
    $connector = new DuckDBConnector();
    $connection = $connector->connect($config);
    
    return new DuckDBConnection($connection, $config['database'], $config['prefix'] ?? '', $config);
});

// Add DuckDB connection
$capsule->addConnection([
    'driver' => 'duckdb',
    'database' => __DIR__ . '/data/analytics.duckdb',
    'prefix' => '',
], 'duckdb');

// Add SQLite connection for comparison (optional)
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
], 'sqlite');

// Set the event dispatcher used by Eloquent models
$capsule->setEventDispatcher(new Dispatcher(new Container));

// Make this Capsule instance available globally
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

// Set default connection
$capsule->getDatabaseManager()->setDefaultConnection('duckdb');

// Helper function for output
function output($message, $type = 'info') {
    $colors = [
        'info' => "\033[0;36m",
        'success' => "\033[0;32m",
        'warning' => "\033[0;33m",
        'error' => "\033[0;31m",
    ];
    
    $reset = "\033[0m";
    $prefix = match($type) {
        'success' => '✓',
        'error' => '✗',
        'warning' => '!',
        default => '→'
    };
    
    echo $colors[$type] . $prefix . " " . $message . $reset . PHP_EOL;
}

// Create data directory if it doesn't exist
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0777, true);
}

// Create exports directory if it doesn't exist
if (!is_dir(__DIR__ . '/exports')) {
    mkdir(__DIR__ . '/exports', 0777, true);
}