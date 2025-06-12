<?php

namespace Laraduck\EloquentDuckDB\DuckDB;

/**
 * Real DuckDB implementation using FFI
 */
class DuckDB
{
    private \FFI $ffi;
    private \FFI\CData $database;
    private \FFI\CData $connection;
    
    private const DUCKDB_LIB_PATHS = [
        // macOS paths
        '/usr/local/lib/libduckdb.dylib',
        '/opt/homebrew/lib/libduckdb.dylib',
        './libduckdb.dylib',
        // Linux paths
        '/usr/local/lib/libduckdb.so',
        '/usr/lib/libduckdb.so',
        './libduckdb.so',
        // Windows paths
        'C:\\Program Files\\duckdb\\duckdb.dll',
        '.\\duckdb.dll',
    ];
    
    public static function create(string $path = ':memory:'): self
    {
        return new self($path);
    }
    
    public function __construct(string $path = ':memory:')
    {
        $this->initializeFFI();
        $this->openDatabase($path);
        $this->connect();
    }
    
    private function initializeFFI(): void
    {
        $libPath = $this->findLibrary();
        if (!$libPath) {
            throw new \RuntimeException(
                'DuckDB library not found. Please install DuckDB and ensure the library is in your system path. ' .
                'On macOS: brew install duckdb, on Linux: see https://duckdb.org/docs/installation'
            );
        }
        
        $header = <<<'HEADER'
typedef enum {
    DuckDBSuccess = 0,
    DuckDBError = 1
} duckdb_state;

typedef void* duckdb_database;
typedef void* duckdb_connection;
typedef void* duckdb_result;
typedef void* duckdb_prepared_statement;

// Database functions
duckdb_state duckdb_open(const char *path, duckdb_database *out_database);
duckdb_state duckdb_open_ext(const char *path, duckdb_database *out_database, void *config, char **out_error);
void duckdb_close(duckdb_database *database);
duckdb_state duckdb_connect(duckdb_database database, duckdb_connection *out_connection);
void duckdb_disconnect(duckdb_connection *connection);

// Query functions
duckdb_state duckdb_query(duckdb_connection connection, const char *query, duckdb_result *out_result);
void duckdb_destroy_result(duckdb_result *result);

// Result functions
uint64_t duckdb_row_count(duckdb_result *result);
uint64_t duckdb_column_count(duckdb_result *result);
const char *duckdb_column_name(duckdb_result *result, uint64_t col);
int duckdb_column_type(duckdb_result *result, uint64_t col);

// Value access functions
const char *duckdb_value_varchar(duckdb_result *result, uint64_t col, uint64_t row);
int32_t duckdb_value_int32(duckdb_result *result, uint64_t col, uint64_t row);
int64_t duckdb_value_int64(duckdb_result *result, uint64_t col, uint64_t row);
float duckdb_value_float(duckdb_result *result, uint64_t col, uint64_t row);
double duckdb_value_double(duckdb_result *result, uint64_t col, uint64_t row);
bool duckdb_value_boolean(duckdb_result *result, uint64_t col, uint64_t row);
bool duckdb_value_is_null(duckdb_result *result, uint64_t col, uint64_t row);

// Prepared statement functions
duckdb_state duckdb_prepare(duckdb_connection connection, const char *query, duckdb_prepared_statement *out_prepared_statement);
void duckdb_destroy_prepare(duckdb_prepared_statement *prepared_statement);
duckdb_state duckdb_bind_int32(duckdb_prepared_statement prepared_statement, uint64_t param_idx, int32_t val);
duckdb_state duckdb_bind_int64(duckdb_prepared_statement prepared_statement, uint64_t param_idx, int64_t val);
duckdb_state duckdb_bind_double(duckdb_prepared_statement prepared_statement, uint64_t param_idx, double val);
duckdb_state duckdb_bind_varchar(duckdb_prepared_statement prepared_statement, uint64_t param_idx, const char *val);
duckdb_state duckdb_bind_null(duckdb_prepared_statement prepared_statement, uint64_t param_idx);
duckdb_state duckdb_execute_prepared(duckdb_prepared_statement prepared_statement, duckdb_result *out_result);

// Error handling
const char *duckdb_result_error(duckdb_result *result);
HEADER;
        
        $this->ffi = \FFI::cdef($header, $libPath);
    }
    
