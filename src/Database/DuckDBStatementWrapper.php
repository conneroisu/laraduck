<?php

namespace Laraduck\EloquentDuckDB\Database;

use PDO;
use PDOException;
use Laraduck\EloquentDuckDB\DuckDB\PreparedStatement;
use Laraduck\EloquentDuckDB\DuckDB\Result;
use Laraduck\EloquentDuckDB\DuckDB\CLIPreparedStatement;
use Laraduck\EloquentDuckDB\DuckDB\CLIResult;

class DuckDBStatementWrapper
{
    protected $statement;
    protected array $attributes;
    protected array $bindings = [];
    protected Result|CLIResult|null $result = null;
    protected int $fetchMode;
    protected array $fetchModeArgs = [];

    public function __construct($statement, array $attributes = [])
    {
        $this->statement = $statement;
        $this->attributes = $attributes;
        $this->fetchMode = $attributes[PDO::ATTR_DEFAULT_FETCH_MODE] ?? PDO::FETCH_BOTH;
    }

    public function execute($params = null): bool
    {
        try {
            if ($this->statement instanceof PreparedStatement || $this->statement instanceof CLIPreparedStatement) {
                if ($params !== null) {
                    foreach ($params as $key => $value) {
                        $this->bindValue($key + 1, $value);
                    }
                }
                
                foreach ($this->bindings as $key => $binding) {
                    $this->statement->bindParam($key, $binding['value']);
                }
                
                $this->result = $this->statement->execute();
            } else {
                $this->result = $this->statement;
            }
            
            return true;
        } catch (\Exception $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
    {
        $this->bindings[$param] = ['value' => $value, 'type' => $type];
        return true;
    }

    public function bindParam($param, &$value, $type = PDO::PARAM_STR, $length = null, $options = null): bool
    {
        $this->bindings[$param] = ['value' => &$value, 'type' => $type];
        return true;
    }

    public function fetch($mode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        if (!$this->result) {
            return false;
        }

        $fetchMode = $mode ?? $this->fetchMode;
        $row = $this->result->fetchRow();

        if (!$row) {
            return false;
        }

        return $this->processFetchMode($row, $fetchMode);
    }

    public function fetchAll($mode = null, ...$args): array
    {
        if (!$this->result) {
            return [];
        }

        $fetchMode = $mode ?? $this->fetchMode;
        $rows = [];

        while ($row = $this->result->fetchRow()) {
            $rows[] = $this->processFetchMode($row, $fetchMode);
        }

        return $rows;
    }

    public function fetchColumn($column = 0)
    {
        $row = $this->fetch(PDO::FETCH_NUM);
        
        if ($row === false) {
            return false;
        }

        return $row[$column] ?? null;
    }

    public function rowCount(): int
    {
        return $this->result ? $this->result->rowCount() : 0;
    }

    public function columnCount(): int
    {
        return $this->result ? $this->result->columnCount() : 0;
    }

    public function setFetchMode($mode, ...$args): bool
    {
        $this->fetchMode = $mode;
        $this->fetchModeArgs = $args;
        return true;
    }

    public function closeCursor(): bool
    {
        $this->result = null;
        return true;
    }

    protected function processFetchMode($row, $mode)
    {
        switch ($mode) {
            case PDO::FETCH_ASSOC:
                return $row;
                
            case PDO::FETCH_NUM:
                return array_values($row);
                
            case PDO::FETCH_BOTH:
                return array_merge($row, array_values($row));
                
            case PDO::FETCH_OBJ:
                return (object) $row;
                
            case PDO::FETCH_CLASS:
                $className = $this->fetchModeArgs[0] ?? 'stdClass';
                $object = new $className();
                foreach ($row as $key => $value) {
                    $object->$key = $value;
                }
                return $object;
                
            default:
                return $row;
        }
    }
}