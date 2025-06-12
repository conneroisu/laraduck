<?php

namespace Laraduck\EloquentDuckDB\Database;

use PDO;
use PDOException;
use Laraduck\EloquentDuckDB\DuckDB\PreparedStatement;
use Laraduck\EloquentDuckDB\DuckDB\Result;
use Laraduck\EloquentDuckDB\DuckDB\CLIPreparedStatement;
use Laraduck\EloquentDuckDB\DuckDB\CLIResult;
use RuntimeException;

/**
 * Statement wrapper for DuckDB prepared statements.
 * 
 * Provides PDOStatement-compatible interface with enhanced
 * type casting, error handling, and performance monitoring.
 */
class DuckDBStatementWrapper
{
    protected $statement;
    protected array $attributes;
    protected array $bindings = [];
    protected Result|CLIResult|null $result = null;
    protected int $fetchMode;
    protected array $fetchModeArgs = [];
    protected string $originalQuery;
    protected float $executionTime = 0;
    protected int $affectedRows = 0;
    protected bool $executed = false;

    public function __construct($statement, array $attributes = [], string $query = '')
    {
        $this->statement = $statement;
        $this->attributes = $attributes;
        $this->originalQuery = $query;
        $this->fetchMode = $attributes[PDO::ATTR_DEFAULT_FETCH_MODE] ?? PDO::FETCH_BOTH;
    }

