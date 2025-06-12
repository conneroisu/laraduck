<?php

namespace Laraduck\EloquentDuckDB\Database;

use PDO;
use PDOException;
use PDOStatement;
use Laraduck\EloquentDuckDB\DuckDB\DuckDB;
use Laraduck\EloquentDuckDB\DuckDB\DuckDBCLI;

class DuckDBPDOWrapper
{
    protected DuckDB|DuckDBCLI $duckdb;
    protected array $attributes = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    public function __construct(DuckDB|DuckDBCLI $duckdb)
    {
        $this->duckdb = $duckdb;
    }

    public function prepare($statement, $options = []): DuckDBStatementWrapper
    {
        try {
            $preparedStatement = $this->duckdb->preparedStatement($statement);
            return new DuckDBStatementWrapper($preparedStatement, $this->attributes);
        } catch (\Exception $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function query($statement): DuckDBStatementWrapper
    {
        try {
            $result = $this->duckdb->query($statement);
            return new DuckDBStatementWrapper($result, $this->attributes);
        } catch (\Exception $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function exec($statement): int
    {
        try {
            $this->duckdb->query($statement);
            return 0;
        } catch (\Exception $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function lastInsertId($name = null): string
    {
        return '0';
    }

    public function beginTransaction(): bool
    {
        try {
            $this->duckdb->query('BEGIN TRANSACTION');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function commit(): bool
    {
        try {
            $this->duckdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function rollBack(): bool
    {
        try {
            $this->duckdb->query('ROLLBACK');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function inTransaction(): bool
    {
        return false;
    }

    public function getAttribute($attribute)
    {
        return $this->attributes[$attribute] ?? null;
    }

    public function setAttribute($attribute, $value): bool
    {
        $this->attributes[$attribute] = $value;
        return true;
    }

    public function quote($string, $type = PDO::PARAM_STR): string
    {
        if ($type === PDO::PARAM_INT) {
            return (string) $string;
        }
        
        return "'" . str_replace("'", "''", $string) . "'";
    }

    public function getDuckDB(): DuckDB|DuckDBCLI
    {
        return $this->duckdb;
    }
}