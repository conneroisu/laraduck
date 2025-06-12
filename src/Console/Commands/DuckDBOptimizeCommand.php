<?php

namespace Laraduck\EloquentDuckDB\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laraduck\EloquentDuckDB\Database\DuckDBConnection;

class DuckDBOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'duckdb:optimize 
                            {--connection= : DuckDB connection to use}
                            {--table= : Specific table to optimize}
                            {--analyze : Run ANALYZE on tables}
                            {--vacuum : Run VACUUM on database}
                            {--checkpoint : Run CHECKPOINT}
                            {--stats : Show optimization statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize DuckDB database performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connectionName = $this->option('connection') ?? $this->getDefaultDuckDBConnection();

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

            $this->info("Optimizing DuckDB database: {$connectionName}");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            $startTime = microtime(true);
            
            if ($this->option('stats')) {
                $this->showPreOptimizationStats($connection);
            }

            $operations = $this->getOperationsToRun();
            
            foreach ($operations as $operation) {
                $this->runOptimizationOperation($connection, $operation);
            }

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✓ Optimization completed in {$duration}ms");

            if ($this->option('stats')) {
                $this->showPostOptimizationStats($connection);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Optimization failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Get list of operations to run based on options
     */
    private function getOperationsToRun(): array
    {
        $operations = [];

        // If specific operations are requested, use those
        if ($this->option('analyze')) {
            $operations[] = 'analyze';
        }
        if ($this->option('vacuum')) {
            $operations[] = 'vacuum';
        }
        if ($this->option('checkpoint')) {
            $operations[] = 'checkpoint';
        }

        // If no specific operations requested, run all
        if (empty($operations)) {
            $operations = ['analyze', 'vacuum', 'checkpoint'];
        }

        return $operations;
    }

    /**
     * Run a specific optimization operation
     */
    private function runOptimizationOperation(DuckDBConnection $connection, string $operation): void
    {
        try {
            switch ($operation) {
                case 'analyze':
                    $this->runAnalyze($connection);
                    break;
                case 'vacuum':
                    $this->runVacuum($connection);
                    break;
                case 'checkpoint':
                    $this->runCheckpoint($connection);
                    break;
            }
        } catch (\Exception $e) {
            $this->warn("Failed to run {$operation}: {$e->getMessage()}");
        }
    }

    /**
     * Run ANALYZE operation
     */
    private function runAnalyze(DuckDBConnection $connection): void
    {
        $this->line("Running ANALYZE...");
        
        $startTime = microtime(true);
        
        if ($table = $this->option('table')) {
            // Analyze specific table
            $connection->statement("ANALYZE {$table}");
            $this->info("  ✓ Analyzed table: {$table}");
        } else {
            // Analyze all tables
            $tables = $this->getAllTables($connection);
            
            if (empty($tables)) {
                $this->line("  No tables found to analyze");
                return;
            }

            foreach ($tables as $table) {
                try {
                    $connection->statement("ANALYZE {$table->table_name}");
                    $this->line("  ✓ Analyzed: {$table->table_name}");
                } catch (\Exception $e) {
                    $this->warn("  ✗ Failed to analyze {$table->table_name}: {$e->getMessage()}");
                }
            }
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        $this->info("  ANALYZE completed in {$duration}ms");
    }

    /**
     * Run VACUUM operation
     */
    private function runVacuum(DuckDBConnection $connection): void
    {
        $this->line("Running VACUUM...");
        
        $startTime = microtime(true);
        
        try {
            $connection->statement("VACUUM");
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            $this->info("  ✓ VACUUM completed in {$duration}ms");
        } catch (\Exception $e) {
            $this->warn("  ✗ VACUUM failed: {$e->getMessage()}");
        }
    }

    /**
     * Run CHECKPOINT operation
     */
    private function runCheckpoint(DuckDBConnection $connection): void
    {
        $this->line("Running CHECKPOINT...");
        
        $startTime = microtime(true);
        
        try {
            $connection->statement("CHECKPOINT");
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            $this->info("  ✓ CHECKPOINT completed in {$duration}ms");
        } catch (\Exception $e) {
            $this->warn("  ✗ CHECKPOINT failed: {$e->getMessage()}");
        }
    }

    /**
     * Show pre-optimization statistics
     */
    private function showPreOptimizationStats(DuckDBConnection $connection): void
    {
        $this->line("\nPre-Optimization Statistics");
        $this->line("─────────────────────────────");
        
        $this->showDatabaseStats($connection);
    }

    /**
     * Show post-optimization statistics
     */
    private function showPostOptimizationStats(DuckDBConnection $connection): void
    {
        $this->line("\nPost-Optimization Statistics");
        $this->line("─────────────────────────────");
        
        $this->showDatabaseStats($connection);
    }

    /**
     * Show database statistics
     */
    private function showDatabaseStats(DuckDBConnection $connection): void
    {
        try {
            // Database size
            $config = $connection->getConfig();
            if (isset($config['database']) && $config['database'] !== ':memory:' && file_exists($config['database'])) {
                $size = filesize($config['database']);
                $this->line("Database Size: " . $this->formatBytes($size));
            }

            // Table count and row counts
            $tables = $this->getAllTables($connection);
            $totalRows = 0;
            
            $this->line("Tables:");
            foreach ($tables as $table) {
                try {
                    $count = $connection->table($table->table_name)->count();
                    $totalRows += $count;
                    $this->line("  {$table->table_name}: " . number_format($count) . " rows");
                } catch (\Exception $e) {
                    $this->line("  {$table->table_name}: Error counting rows");
                }
            }
            
            $this->line("Total Rows: " . number_format($totalRows));

            // Memory usage
            try {
                $memoryInfo = $connection->select("PRAGMA memory_usage");
                if (!empty($memoryInfo)) {
                    $this->line("Memory Usage: {$memoryInfo[0]->memory_usage}");
                }
            } catch (\Exception $e) {
                // Memory usage not available
            }

        } catch (\Exception $e) {
            $this->warn("Could not retrieve database statistics: {$e->getMessage()}");
        }
    }

    /**
     * Get all tables in the database
     */
    private function getAllTables(DuckDBConnection $connection): array
    {
        return $connection->select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'main' 
            ORDER BY table_name
        ");
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