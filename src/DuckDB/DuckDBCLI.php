<?php

namespace Laraduck\EloquentDuckDB\DuckDB;

/**
 * DuckDB implementation using CLI wrapper
 */
class DuckDBCLI
{
    private string $database;
    private string $duckdbPath;
    private array $initCommands = [];
    
    public static function create(string $path = ':memory:'): self
    {
        return new self($path);
    }
    
    public function __construct(string $path = ':memory:')
    {
        $this->database = $path;
        $this->duckdbPath = $this->findDuckDBExecutable();
        
        if (!$this->duckdbPath) {
            throw new \RuntimeException('DuckDB executable not found in PATH');
        }
    }
    
    private function findDuckDBExecutable(): ?string
    {
        $result = shell_exec('which duckdb 2>/dev/null');
        return $result ? trim($result) : null;
    }
    
    public function query(string $sql): CLIResult
    {
        $sql = str_replace('"', '\"', $sql);
        $cmd = sprintf('%s %s -csv -c "%s" 2>&1', 
            escapeshellarg($this->duckdbPath),
            $this->database === ':memory:' ? '' : escapeshellarg($this->database),
            $sql
        );
        
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException("DuckDB command failed with code $returnCode: " . implode("\n", $output));
        }
        
        $outputStr = implode("\n", $output);
        
        if (strpos($outputStr, 'Error:') !== false) {
            throw new \RuntimeException("Query failed: " . $outputStr);
        }
        
        return new CLIResult($outputStr);
    }
    
    public function preparedStatement(string $sql): CLIPreparedStatement
    {
        return new CLIPreparedStatement($this, $sql);
    }
}

class CLIResult
{
    private array $rows = [];
    private int $currentRow = 0;
    private array $columns = [];
    
    public function __construct(?string $csvOutput)
    {
        if ($csvOutput === null || empty(trim($csvOutput))) {
            return;
        }
        
        $lines = explode("\n", trim($csvOutput));
        if (count($lines) > 0) {
            // First line is headers
            $this->columns = str_getcsv($lines[0], ',', '"', '\\');
            
            // Rest are data rows
            for ($i = 1; $i < count($lines); $i++) {
                if (!empty(trim($lines[$i]))) {
                    $values = str_getcsv($lines[$i], ',', '"', '\\');
                    $row = [];
                    foreach ($this->columns as $idx => $col) {
                        $value = $values[$idx] ?? null;
                        // Convert string "NULL" to actual null
                        if ($value === 'NULL') {
                            $value = null;
                        }
                        $row[$col] = $value;
                    }
                    $this->rows[] = $row;
                }
            }
        }
    }
    
    public function fetchRow(): ?array
    {
        if ($this->currentRow >= count($this->rows)) {
            return null;
        }
        
        return $this->rows[$this->currentRow++];
    }
    
    public function rowCount(): int
    {
        return count($this->rows);
    }
    
    public function columnCount(): int
    {
        return count($this->columns);
    }
}

class CLIPreparedStatement
{
    private DuckDBCLI $db;
    private string $sql;
    private array $params = [];
    
    public function __construct(DuckDBCLI $db, string $sql)
    {
        $this->db = $db;
        $this->sql = $sql;
    }
    
    public function bindParam(int $index, $value): void
    {
        $this->params[$index] = $value;
    }
    
    public function execute(): CLIResult
    {
        // Replace ? placeholders with values
        $sql = $this->sql;
        $paramIndex = 1;
        
        while (($pos = strpos($sql, '?')) !== false) {
            if (!isset($this->params[$paramIndex])) {
                throw new \RuntimeException("Missing parameter $paramIndex");
            }
            
            $value = $this->params[$paramIndex];
            if ($value === null) {
                $replacement = 'NULL';
            } elseif (is_numeric($value)) {
                $replacement = $value;
            } else {
                $replacement = "'" . str_replace("'", "''", $value) . "'";
            }
            
            $sql = substr_replace($sql, $replacement, $pos, 1);
            $paramIndex++;
        }
        
        return $this->db->query($sql);
    }
}