<?php

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;
use Laraduck\EloquentDuckDB\DuckDBServiceProvider;
use Laraduck\EloquentDuckDB\Connectors\DuckDBConnector;
use Laraduck\EloquentDuckDB\Database\DuckDBConnection;

// Create container
$container = new Container;
Container::setInstance($container);

// Setup config service with database connections array
$config = new Config([
    'database' => [
        'connections' => []
    ]
]);
$container->instance('config', $config);

// Setup Eloquent with our container
$capsule = new Capsule($container);

// Register the database manager in container
$container->instance('db', $capsule->getDatabaseManager());

// Register service provider
$provider = new DuckDBServiceProvider($container);
$provider->register();

// Manually extend Capsule's database manager with DuckDB driver
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

// Add MySQL connection for comparison (optional)
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