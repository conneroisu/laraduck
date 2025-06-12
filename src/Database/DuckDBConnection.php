<?php

namespace Laraduck\EloquentDuckDB\Database;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar as QueryGrammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Schema\Grammars\Grammar as SchemaGrammar;
use Laraduck\EloquentDuckDB\Query\Builder as DuckDBQueryBuilder;
use Laraduck\EloquentDuckDB\Query\Grammars\DuckDBGrammar as DuckDBQueryGrammar;
use Laraduck\EloquentDuckDB\Query\Processors\DuckDBProcessor;
use Laraduck\EloquentDuckDB\Schema\Builder as DuckDBSchemaBuilder;
use Laraduck\EloquentDuckDB\Schema\Grammars\DuckDBGrammar as DuckDBSchemaGrammar;

class DuckDBConnection extends Connection
{
    protected function getDefaultQueryGrammar()
    {
        ($grammar = new DuckDBQueryGrammar)->setConnection($this);
        return $this->withTablePrefix($grammar);
    }

    protected function getDefaultSchemaGrammar()
    {
        ($grammar = new DuckDBSchemaGrammar)->setConnection($this);
        return $this->withTablePrefix($grammar);
    }

    protected function getDefaultPostProcessor()
    {
        return new DuckDBProcessor;
    }

    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new DuckDBSchemaBuilder($this);
    }

    public function query()
    {
        return new DuckDBQueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }

    public function table($table, $as = null)
    {
        return $this->query()->from($table, $as);
    }

    public function raw($value)
    {
        return $this->query()->raw($value);
    }

    public function getDuckDB()
    {
        if ($this->pdo instanceof DuckDBPDOWrapper) {
            return $this->pdo->getDuckDB();
        }

        return null;
    }
    
    /**
     * Configure the PDO prepared statement.
     *
     * @param  \PDOStatement|\Laraduck\EloquentDuckDB\Database\DuckDBStatementWrapper  $statement
     * @return \PDOStatement|\Laraduck\EloquentDuckDB\Database\DuckDBStatementWrapper
     */
    protected function prepared($statement)
    {
        // For DuckDBStatementWrapper, we don't need to do anything special
        if ($statement instanceof DuckDBStatementWrapper) {
            return $statement;
        }
        
        // For regular PDOStatement, use parent implementation
        return parent::prepared($statement);
    }

    public function statement($query, $bindings = [])
    {
        // Skip empty queries that DuckDB doesn't support (like pragma commands)
        if (empty(trim($query))) {
            return true;
        }
        
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $this->recordsHaveBeenModified();

            return $statement->execute();
        });
    }

    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            $this->recordsHaveBeenModified(
                ($count = $statement->rowCount()) > 0
            );

            return $count;
        });
    }

    public function unprepared($query)
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {
                return true;
            }

            $this->recordsHaveBeenModified(
                $change = $this->getPdo()->exec($query) !== false
            );

            return $change;
        });
    }

    /**
     * Export query results to a file with enhanced options.
     */
    public function copyTo($query, $path, $format = 'PARQUET', array $options = [])
    {
        $optionsString = collect($options)->map(function ($value, $key) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            return "{$key} = {$value}";
        })->implode(', ');

        $sql = "COPY ({$query}) TO '{$path}' (FORMAT {$format}" . 
               ($optionsString ? ", {$optionsString}" : "") . ")";

        return $this->statement($sql);
    }

    /**
     * Import data from a file with enhanced options.
     */
    public function copyFrom($table, $path, $format = 'AUTO', array $options = [])
    {
        $optionsString = collect($options)->map(function ($value, $key) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            return "{$key} = {$value}";
        })->implode(', ');

        $formatString = $format === 'AUTO' ? '' : "(FORMAT {$format}" . 
                       ($optionsString ? ", {$optionsString}" : "") . ")";

        $sql = "COPY {$table} FROM '{$path}' {$formatString}";

        return $this->statement($sql);
    }

    /**
     * Bulk insert data using DuckDB's optimized INSERT methods.
     */
    public function bulkInsert(string $table, array $data, array $options = []): int
    {
        if (empty($data)) {
            return 0;
        }

        $chunkSize = $options['chunk_size'] ?? 10000;
        $useAppender = $options['use_appender'] ?? false;
        $affectedRows = 0;

        foreach (array_chunk($data, $chunkSize) as $chunk) {
            if ($useAppender && $this->pdo instanceof DuckDBPDOWrapper) {
                // Use DuckDB's native appender for better performance
                $affectedRows += $this->bulkInsertWithAppender($table, $chunk);
            } else {
                // Fall back to batch INSERT statements
                $affectedRows += $this->bulkInsertWithBatch($table, $chunk);
            }
        }

        return $affectedRows;
    }

    /**
     * Install a DuckDB extension.
     */
    public function install($extension): bool
    {
        try {
            $this->statement("INSTALL {$extension}");
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to install extension '{$extension}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Load a DuckDB extension.
     */
    public function load($extension): bool
    {
        try {
            $this->statement("LOAD {$extension}");
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to load extension '{$extension}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set a DuckDB configuration setting.
     */
    public function setSetting($key, $value): bool
    {
        try {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $this->statement("SET {$key} = '{$value}'");
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to set setting '{$key}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a DuckDB configuration setting.
     */
    public function getSetting($key)
    {
        try {
            $result = $this->select("SELECT current_setting('{$key}') as value");
            return $result[0]->value ?? null;
        } catch (\Exception $e) {
            $this->logError("Failed to get setting '{$key}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get comprehensive database information.
     */
    public function getDatabaseInfo(): array
    {
        try {
            $version = $this->scalar('SELECT version()');
            $memoryUsage = $this->select('PRAGMA memory_usage');
            $settings = $this->select('SELECT * FROM duckdb_settings()');
            
            return [
                'version' => $version,
                'memory_usage' => $memoryUsage,
                'settings' => $settings,
                'extensions' => $this->getLoadedExtensions(),
                'tables' => $this->getAllTables(),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get all loaded extensions.
     */
    public function getLoadedExtensions(): array
    {
        try {
            return $this->select('SELECT * FROM duckdb_extensions() WHERE loaded = true');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all tables in the database.
     */
    public function getAllTables(): array
    {
        try {
            return $this->select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'main'");
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Analyze a table and return statistics.
     */
    public function analyzeTable(string $table): array
    {
        try {
            return $this->select("SELECT * FROM pragma_table_info('{$table}')");
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get table statistics using SUMMARIZE.
     */
    public function summarizeTable(string $table, array $columns = []): array
    {
        try {
            if (empty($columns)) {
                return $this->select("SUMMARIZE {$table}");
            }
            
            $columnList = implode(', ', $columns);
            return $this->select("SUMMARIZE SELECT {$columnList} FROM {$table}");
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Execute EXPLAIN ANALYZE for query performance analysis.
     */
    public function explainAnalyze(string $query, array $bindings = []): array
    {
        try {
            return $this->select("EXPLAIN ANALYZE {$query}", $bindings);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Create a temporary view for complex analytical queries.
     */
    public function createTempView(string $name, string $query, array $bindings = []): bool
    {
        try {
            $sql = "CREATE OR REPLACE TEMPORARY VIEW {$name} AS ({$query})";
            $this->statement($sql, $bindings);
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to create temp view '{$name}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Drop a temporary view.
     */
    public function dropTempView(string $name): bool
    {
        try {
            $this->statement("DROP VIEW IF EXISTS {$name}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Execute a transaction with retry logic for analytical workloads.
     */
    public function transactionWithRetry(callable $callback, int $maxRetries = 3): mixed
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                return $this->transaction($callback);
            } catch (\Exception $e) {
                $attempt++;
                
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
                
                // Wait before retry (exponential backoff)
                usleep(100000 * (2 ** $attempt)); // 0.1s, 0.2s, 0.4s
                
                $this->logError("Transaction attempt {$attempt} failed, retrying: " . $e->getMessage());
            }
        }
        
        throw new \RuntimeException('Maximum transaction retries exceeded');
    }

    /**
     * Optimize the database by running VACUUM and ANALYZE.
     */
    public function optimize(): array
    {
        $results = [];
        
        try {
            // Run VACUUM to reclaim space
            $this->statement('VACUUM');
            $results['vacuum'] = 'success';
        } catch (\Exception $e) {
            $results['vacuum'] = 'error: ' . $e->getMessage();
        }
        
        try {
            // Update table statistics
            $this->statement('ANALYZE');
            $results['analyze'] = 'success';
        } catch (\Exception $e) {
            $results['analyze'] = 'error: ' . $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Get query performance statistics.
     */
    public function getQueryPerformance(): array
    {
        if ($this->pdo instanceof DuckDBPDOWrapper) {
            return $this->pdo->getLastQueryPerformance();
        }
        
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Enable query profiling for performance analysis.
     */
    public function enableProfiling(bool $enable = true): bool
    {
        return $this->setSetting('enable_profiling', $enable);
    }

    /**
     * Get profiling output for the last query.
     */
    public function getProfilingOutput(): array
    {
        try {
            return $this->select('PRAGMA profiling_output');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Create a materialized view for frequently accessed analytical queries.
     */
    public function createMaterializedView(string $name, string $query, array $bindings = []): bool
    {
        try {
            // DuckDB doesn't have traditional materialized views, but we can create a table
            $sql = "CREATE OR REPLACE TABLE {$name} AS ({$query})";
            $this->statement($sql, $bindings);
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to create materialized view '{$name}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Refresh a materialized view.
     */
    public function refreshMaterializedView(string $name, string $query, array $bindings = []): bool
    {
        try {
            $this->statement("DROP TABLE IF EXISTS {$name}");
            return $this->createMaterializedView($name, $query, $bindings);
        } catch (\Exception $e) {
            $this->logError("Failed to refresh materialized view '{$name}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk insert using DuckDB's native appender (if available).
     */
    protected function bulkInsertWithAppender(string $table, array $data): int
    {
        // This would use DuckDB's native appender API if available through FFI
        // For now, fall back to batch insert
        return $this->bulkInsertWithBatch($table, $data);
    }

    /**
     * Bulk insert using batch INSERT statements.
     */
    protected function bulkInsertWithBatch(string $table, array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $columns = array_keys(reset($data));
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $values = implode(', ', array_fill(0, count($data), $placeholders));
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES {$values}";
        
        $bindings = [];
        foreach ($data as $row) {
            foreach ($row as $value) {
                $bindings[] = $value;
            }
        }
        
        $this->statement($sql, $bindings);
        return count($data);
    }

    /**
     * Log errors for debugging.
     */
    protected function logError(string $message): void
    {
        if (config('app.debug', false)) {
            error_log("DuckDB Connection Error: {$message}");
        }
    }

    /**
     * Override scalar method to ensure numeric values are properly cast
     */
    public function scalar($query, $bindings = [], $useReadPdo = true)
    {
        $result = $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return true;
            }

            $statement = $this->prepared(
                $this->getPdoForSelect($useReadPdo)->prepare($query)
            );

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            $result = $statement->fetchColumn();
            
            // Handle NULL strings from DuckDB CLI
            if ($result === 'NULL') {
                return null;
            }
            
            // Cast numeric strings to appropriate types for DuckDB compatibility
            if (is_string($result) && is_numeric($result)) {
                if (ctype_digit($result) || (substr($result, 0, 1) === '-' && ctype_digit(substr($result, 1)))) {
                    return (int) $result;
                }
                return (float) $result;
            }
            
            return $result;
        });

        return $result;
    }
}