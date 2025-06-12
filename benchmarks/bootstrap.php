<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Laraduck\EloquentDuckDB\Database\DuckDBConnection;
use Laraduck\EloquentDuckDB\Connectors\DuckDBConnector;

// Create container
$container = new Container;
Container::setInstance($container);

// Create config repository
$config = new \Illuminate\Config\Repository([]);
$container->instance('config', $config);

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

// Create temporary database file for benchmarks
$tempDbPath = tempnam(sys_get_temp_dir(), 'laraduck_benchmark_') . '.duckdb';

// Add DuckDB connection configuration to config repository
$config->set('database.connections.duckdb', [
    'driver' => 'duckdb',
    'database' => $tempDbPath,
    'prefix' => '',
]);
$config->set('database.default', 'duckdb');

// Add DuckDB connection
$capsule->addConnection([
    'driver' => 'duckdb',
    'database' => $tempDbPath,
    'prefix' => '',
], 'duckdb');

// Register cleanup function
register_shutdown_function(function() use ($tempDbPath) {
    if (file_exists($tempDbPath)) {
        unlink($tempDbPath);
    }
});

// Add SQLite connection for comparison
$config->set('database.connections.sqlite', [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
]);
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
], 'sqlite');

// Try to add MySQL connection if credentials are available
if (getenv('MYSQL_HOST') && getenv('MYSQL_USER')) {
    $mysqlConfig = [
        'driver' => 'mysql',
        'host' => getenv('MYSQL_HOST') ?: 'localhost',
        'port' => getenv('MYSQL_PORT') ?: '3306',
        'database' => getenv('MYSQL_DATABASE') ?: 'laraduck_benchmark',
        'username' => getenv('MYSQL_USER') ?: 'root',
        'password' => getenv('MYSQL_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ];
    $config->set('database.connections.mysql', $mysqlConfig);
    $capsule->addConnection($mysqlConfig, 'mysql');
}

// Try to add PostgreSQL connection if credentials are available
if (getenv('PGSQL_HOST') && getenv('PGSQL_USER')) {
    $pgsqlConfig = [
        'driver' => 'pgsql',
        'host' => getenv('PGSQL_HOST') ?: 'localhost',
        'port' => getenv('PGSQL_PORT') ?: '5432',
        'database' => getenv('PGSQL_DATABASE') ?: 'laraduck_benchmark',
        'username' => getenv('PGSQL_USER') ?: 'postgres',
        'password' => getenv('PGSQL_PASSWORD') ?: '',
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
    ];
    $config->set('database.connections.pgsql', $pgsqlConfig);
    $capsule->addConnection($pgsqlConfig, 'pgsql');
}

// Set the event dispatcher used by Eloquent models
$capsule->setEventDispatcher(new Dispatcher(new Container));

// Make this Capsule instance available globally
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

// Set default connection
$capsule->getDatabaseManager()->setDefaultConnection('duckdb');

// Make DB facade available
\Illuminate\Support\Facades\Facade::setFacadeApplication($container);
$container->instance('db', $capsule->getDatabaseManager());

// Set up DB facade to use our database manager  
class DB extends \Illuminate\Support\Facades\DB {
    protected static function getFacadeAccessor()
    {
        return 'db';
    }
}