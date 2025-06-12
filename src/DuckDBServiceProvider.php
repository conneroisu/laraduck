<?php

namespace Laraduck\EloquentDuckDB;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use Laraduck\EloquentDuckDB\Connectors\DuckDBConnector;
use Laraduck\EloquentDuckDB\Database\DuckDBConnection;
use Laraduck\EloquentDuckDB\Console\Commands\DuckDBInfoCommand;
use Laraduck\EloquentDuckDB\Console\Commands\DuckDBFileCommand;
use Laraduck\EloquentDuckDB\Console\Commands\DuckDBOptimizeCommand;

class DuckDBServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/duckdb.php', 'database.connections.duckdb');

        Connection::resolverFor('duckdb', function ($connection, $database, $prefix, $config) {
            return new DuckDBConnection($connection, $database, $prefix, $config);
        });

        $this->app['db']->extend('duckdb', function ($config, $name) {
            $config['name'] = $name;
            
            $connector = new DuckDBConnector();
            $connection = $connector->connect($config);
            
            return new DuckDBConnection($connection, $config['database'], $config['prefix'] ?? '', $config);
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/duckdb.php' => config_path('duckdb.php'),
            ], 'duckdb-config');

            $this->commands([
                DuckDBInfoCommand::class,
                DuckDBFileCommand::class,
                DuckDBOptimizeCommand::class,
            ]);
        }
    }

    public function provides()
    {
        return [
            'db.connector.duckdb',
        ];
    }
}