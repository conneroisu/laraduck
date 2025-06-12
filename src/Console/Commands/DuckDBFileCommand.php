<?php

namespace Laraduck\EloquentDuckDB\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laraduck\EloquentDuckDB\Database\DuckDBConnection;

class DuckDBFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'duckdb:file 
                            {action : Action to perform (import, export, query, inspect)}
                            {--connection= : DuckDB connection to use}
                            {--file= : File path}
                            {--format= : File format (parquet, csv, json)}
                            {--table= : Table name for import/export}
                            {--query= : SQL query for export}
                            {--limit=1000 : Limit for query operations}
                            {--compression= : Compression type (gzip, snappy, zstd)}
                            {--header : Include header for CSV files}
                            {--delimiter=, : Delimiter for CSV files}
                            {--overwrite : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Perform file operations with DuckDB (import, export, query, inspect)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
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

            return match ($action) {
                'import' => $this->handleImport($connection),
                'export' => $this->handleExport($connection),
                'query' => $this->handleQuery($connection),
                'inspect' => $this->handleInspect($connection),
                default => $this->handleInvalidAction($action),
            };

        } catch (\Exception $e) {
            $this->error("Operation failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Handle file import operation
     */
    private function handleImport(DuckDBConnection $connection): int
    {
        $file = $this->option('file');
        $table = $this->option('table');
        $format = $this->option('format');

        if (!$file) {
            $file = $this->ask('Enter file path to import');
        }

        if (!$table) {
            $table = $this->ask('Enter target table name');
        }

        if (!$format) {
            $format = $this->detectFileFormat($file);
        }

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info("Importing {$format} file: {$file}");
        $this->info("Target table: {$table}");

        $sql = $this->buildImportSQL($file, $table, $format);
        
        try {
            $startTime = microtime(true);
            $connection->statement($sql);
            $endTime = microtime(true);

            $count = $connection->table($table)->count();
            $duration = round(($endTime - $startTime) * 1000, 2);

            $this->info("✓ Imported {$count} rows in {$duration}ms");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Handle file export operation
     */
    private function handleExport(DuckDBConnection $connection): int
    {
        $file = $this->option('file');
        $table = $this->option('table');
        $query = $this->option('query');
        $format = $this->option('format');

        if (!$file) {
            $file = $this->ask('Enter output file path');
        }

        if (!$format) {
            $format = $this->detectFileFormat($file);
        }

        if (!$query && !$table) {
            $table = $this->ask('Enter table name to export (or use --query option)');
        }

        if (file_exists($file) && !$this->option('overwrite')) {
            if (!$this->confirm("File exists. Overwrite?")) {
                $this->line('Export cancelled');
                return self::SUCCESS;
            }
        }

        $sql = $this->buildExportSQL($file, $table, $query, $format);
        
        try {
            $this->info("Exporting to {$format} file: {$file}");
            
            $startTime = microtime(true);
            $connection->statement($sql);
            $endTime = microtime(true);

            $duration = round(($endTime - $startTime) * 1000, 2);
            $size = file_exists($file) ? $this->formatBytes(filesize($file)) : 'Unknown';

            $this->info("✓ Export completed in {$duration}ms");
            $this->info("✓ File size: {$size}");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Handle file query operation
     */
    private function handleQuery(DuckDBConnection $connection): int
    {
        $file = $this->option('file');
        $format = $this->option('format');
        $limit = $this->option('limit');

        if (!$file) {
            $file = $this->ask('Enter file path to query');
        }

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        if (!$format) {
            $format = $this->detectFileFormat($file);
        }

        try {
            $readFunction = $this->getReadFunction($format);
            $sql = "SELECT * FROM {$readFunction}('{$file}') LIMIT {$limit}";

            $this->info("Querying {$format} file: {$file}");
            $this->line("SQL: {$sql}");
            
            $results = $connection->select($sql);
            
            if (empty($results)) {
                $this->line('No data found');
                return self::SUCCESS;
            }

            // Convert to array for table display
            $headers = array_keys((array) $results[0]);
            $rows = array_map(fn($row) => array_values((array) $row), $results);

            $this->table($headers, $rows);
            $this->info("Showing " . count($results) . " rows (limit: {$limit})");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Query failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Handle file inspection operation
     */
    private function handleInspect(DuckDBConnection $connection): int
    {
        $file = $this->option('file');
        $format = $this->option('format');

        if (!$file) {
            $file = $this->ask('Enter file path to inspect');
        }

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        if (!$format) {
            $format = $this->detectFileFormat($file);
        }

        try {
            $this->displayFileInfo($file, $format);
            $this->displayFileSchema($connection, $file, $format);
            $this->displayFileSample($connection, $file, $format);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Inspection failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Display file information
     */
    private function displayFileInfo(string $file, string $format): void
    {
        $this->info("File Information");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $this->table(['Property', 'Value'], [
            ['File Path', $file],
            ['Format', strtoupper($format)],
            ['Size', $this->formatBytes(filesize($file))],
            ['Modified', date('Y-m-d H:i:s', filemtime($file))],
        ]);
    }

    /**
     * Display file schema
     */
    private function displayFileSchema(DuckDBConnection $connection, string $file, string $format): void
    {
        $this->line("\nFile Schema");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            $readFunction = $this->getReadFunction($format);
            $sql = "DESCRIBE SELECT * FROM {$readFunction}('{$file}')";
            
            $schema = $connection->select($sql);
            
            $schemaData = array_map(function($row) {
                return [
                    $row->column_name,
                    $row->column_type,
                    $row->null ?? 'YES',
                ];
            }, $schema);

            $this->table(['Column', 'Type', 'Nullable'], $schemaData);

        } catch (\Exception $e) {
            $this->warn("Could not retrieve schema: {$e->getMessage()}");
        }
    }

    /**
     * Display file sample data
     */
    private function displayFileSample(DuckDBConnection $connection, string $file, string $format): void
    {
        $this->line("\nSample Data (first 5 rows)");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            $readFunction = $this->getReadFunction($format);
            $sql = "SELECT * FROM {$readFunction}('{$file}') LIMIT 5";
            
            $results = $connection->select($sql);
            
            if (empty($results)) {
                $this->line('No data found');
                return;
            }

            $headers = array_keys((array) $results[0]);
            $rows = array_map(fn($row) => array_values((array) $row), $results);

            $this->table($headers, $rows);

        } catch (\Exception $e) {
            $this->warn("Could not retrieve sample data: {$e->getMessage()}");
        }
    }

    /**
     * Build import SQL statement
     */
    private function buildImportSQL(string $file, string $table, string $format): string
    {
        $readFunction = $this->getReadFunction($format);
        
        return "CREATE OR REPLACE TABLE {$table} AS SELECT * FROM {$readFunction}('{$file}')";
    }

    /**
     * Build export SQL statement
     */
    private function buildExportSQL(string $file, ?string $table, ?string $query, string $format): string
    {
        $selectClause = $query ?: "SELECT * FROM {$table}";
        $formatOptions = $this->getFormatOptions($format);
        
        return "COPY ({$selectClause}) TO '{$file}' {$formatOptions}";
    }

    /**
     * Get DuckDB read function for format
     */
    private function getReadFunction(string $format): string
    {
        return match ($format) {
            'parquet' => 'read_parquet',
            'csv' => 'read_csv_auto',
            'json' => 'read_json_auto',
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    /**
     * Get format options for COPY statement
     */
    private function getFormatOptions(string $format): string
    {
        $options = [];

        switch ($format) {
            case 'parquet':
                $options[] = 'FORMAT PARQUET';
                if ($compression = $this->option('compression')) {
                    $options[] = "COMPRESSION {$compression}";
                }
                break;

            case 'csv':
                $options[] = 'FORMAT CSV';
                if ($this->option('header')) {
                    $options[] = 'HEADER TRUE';
                }
                if ($delimiter = $this->option('delimiter')) {
                    $options[] = "DELIMITER '{$delimiter}'";
                }
                break;

            case 'json':
                $options[] = 'FORMAT JSON';
                break;
        }

        return '(' . implode(', ', $options) . ')';
    }

    /**
     * Detect file format from extension
     */
    private function detectFileFormat(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'parquet' => 'parquet',
            'csv' => 'csv',
            'json' => 'json',
            default => $this->choice('Select file format', ['parquet', 'csv', 'json'], 0),
        };
    }

    /**
     * Handle invalid action
     */
    private function handleInvalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}");
        $this->line('Available actions: import, export, query, inspect');
        return self::FAILURE;
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