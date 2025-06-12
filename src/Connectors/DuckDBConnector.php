<?php

namespace Laraduck\EloquentDuckDB\Connectors;

use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Laraduck\EloquentDuckDB\Database\DuckDBPDOWrapper;
use Laraduck\EloquentDuckDB\DuckDB\DuckDBCLI as DuckDB;

class DuckDBConnector extends Connector implements ConnectorInterface
{
    public function connect(array $config)
    {
        $database = $config['database'] ?? ':memory:';
        
        // Handle relative paths by making them absolute from Laravel's base path
        if ($database !== ':memory:' && !str_starts_with($database, '/')) {
            $database = base_path($database);
        }
        
        $duckdb = DuckDB::create($database);
        
        if (isset($config['read_only']) && $config['read_only']) {
            $duckdb->query('SET read_only = true;');
        }
        
        if (isset($config['threads'])) {
            $duckdb->query("SET threads = {$config['threads']};");
        }
        
        if (isset($config['memory_limit'])) {
            $duckdb->query("SET memory_limit = '{$config['memory_limit']}';");
        }
        
        if (isset($config['extensions'])) {
            foreach ($config['extensions'] as $extension) {
                $duckdb->query("INSTALL {$extension}; LOAD {$extension};");
            }
        }
        
        if (isset($config['settings'])) {
            foreach ($config['settings'] as $key => $value) {
                if ($value !== null) {
                    // Handle boolean values properly
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    }
                    $duckdb->query("SET {$key} = '{$value}';");
                }
            }
        }
        
        return new DuckDBPDOWrapper($duckdb);
    }
}