<?php

namespace Laraduck\EloquentDuckDB\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laraduck\EloquentDuckDB\Database\DuckDBConnection;

class DuckDBInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'duckdb:info {connection?}';

    /**
     * The console command description.
     */
    protected $description = 'Display DuckDB connection information and database status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connectionName = $this->argument('connection') ?? $this->getDefaultDuckDBConnection();

        if (!$connectionName) {
            $this->error('No DuckDB connections found in configuration');
            return self::FAILURE;
        }

        try {
            $connection = DB::connection($connectionName);
            
            if (!$connection instanceof DuckDBConnection) {
                $this->error("Connection '{$connectionName}' is not a DuckDB connection");
                return self::FAILURE;
            }

            $this->displayConnectionInfo($connection, $connectionName);
            $this->displayDatabaseInfo($connection);
            $this->displayMemoryInfo($connection);
            $this->displayExtensions($connection);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to connect to DuckDB: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Display connection information
     */
    private function displayConnectionInfo(DuckDBConnection $connection, string $connectionName): void
    {
        $config = $connection->getConfig();
        
        $this->info("DuckDB Connection Information");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $this->table(['Property', 'Value'], [
            ['Connection Name', $connectionName],
            ['Database Path', $config['database'] ?? 'N/A'],
            ['Driver', $config['driver'] ?? 'N/A'],
            ['Read Only', ($config['read_only'] ?? false) ? 'Yes' : 'No'],
            ['Memory Limit', $config['memory_limit'] ?? 'Default'],
            ['Thread Count', $config['threads'] ?? 'Default'],
        ]);
    }

    /**
     * Display database information
     */
    private function displayDatabaseInfo(DuckDBConnection $connection): void
    {
        $this->line("\nDatabase Information");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            // Get DuckDB version
            $version = $connection->selectOne('SELECT version() as version');
            $this->line("Version: {$version->version}");

            // Get database size if it's a file database
            $config = $connection->getConfig();
            if (isset($config['database']) && $config['database'] !== ':memory:' && file_exists($config['database'])) {
                $size = filesize($config['database']);
                $this->line("Database Size: " . $this->formatBytes($size));
            }

            // Get table count
            $tables = $connection->select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'main'");
            $this->line("Tables: {$tables[0]->count}");

            // Get view count
            $views = $connection->select("SELECT COUNT(*) as count FROM information_schema.views WHERE table_schema = 'main'");
            $this->line("Views: {$views[0]->count}");

        } catch (\Exception $e) {
            $this->warn("Could not retrieve database information: {$e->getMessage()}");
        }
    }

    /**
     * Display memory information
     */
    private function displayMemoryInfo(DuckDBConnection $connection): void
    {
        $this->line("\nMemory Usage");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            $memoryInfo = $connection->select("PRAGMA memory_limit");
            if (!empty($memoryInfo)) {
                $this->line("Memory Limit: {$memoryInfo[0]->memory_limit}");
            }

            $tempDir = $connection->select("PRAGMA temp_directory");
            if (!empty($tempDir)) {
                $this->line("Temp Directory: {$tempDir[0]->temp_directory}");
            }

        } catch (\Exception $e) {
            $this->warn("Could not retrieve memory information: {$e->getMessage()}");
        }
    }

    /**
     * Display loaded extensions
     */
    private function displayExtensions(DuckDBConnection $connection): void
    {
        $this->line("\nLoaded Extensions");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            $extensions = $connection->select("SELECT extension_name, loaded FROM duckdb_extensions() WHERE loaded = true ORDER BY extension_name");
            
            if (empty($extensions)) {
                $this->line("No extensions loaded");
            } else {
                foreach ($extensions as $ext) {
                    $this->line("✓ {$ext->extension_name}");
                }
            }

        } catch (\Exception $e) {
            $this->warn("Could not retrieve extension information: {$e->getMessage()}");
        }
    }

    /**
     * Get the default DuckDB connection name
     */
    private function getDefaultDuckDBConnection(): ?string
    {
        $connections = config('database.connections', []);
        
        foreach ($connections as $name => $config) {
            if (($config['driver'] ?? null) === 'duckdb') {
                return $name;
            }
        }

        return null;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}