    /**
     * Execute the prepared statement.
     */
    public function execute($params = null): bool
    {
        try {
            $startTime = microtime(true);
            
            if ($this->statement instanceof PreparedStatement || $this->statement instanceof CLIPreparedStatement) {
                // Bind parameters passed to execute()
                if ($params !== null) {
                    foreach ($params as $key => $value) {
                        $this->bindValue($key + 1, $value);
                    }
                }
                
                // Bind all accumulated parameters
                foreach ($this->bindings as $key => $binding) {
                    $this->statement->bindParam($key, $binding['value']);
                }
                
                $this->result = $this->statement->execute();
            } else {
                // Direct result (from query() method)
                $this->result = $this->statement;
            }
            
            $this->executionTime = microtime(true) - $startTime;
            $this->executed = true;
            
            // Update affected rows count
            if ($this->result && method_exists($this->result, 'rowCount')) {
                $this->affectedRows = $this->result->rowCount();
            }
            
            return true;
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Bind a value to a parameter.
     */
    public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
    {
        try {
            $this->bindings[$param] = [
                'value' => $this->castValueForDuckDB($value, $type),
                'type' => $type
            ];
            return true;
        } catch (\Exception $e) {
            throw new PDOException("Failed to bind value for parameter {$param}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Bind a parameter by reference.
     */
    public function bindParam($param, &$value, $type = PDO::PARAM_STR, $length = null, $options = null): bool
    {
        try {
            $this->bindings[$param] = [
                'value' => &$value,
                'type' => $type
            ];
            return true;
        } catch (\Exception $e) {
            throw new PDOException("Failed to bind parameter {$param}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch a single row from the result set.
     */
    public function fetch($mode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        if (!$this->result) {
            return false;
        }

        try {
            $fetchMode = $mode ?? $this->fetchMode;
            $row = $this->result->fetchRow();

            if (!$row) {
                return false;
            }

            return $this->processFetchMode($row, $fetchMode);
        } catch (\Exception $e) {
            throw new PDOException("Failed to fetch row: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch all rows from the result set.
     */
    public function fetchAll($mode = null, ...$args): array
    {
        if (!$this->result) {
            return [];
        }

        try {
            $fetchMode = $mode ?? $this->fetchMode;
            $rows = [];

            while ($row = $this->result->fetchRow()) {
                $rows[] = $this->processFetchMode($row, $fetchMode, $args);
            }

            return $rows;
        } catch (\Exception $e) {
            throw new PDOException("Failed to fetch all rows: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch a single column from the next row.
     */
    public function fetchColumn($column = 0)
    {
        try {
            $row = $this->fetch(PDO::FETCH_NUM);
            
            if ($row === false) {
                return false;
            }

            $value = $row[$column] ?? null;
            return $this->castDuckDBValue($value);
        } catch (\Exception $e) {
            throw new PDOException("Failed to fetch column {$column}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch the next row as an object.
     */
    public function fetchObject($className = null, $constructorArgs = null)
    {
        $className = $className ?? 'stdClass';
        
        try {
            $row = $this->fetch(PDO::FETCH_ASSOC);
            
            if ($row === false) {
                return false;
            }

            if ($className === 'stdClass') {
                return (object) $row;
            }

            $object = $constructorArgs 
                ? new $className(...$constructorArgs)
                : new $className();

            foreach ($row as $key => $value) {
                $object->$key = $value;
            }

            return $object;
        } catch (\Exception $e) {
            throw new PDOException("Failed to fetch object: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the number of rows affected by the last statement.
     */
    public function rowCount(): int
    {
        return $this->affectedRows;
    }

    /**
     * Get the number of columns in the result set.
     */
    public function columnCount(): int
    {
        return $this->result ? $this->result->columnCount() : 0;
    }

    /**
     * Set the default fetch mode.
     */
    public function setFetchMode($mode, ...$args): bool
    {
        $this->fetchMode = $mode;
        $this->fetchModeArgs = $args;
        return true;
    }

    /**
     * Close the cursor and free the result set.
     */
    public function closeCursor(): bool
    {
        $this->result = null;
        return true;
    }

    /**
     * Get metadata for a column.
     */
    public function getColumnMeta($column): array
    {
        // DuckDB doesn't provide detailed column metadata through CLI
        // This is a basic implementation
        return [
            'name' => "column_{$column}",
            'table' => '',
            'len' => -1,
            'precision' => 0,
            'pdo_type' => PDO::PARAM_STR,
            'driver:decl_type' => 'VARCHAR',
            'flags' => [],
        ];
    }

    /**
     * Get the execution time of the last statement.
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * Get the original query string.
     */
    public function getQuery(): string
    {
        return $this->originalQuery;
    }

    /**
     * Check if the statement has been executed.
     */
    public function isExecuted(): bool
    {
        return $this->executed;
    }

    /**
     * Get performance information.
     */
    public function getPerformanceInfo(): array
    {
        return [
            'execution_time' => $this->executionTime,
            'row_count' => $this->rowCount(),
            'column_count' => $this->columnCount(),
            'memory_usage' => memory_get_usage(true),
            'query' => $this->originalQuery,
        ];
    }

    /**
     * Process row data according to fetch mode.
     */
    protected function processFetchMode($row, $mode, $args = [])
    {
        // Convert DuckDB values to PHP types
        $typeCastRow = [];
        foreach ($row as $key => $value) {
            $typeCastRow[$key] = $this->castDuckDBValue($value);
        }
        
        switch ($mode) {
            case PDO::FETCH_ASSOC:
                return $typeCastRow;
                
            case PDO::FETCH_NUM:
                return array_values($typeCastRow);
                
            case PDO::FETCH_BOTH:
                return array_merge($typeCastRow, array_values($typeCastRow));
                
            case PDO::FETCH_OBJ:
                return (object) $typeCastRow;
                
            case PDO::FETCH_CLASS:
                $className = $this->fetchModeArgs[0] ?? $args[0] ?? 'stdClass';
                $constructorArgs = $this->fetchModeArgs[1] ?? $args[1] ?? null;
                
                if ($className === 'stdClass') {
                    return (object) $typeCastRow;
                }
                
                $object = $constructorArgs 
                    ? new $className(...$constructorArgs)
                    : new $className();
                
                foreach ($typeCastRow as $key => $value) {
                    $object->$key = $value;
                }
                return $object;
                
            case PDO::FETCH_INTO:
                $object = $this->fetchModeArgs[0] ?? $args[0] ?? new \stdClass();
                foreach ($typeCastRow as $key => $value) {
                    $object->$key = $value;
                }
                return $object;
                
            case PDO::FETCH_COLUMN:
                $columnIndex = $this->fetchModeArgs[0] ?? $args[0] ?? 0;
                $values = array_values($typeCastRow);
                return $values[$columnIndex] ?? null;
                
            case PDO::FETCH_KEY_PAIR:
                $values = array_values($typeCastRow);
                return count($values) >= 2 ? [$values[0] => $values[1]] : [];
                
            default:
                return $typeCastRow;
        }
    }

    /**
     * Cast DuckDB value to appropriate PHP type.
     */
    protected function castDuckDBValue($value)
    {
        // Handle NULL strings from DuckDB CLI
        if ($value === 'NULL' || $value === null) {
            return null;
        }
        
        // Handle boolean values
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        
        // Cast numeric strings to appropriate types
        if (is_string($value) && is_numeric($value)) {
            // Check if it's an integer
            if (ctype_digit($value) || (substr($value, 0, 1) === '-' && ctype_digit(substr($value, 1)))) {
                $intValue = (int) $value;
                // Check for integer overflow
                if ((string) $intValue === $value) {
                    return $intValue;
                }
            }
            // Cast to float for decimal numbers
            return (float) $value;
        }
        
        // Handle date/time strings if needed
        if (is_string($value) && $this->isDateTimeString($value)) {
            try {
                return new \DateTime($value);
            } catch (\Exception $e) {
                // If parsing fails, return as string
                return $value;
            }
        }
        
        return $value;
    }

    /**
     * Cast PHP value for DuckDB compatibility.
     */
    protected function castValueForDuckDB($value, $type): mixed
    {
        switch ($type) {
            case PDO::PARAM_NULL:
                return null;
                
            case PDO::PARAM_BOOL:
                return (bool) $value;
                
            case PDO::PARAM_INT:
                return (int) $value;
                
            case PDO::PARAM_STR:
            default:
                if ($value instanceof \DateTime) {
                    return $value->format('Y-m-d H:i:s');
                }
                return (string) $value;
        }
    }

    /**
     * Check if a string appears to be a date/time value.
     */
    protected function isDateTimeString(string $value): bool
    {
        // Simple check for common date/time patterns
        return preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?/', $value) === 1;
    }

    /**
     * Handle exceptions and convert to PDOException.
     */
    protected function handleException(\Exception $e): never
    {
        $message = $e->getMessage();
        
        // Enhance error messages for common issues
        if (str_contains($message, 'parameter')) {
            $message = "Parameter binding error: {$message}. Check parameter types and values.";
        } elseif (str_contains($message, 'fetch')) {
            $message = "Fetch error: {$message}. Ensure query was executed successfully.";
        }
        
        throw new PDOException($message, (int) $e->getCode(), $e);
    }

    /**
     * Debug method to get current binding information.
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}