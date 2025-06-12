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

    public function copyTo($query, $path, $format = 'PARQUET', array $options = [])
    {
        $optionsString = collect($options)->map(function ($value, $key) {
            return "{$key} = {$value}";
        })->implode(', ');

        $sql = "COPY ({$query}) TO '{$path}' (FORMAT {$format}" . 
               ($optionsString ? ", {$optionsString}" : "") . ")";

        return $this->statement($sql);
    }

    public function copyFrom($table, $path, $format = 'AUTO', array $options = [])
    {
        $optionsString = collect($options)->map(function ($value, $key) {
            return "{$key} = {$value}";
        })->implode(', ');

        $formatString = $format === 'AUTO' ? '' : "(FORMAT {$format}" . 
                       ($optionsString ? ", {$optionsString}" : "") . ")";

        $sql = "COPY {$table} FROM '{$path}' {$formatString}";

        return $this->statement($sql);
    }

    public function install($extension)
    {
        return $this->statement("INSTALL {$extension}");
    }

    public function load($extension)
    {
        return $this->statement("LOAD {$extension}");
    }

    public function setSetting($key, $value)
    {
        return $this->statement("SET {$key} = '{$value}'");
    }

    public function getSetting($key)
    {
        $result = $this->select("SELECT current_setting('{$key}') as value");
        return $result[0]->value ?? null;
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