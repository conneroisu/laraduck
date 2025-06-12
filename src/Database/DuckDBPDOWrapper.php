<?php

namespace Laraduck\EloquentDuckDB\Database;

use PDO;
use PDOException;
use PDOStatement;
use Laraduck\EloquentDuckDB\DuckDB\DuckDB;
use Laraduck\EloquentDuckDB\DuckDB\DuckDBCLI;
use RuntimeException;

/**
 * PDO wrapper for DuckDB connections.
 * 
 * Provides PDO-compatible interface for DuckDB with enhanced
 * analytical capabilities and optimized error handling.
 */
class DuckDBPDOWrapper
{
    protected DuckDB|DuckDBCLI $duckdb;
    protected array $attributes = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    protected bool $inTransaction = false;
    protected array $transactionStack = [];
    protected array $queryLog = [];
    protected bool $enableQueryLogging = false;
    protected float $lastQueryTime = 0;

    public function __construct(DuckDB|DuckDBCLI $duckdb)
    {
        $this->duckdb = $duckdb;
        $this->enableQueryLogging = config('database.connections.duckdb.enable_query_log', false);
    }

    /**
     * Prepare a statement for execution.
     */
    public function prepare($statement, $options = []): DuckDBStatementWrapper
    {
        try {
            $this->logQuery($statement, [], 'prepare');
            $preparedStatement = $this->duckdb->preparedStatement($statement);
            return new DuckDBStatementWrapper($preparedStatement, $this->attributes, $statement);
        } catch (\Exception $e) {
            $this->handleDuckDBException($e, $statement);
        }
    }

    /**
     * Execute a query directly.
     */
    public function query($statement): DuckDBStatementWrapper
    {
        try {
            $startTime = microtime(true);
            $this->logQuery($statement, [], 'query');
            
            $result = $this->duckdb->query($statement);
            $this->lastQueryTime = microtime(true) - $startTime;
            
            return new DuckDBStatementWrapper($result, $this->attributes, $statement);
        } catch (\Exception $e) {
            $this->handleDuckDBException($e, $statement);
        }
    }

