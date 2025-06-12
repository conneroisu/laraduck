<?php

namespace Laraduck\EloquentDuckDB\Schema;

use Closure;
use Illuminate\Database\Schema\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    public function hasTable($table)
    {
        $table = $this->connection->getTablePrefix().$table;

        return count($this->connection->select(
            $this->grammar->compileTableExists(),
            [$table]
        )) > 0;
    }

    public function hasColumn($table, $column)
    {
        return in_array(
            strtolower($column),
            array_map('strtolower', $this->getColumnListing($table))
        );
    }

    public function hasColumns($table, array $columns)
    {
        $tableColumns = array_map('strtolower', $this->getColumnListing($table));

        foreach ($columns as $column) {
            if (!in_array(strtolower($column), $tableColumns)) {
                return false;
            }
        }

        return true;
    }

    public function getColumnListing($table)
    {
        $table = $this->connection->getTablePrefix().$table;

        $results = $this->connection->select($this->grammar->compileColumnListing(), [$table]);

        return $this->connection->getPostProcessor()->processColumnListing($results);
    }

    public function getColumnType($table, $column, $fullDefinition = false)
    {
        $table = $this->connection->getTablePrefix().$table;

        $columns = $this->connection->select(
            "select data_type from information_schema.columns where table_schema = 'main' and table_name = ? and column_name = ?",
            [$table, $column]
        );

        return count($columns) > 0 ? $columns[0]->data_type : null;
    }

    public function dropAllTables()
    {
        $tables = $this->getAllTables();

        if (empty($tables)) {
            return;
        }

        $this->disableForeignKeyConstraints();

        foreach ($tables as $table) {
            $this->connection->statement(
                $this->grammar->compileDropIfExists($this->blueprintClass($table))
            );
        }

        $this->enableForeignKeyConstraints();
    }

    public function dropAllViews()
    {
        $views = $this->getAllViews();

        if (empty($views)) {
            return;
        }

        foreach ($views as $view) {
            $this->connection->statement(
                "drop view if exists {$this->connection->getTablePrefix()}{$view}"
            );
        }
    }

    public function getAllTables()
    {
        $results = $this->connection->select(
            "select table_name from information_schema.tables where table_schema = 'main' and table_type = 'BASE TABLE'"
        );

        return array_map(function ($result) {
            return $result->table_name;
        }, $results);
    }

    public function getAllViews()
    {
        $results = $this->connection->select(
            "select table_name from information_schema.tables where table_schema = 'main' and table_type = 'VIEW'"
        );

        return array_map(function ($result) {
            return $result->table_name;
        }, $results);
    }

    public function createDatabase($name)
    {
        throw new \LogicException('DuckDB does not support database creation through SQL.');
    }

    public function dropDatabaseIfExists($name)
    {
        throw new \LogicException('DuckDB does not support database deletion through SQL.');
    }

    public function blueprintClass($table)
    {
        $blueprint = new Blueprint($table);
        if (method_exists($blueprint, 'setConnection')) {
            $blueprint->setConnection($this->connection);
        }
        return $blueprint;
    }

    public function blueprintResolver(Closure $callback)
    {
        return function ($table) use ($callback) {
            $blueprint = $this->blueprintClass($table);

            if ($callback) {
                $callback($blueprint);
            }

            return $blueprint;
        };
    }

    protected function createBlueprint($table, ?\Closure $callback = null)
    {
        return $this->blueprintClass($table);
    }
}