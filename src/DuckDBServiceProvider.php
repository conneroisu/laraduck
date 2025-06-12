<?php

namespace Laraduck\EloquentDuckDB;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Laraduck\EloquentDuckDB\Connectors\DuckDBConnector;
use Laraduck\EloquentDuckDB\Database\DuckDBConnection;
use Laraduck\EloquentDuckDB\Console\Commands\DuckDBInfoCommand;
use Laraduck\EloquentDuckDB\Console\Commands\DuckDBFileCommand;
use Laraduck\EloquentDuckDB\Console\Commands\DuckDBOptimizeCommand;
use Laraduck\EloquentDuckDB\Cache\AnalyticalQueryCache;

class DuckDBServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        // Merge enhanced configuration
        $this->mergeConfigurations();
        
        // Register the DuckDB connection resolver
        $this->registerConnectionResolver();
        
        // Register the DuckDB database driver
        $this->registerDatabaseDriver();
        
        // Register additional services
        $this->registerServices();
    }

    /**
     * Bootstrap the application events.
     */
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
        
        // Register macros for enhanced query functionality
        $this->registerQueryBuilderMacros();
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides()
    {
        return [
            'db.connector.duckdb',
            'duckdb.cache',
        ];
    }
    
    /**
     * Merge DuckDB configurations into Laravel's database config.
     */
    protected function mergeConfigurations()
    {
        // Only merge the default duckdb configuration to avoid conflicts
        $this->mergeConfigFrom(__DIR__ . '/Config/duckdb.php', 'database.connections.duckdb');
    }
    
    /**
     * Register the DuckDB connection resolver.
     */
    protected function registerConnectionResolver()
    {
        Connection::resolverFor('duckdb', function ($connection, $database, $prefix, $config) {
            return new DuckDBConnection($connection, $database, $prefix, $config);
        });
    }
    
    /**
     * Register the DuckDB database driver.
     */
    protected function registerDatabaseDriver()
    {
        $this->app['db']->extend('duckdb', function ($config, $name) {
            $config['name'] = $name;
            
            try {
                $connector = new DuckDBConnector();
                $connection = $connector->connect($config);
                
                return new DuckDBConnection(
                    $connection, 
                    $config['database'] ?? ':memory:', 
                    $config['prefix'] ?? '', 
                    $config
                );
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    "Failed to establish DuckDB connection '{$name}': " . $e->getMessage(),
                    0,
                    $e
                );
            }
        });
    }
    
    /**
     * Register additional services.
     */
    protected function registerServices()
    {
        // Register the analytical query cache
        $this->app->singleton('duckdb.cache', function ($app) {
            return new AnalyticalQueryCache(
                $app['cache.store'],
                config('database.connections.duckdb.cache.default_ttl', 3600)
            );
        });
        
        // Bind the cache to the container
        $this->app->bind(AnalyticalQueryCache::class, function ($app) {
            return $app['duckdb.cache'];
        });
    }
    
    /**
     * Register useful query builder macros for DuckDB.
     */
    protected function registerQueryBuilderMacros()
    {
        // Add a macro for querying Parquet files
        DB::macro('fromParquet', function ($path, $as = null) {
            $connection = DB::connection('duckdb');
            $query = $connection->query();
            
            if ($as) {
                return $query->fromRaw("'{$path}' AS {$as}");
            }
            
            return $query->fromRaw("'{$path}'");
        });
        
        // Add a macro for querying CSV files
        DB::macro('fromCsv', function ($path, $options = []) {
            $connection = DB::connection('duckdb');
            $query = $connection->query();
            
            if (empty($options)) {
                return $query->fromRaw("read_csv_auto('{$path}')");
            }
            
            $optionsString = collect($options)->map(function ($value, $key) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                return "{$key} = {$value}";
            })->implode(', ');
            
            return $query->fromRaw("read_csv('{$path}', {$optionsString})");
        });
        
        // Add a macro for querying JSON files
        DB::macro('fromJson', function ($path, $options = []) {
            $connection = DB::connection('duckdb');
            $query = $connection->query();
            
            if (empty($options)) {
                return $query->fromRaw("read_json_auto('{$path}')");
            }
            
            $optionsJson = json_encode($options);
            return $query->fromRaw("read_json('{$path}', {$optionsJson})");
        });
        
        // Add a macro for EXPLAIN ANALYZE queries
        DB::macro('explainAnalyze', function ($query) {
            $connection = DB::connection('duckdb');
            
            if (is_string($query)) {
                $sql = $query;
                $bindings = [];
            } else {
                $sql = $query->toSql();
                $bindings = $query->getBindings();
            }
            
            return $connection->select("EXPLAIN ANALYZE {$sql}", $bindings);
        });
        
        // Add a macro for getting table statistics
        DB::macro('summarizeTable', function ($table) {
            $connection = DB::connection('duckdb');
            return $connection->select("SUMMARIZE {$table}");
        });
    }
}