    /**
     * Execute a statement and return the number of affected rows.
     */
    public function exec($statement): int
    {
        try {
            $startTime = microtime(true);
            $this->logQuery($statement, [], 'exec');
            
            $result = $this->duckdb->query($statement);
            $this->lastQueryTime = microtime(true) - $startTime;
            
            // For DuckDB, we'll estimate affected rows based on the result
            if (method_exists($result, 'rowCount')) {
                return $result->rowCount();
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->handleDuckDBException($e, $statement);
        }
    }

    /**
     * Get the last insert ID (DuckDB doesn't support auto-increment traditionally).
     */
    public function lastInsertId($name = null): string
    {
        // DuckDB doesn't have traditional auto-increment, but we can try to get the last rowid
        try {
            if ($name) {
                $result = $this->query("SELECT MAX({$name}) as last_id FROM {$name}");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                return (string) ($row['last_id'] ?? '0');
            }
            return '0';
        } catch (\Exception $e) {
            return '0';
        }
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): bool
    {
        try {
            if ($this->inTransaction) {
                // DuckDB supports nested transactions with savepoints
                $savepoint = 'sp_' . count($this->transactionStack);
                $this->duckdb->query("SAVEPOINT {$savepoint}");
                $this->transactionStack[] = $savepoint;
            } else {
                $this->duckdb->query('BEGIN TRANSACTION');
                $this->inTransaction = true;
            }
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to begin transaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Commit a transaction.
     */
    public function commit(): bool
    {
        try {
            if (!empty($this->transactionStack)) {
                // Release savepoint
                $savepoint = array_pop($this->transactionStack);
                $this->duckdb->query("RELEASE SAVEPOINT {$savepoint}");
            } else if ($this->inTransaction) {
                $this->duckdb->query('COMMIT');
                $this->inTransaction = false;
            }
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to commit transaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Roll back a transaction.
     */
    public function rollBack(): bool
    {
        try {
            if (!empty($this->transactionStack)) {
                // Rollback to savepoint
                $savepoint = array_pop($this->transactionStack);
                $this->duckdb->query("ROLLBACK TO SAVEPOINT {$savepoint}");
            } else if ($this->inTransaction) {
                $this->duckdb->query('ROLLBACK');
                $this->inTransaction = false;
            }
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to rollback transaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if currently in a transaction.
     */
    public function inTransaction(): bool
    {
        return $this->inTransaction || !empty($this->transactionStack);
    }

    /**
     * Get a PDO attribute value.
     */
    public function getAttribute($attribute)
    {
        return $this->attributes[$attribute] ?? null;
    }

    /**
     * Set a PDO attribute value.
     */
    public function setAttribute($attribute, $value): bool
    {
        $this->attributes[$attribute] = $value;
        
        // Handle special attributes
        if ($attribute === PDO::ATTR_ERRMODE && $value === PDO::ERRMODE_SILENT) {
            // Warn about silent error mode as it's not recommended for analytical workloads
            error_log('Warning: Silent error mode is not recommended for DuckDB analytical workloads');
        }
        
        return true;
    }

    /**
     * Quote a string for use in a query.
     */
    public function quote($string, $type = PDO::PARAM_STR): string
    {
        if ($string === null) {
            return 'NULL';
        }
        
        if ($type === PDO::PARAM_INT) {
            return (string) $string;
        }
        
        if ($type === PDO::PARAM_BOOL) {
            return $string ? 'true' : 'false';
        }
        
        // Enhanced string escaping for DuckDB
        $escaped = str_replace("'", "''", (string) $string);
        return "'{$escaped}'";
    }

    /**
     * Get the underlying DuckDB instance.
     */
    public function getDuckDB(): DuckDB|DuckDBCLI
    {
        return $this->duckdb;
    }
    
    /**
     * Execute a bulk copy operation for analytical workloads.
     */
    public function bulkCopy(string $table, string $filePath, array $options = []): bool
    {
        try {
            $format = $options['format'] ?? 'AUTO';
            $optionsString = '';
            
            if (!empty($options)) {
                $optionPairs = [];
                foreach ($options as $key => $value) {
                    if ($key !== 'format' && $value !== null) {
                        $optionPairs[] = "{$key} = " . $this->formatOptionValue($value);
                    }
                }
                if (!empty($optionPairs)) {
                    $optionsString = ', ' . implode(', ', $optionPairs);
                }
            }
            
            $sql = "COPY {$table} FROM '{$filePath}' (FORMAT {$format}{$optionsString})";
            $this->exec($sql);
            
            return true;
        } catch (\Exception $e) {
            $this->logError("Bulk copy failed: " . $e->getMessage());
            throw new PDOException("Bulk copy operation failed: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Export query results to a file.
     */
    public function exportQuery(string $query, string $filePath, array $bindings = [], array $options = []): bool
    {
        try {
            $format = $options['format'] ?? 'PARQUET';
            $optionsString = '';
            
            if (!empty($options)) {
                $optionPairs = [];
                foreach ($options as $key => $value) {
                    if ($key !== 'format' && $value !== null) {
                        $optionPairs[] = "{$key} = " . $this->formatOptionValue($value);
                    }
                }
                if (!empty($optionPairs)) {
                    $optionsString = ', ' . implode(', ', $optionPairs);
                }
            }
            
            // Prepare the query with bindings
            $preparedQuery = $this->prepareQueryWithBindings($query, $bindings);
            
            $sql = "COPY ({$preparedQuery}) TO '{$filePath}' (FORMAT {$format}{$optionsString})";
            $this->exec($sql);
            
            return true;
        } catch (\Exception $e) {
            $this->logError("Export query failed: " . $e->getMessage());
            throw new PDOException("Export operation failed: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Get performance information about the last query.
     */
    public function getLastQueryPerformance(): array
    {
        return [
            'execution_time' => $this->lastQueryTime,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }
    
    /**
     * Get query execution log.
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
    
    /**
     * Clear the query log.
     */
    public function clearQueryLog(): void
    {
        $this->queryLog = [];
    }
    
    /**
     * Enable or disable query logging.
     */
    public function enableQueryLogging(bool $enable = true): void
    {
        $this->enableQueryLogging = $enable;
    }
    
    /**
     * Handle DuckDB-specific exceptions and convert to PDOException.
     */
    protected function handleDuckDBException(\Exception $e, string $query = ''): never
    {
        $message = $e->getMessage();
        $code = $e->getCode();
        
        // Enhanced error messages for common DuckDB issues
        if (str_contains($message, 'Catalog Error')) {
            $message = "DuckDB Catalog Error: {$message}. Check table/column names and file paths.";
        } elseif (str_contains($message, 'Parser Error')) {
            $message = "DuckDB Parser Error: {$message}. Check SQL syntax for DuckDB compatibility.";
        } elseif (str_contains($message, 'Binder Error')) {
            $message = "DuckDB Binder Error: {$message}. Check column references and data types.";
        } elseif (str_contains($message, 'Out of Memory')) {
            $message = "DuckDB Out of Memory: {$message}. Consider increasing memory_limit or optimizing query.";
        }
        
        // Log the error for debugging
        $this->logError("DuckDB Error in query '{$query}': {$message}");
        
        throw new PDOException($message, (int) $code, $e);
    }
    
    /**
     * Log query execution for debugging and performance monitoring.
     */
    protected function logQuery(string $query, array $bindings = [], string $type = 'query'): void
    {
        if (!$this->enableQueryLogging) {
            return;
        }
        
        $this->queryLog[] = [
            'query' => $query,
            'bindings' => $bindings,
            'type' => $type,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
        ];
        
        // Limit log size to prevent memory issues
        if (count($this->queryLog) > 1000) {
            $this->queryLog = array_slice($this->queryLog, -500);
        }
    }
    
    /**
     * Log errors for debugging.
     */
    protected function logError(string $message): void
    {
        error_log("DuckDB PDO Wrapper Error: {$message}");
    }
    
    /**
     * Format option values for DuckDB commands.
     */
    protected function formatOptionValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        return "'{$value}'";
    }
    
    /**
     * Prepare a query with bindings for execution.
     */
    protected function prepareQueryWithBindings(string $query, array $bindings): string
    {
        $index = 0;
        return preg_replace_callback('/\?/', function () use ($bindings, &$index) {
            $value = $bindings[$index++] ?? null;
            return $this->quote($value);
        }, $query);
    }
}