    private function findLibrary(): ?string
    {
        // Check environment variable first
        $envPath = getenv('DUCKDB_LIB_PATH');
        if ($envPath && file_exists($envPath)) {
            return $envPath;
        }
        
        // Check for nix store paths dynamically
        $nixPaths = glob('/nix/store/*-duckdb-*-lib/lib/libduckdb.dylib');
        if (!empty($nixPaths)) {
            return $nixPaths[0];
        }
        
        // Also check for .so files on Linux
        $nixPathsSo = glob('/nix/store/*-duckdb-*-lib/lib/libduckdb.so');
        if (!empty($nixPathsSo)) {
            return $nixPathsSo[0];
        }
        
        // Check common paths
        foreach (self::DUCKDB_LIB_PATHS as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    private function openDatabase(string $path): void
    {
        $this->database = $this->ffi->new('duckdb_database');
        $result = $this->ffi->duckdb_open($path, \FFI::addr($this->database));
        
        if ($result !== 0) {
            throw new \RuntimeException("Failed to open DuckDB database at: {$path}");
        }
    }
    
    private function connect(): void
    {
        $this->connection = $this->ffi->new('duckdb_connection');
        $result = $this->ffi->duckdb_connect($this->database, \FFI::addr($this->connection));
        
        if ($result !== 0) {
            throw new \RuntimeException("Failed to connect to DuckDB database");
        }
    }
    
    public function query(string $sql): Result
    {
        $result = $this->ffi->new('duckdb_result');
        $state = $this->ffi->duckdb_query($this->connection, $sql, \FFI::addr($result));
        
        if ($state !== 0) {
            $error = $this->ffi->duckdb_result_error(\FFI::addr($result));
            $errorMsg = $error ? \FFI::string($error) : "Unknown error";
            $this->ffi->duckdb_destroy_result(\FFI::addr($result));
            throw new \RuntimeException("Query failed: {$errorMsg}");
        }
        
        return new Result($this->ffi, $result);
    }
    
    public function preparedStatement(string $sql): PreparedStatement
    {
        $stmt = $this->ffi->new('duckdb_prepared_statement');
        $state = $this->ffi->duckdb_prepare($this->connection, $sql, \FFI::addr($stmt));
        
        if ($state !== 0) {
            throw new \RuntimeException("Failed to prepare statement: {$sql}");
        }
        
        return new PreparedStatement($this->ffi, $stmt);
    }
    
    public function __destruct()
    {
        if (isset($this->connection)) {
            $this->ffi->duckdb_disconnect(\FFI::addr($this->connection));
        }
        
        if (isset($this->database)) {
            $this->ffi->duckdb_close(\FFI::addr($this->database));
        }
    }
}

class Result
{
    private \FFI $ffi;
    private \FFI\CData $result;
    private int $currentRow = 0;
    private int $rowCount;
    private int $columnCount;
    
    public function __construct(\FFI $ffi, \FFI\CData $result)
    {
        $this->ffi = $ffi;
        $this->result = $result;
        $this->rowCount = $this->ffi->duckdb_row_count(\FFI::addr($this->result));
        $this->columnCount = $this->ffi->duckdb_column_count(\FFI::addr($this->result));
    }
    
    public function fetchRow(): ?array
    {
        if ($this->currentRow >= $this->rowCount) {
            return null;
        }
        
        $row = [];
        for ($col = 0; $col < $this->columnCount; $col++) {
            $columnName = \FFI::string($this->ffi->duckdb_column_name(\FFI::addr($this->result), $col));
            
            if ($this->ffi->duckdb_value_is_null(\FFI::addr($this->result), $col, $this->currentRow)) {
                $row[$columnName] = null;
            } else {
                $columnType = $this->ffi->duckdb_column_type(\FFI::addr($this->result), $col);
                
                // DuckDB type constants
                $row[$columnName] = match ($columnType) {
                    1 => $this->ffi->duckdb_value_boolean(\FFI::addr($this->result), $col, $this->currentRow), // BOOLEAN
                    2 => $this->ffi->duckdb_value_int32(\FFI::addr($this->result), $col, $this->currentRow), // TINYINT
                    3 => $this->ffi->duckdb_value_int32(\FFI::addr($this->result), $col, $this->currentRow), // SMALLINT
                    4 => $this->ffi->duckdb_value_int32(\FFI::addr($this->result), $col, $this->currentRow), // INTEGER
                    5 => $this->ffi->duckdb_value_int64(\FFI::addr($this->result), $col, $this->currentRow), // BIGINT
                    10 => $this->ffi->duckdb_value_float(\FFI::addr($this->result), $col, $this->currentRow), // FLOAT
                    11 => $this->ffi->duckdb_value_double(\FFI::addr($this->result), $col, $this->currentRow), // DOUBLE
                    default => \FFI::string($this->ffi->duckdb_value_varchar(\FFI::addr($this->result), $col, $this->currentRow)),
                };
            }
        }
        
        $this->currentRow++;
        return $row;
    }
    
    public function rowCount(): int
    {
        return $this->rowCount;
    }
    
    public function columnCount(): int
    {
        return $this->columnCount;
    }
    
    public function __destruct()
    {
        $this->ffi->duckdb_destroy_result(\FFI::addr($this->result));
    }
}

class PreparedStatement
{
    private \FFI $ffi;
    private \FFI\CData $stmt;
    
    public function __construct(\FFI $ffi, \FFI\CData $stmt)
    {
        $this->ffi = $ffi;
        $this->stmt = $stmt;
    }
    
    public function bindParam(int $index, $value): void
    {
        // DuckDB uses 1-based indexing for parameters
        $paramIdx = $index - 1;
        
        if ($value === null) {
            $this->ffi->duckdb_bind_null($this->stmt, $paramIdx);
        } elseif (is_int($value)) {
            if ($value >= -2147483648 && $value <= 2147483647) {
                $this->ffi->duckdb_bind_int32($this->stmt, $paramIdx, $value);
            } else {
                $this->ffi->duckdb_bind_int64($this->stmt, $paramIdx, $value);
            }
        } elseif (is_float($value)) {
            $this->ffi->duckdb_bind_double($this->stmt, $paramIdx, $value);
        } elseif (is_bool($value)) {
            $this->ffi->duckdb_bind_int32($this->stmt, $paramIdx, $value ? 1 : 0);
        } else {
            $this->ffi->duckdb_bind_varchar($this->stmt, $paramIdx, (string)$value);
        }
    }
    
    public function execute(): Result
    {
        $result = $this->ffi->new('duckdb_result');
        $state = $this->ffi->duckdb_execute_prepared($this->stmt, \FFI::addr($result));
        
        if ($state !== 0) {
            $error = $this->ffi->duckdb_result_error(\FFI::addr($result));
            $errorMsg = $error ? \FFI::string($error) : "Unknown error";
            $this->ffi->duckdb_destroy_result(\FFI::addr($result));
            throw new \RuntimeException("Prepared statement execution failed: {$errorMsg}");
        }
        
        return new Result($this->ffi, $result);
    }
    
    public function __destruct()
    {
        $this->ffi->duckdb_destroy_prepare(\FFI::addr($this->stmt));
    }
}