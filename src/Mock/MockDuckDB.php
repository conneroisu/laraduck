<?php

namespace Laraduck\EloquentDuckDB\Mock;

/**
 * Mock DuckDB implementation for testing
 */
class MockDuckDB
{
    protected $database;
    protected $results = [];
    
    public static function create($database)
    {
        return new self($database);
    }
    
    public function __construct($database)
    {
        $this->database = $database;
    }
    
    public function query($sql)
    {
        // Mock implementation - just return a result object
        return new MockResult([]);
    }
    
    public function preparedStatement($sql)
    {
        return new MockPreparedStatement($sql);
    }
}

class MockResult
{
    protected $rows;
    protected $currentRow = 0;
    
    public function __construct($rows = [])
    {
        $this->rows = $rows;
    }
    
    public function fetchRow()
    {
        if ($this->currentRow >= count($this->rows)) {
            return null;
        }
        
        return $this->rows[$this->currentRow++];
    }
    
    public function rowCount()
    {
        return count($this->rows);
    }
    
    public function columnCount()
    {
        return empty($this->rows) ? 0 : count(array_keys($this->rows[0]));
    }
}

class MockPreparedStatement
{
    protected $sql;
    protected $params = [];
    
    public function __construct($sql)
    {
        $this->sql = $sql;
    }
    
    public function bindParam($index, $value)
    {
        $this->params[$index] = $value;
    }
    
    public function execute()
    {
        // Mock execution
        return new MockResult([]);
    }
}