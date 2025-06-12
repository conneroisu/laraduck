<?php

namespace Laraduck\EloquentDuckDB\Query;

use Closure;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Builder extends BaseBuilder
{
    public $sample;
    
    public $qualify = [];
    
    public $ctes = [];
    
    public $recursiveCte = false;

    public function sample($value, $method = 'BERNOULLI')
    {
        $this->sample = [
            'value' => $value,
            'method' => strtoupper($method),
        ];

        return $this;
    }

    public function window($function, $partitionBy = null, $orderBy = null, $frame = null)
    {
        $window = [];

        if ($partitionBy) {
            $window['partition'] = is_array($partitionBy) ? $partitionBy : [$partitionBy];
        }

        if ($orderBy) {
            $window['order'] = $orderBy;
        }

        if ($frame) {
            $window['frame'] = $frame;
        }

        return $this->selectRaw($function . ' ' . $this->grammar->compileWindow($this, $window));
    }

    public function qualify($column, $operator = null, $value = null, $boolean = 'and')
    {
        list($value, $operator) = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        if ($column instanceof Closure && is_null($operator)) {
            return $this->qualifyNested($column, $boolean);
        }

        if ($this->isInvalidOperator($operator)) {
            list($value, $operator) = [$operator, '='];
        }

        if ($value instanceof Closure) {
            return $this->qualifySub($column, $operator, $value, $boolean);
        }

        $type = 'Basic';

        $this->qualify[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );

        return $this;
    }

    protected function qualifyNested(Closure $callback, $boolean = 'and')
    {
        $callback($query = $this->forNestedWhere());

        return $this->addNestedQualifyQuery($query, $boolean);
    }

    protected function addNestedQualifyQuery($query, $boolean = 'and')
    {
        if (count($query->qualify)) {
            $type = 'Nested';
            
            $this->qualify[] = compact('type', 'query', 'boolean');
        }

        return $this;
    }

    protected function qualifySub($column, $operator, Closure $callback, $boolean)
    {
        $type = 'Sub';

        $callback($query = $this->forSubQuery());

        $this->qualify[] = compact(
            'type', 'column', 'operator', 'query', 'boolean'
        );

        return $this;
    }

    public function orQualify($column, $operator = null, $value = null)
    {
        return $this->qualify($column, $operator, $value, 'or');
    }

    public function groupByAll()
    {
        $this->groups = ['all'];

        return $this;
    }

    public function withCte($name, $query, $columns = null, $materialized = true)
    {
        $columns = $columns ? '(' . implode(', ', Arr::wrap($columns)) . ')' : '';

        if ($query instanceof Closure) {
            $callback = $query;
            $query = $this->newQuery();
            $callback($query);
        }

        $sql = $query instanceof self ? $query->toSql() : (string) $query;

        $this->ctes[] = [
            'name' => $name . $columns,
            'query' => $sql,
            'materialized' => $materialized,
        ];

        if ($query instanceof self) {
            $this->addBinding($query->getBindings(), 'cte');
        }

        return $this;
    }

    public function withRecursiveCte($name, $baseQuery, $recursiveQuery, $columns = null)
    {
        $this->recursiveCte = true;

        $columns = $columns ? '(' . implode(', ', Arr::wrap($columns)) . ')' : '';

        if ($baseQuery instanceof Closure) {
            $callback = $baseQuery;
            $baseQuery = $this->newQuery();
            $callback($baseQuery);
        }

        if ($recursiveQuery instanceof Closure) {
            $callback = $recursiveQuery;
            $recursiveQuery = $this->newQuery();
            $callback($recursiveQuery);
        }

        $baseSql = $baseQuery instanceof self ? $baseQuery->toSql() : (string) $baseQuery;
        $recursiveSql = $recursiveQuery instanceof self ? $recursiveQuery->toSql() : (string) $recursiveQuery;

        $this->ctes[] = [
            'name' => $name . $columns,
            'query' => $baseSql . ' union all ' . $recursiveSql,
            'materialized' => true,
        ];

        if ($baseQuery instanceof self) {
            $this->addBinding($baseQuery->getBindings(), 'cte');
        }

        if ($recursiveQuery instanceof self) {
            $this->addBinding($recursiveQuery->getBindings(), 'cte');
        }

        return $this;
    }

    public function fromParquet($path, $as = null)
    {
        return $this->from($path, $as);
    }

    public function fromCsv($path, $options = [], $as = null)
    {
        if (empty($options)) {
            return $this->from("read_csv_auto('{$path}')", $as);
        }

        $optionsString = collect($options)->map(function ($value, $key) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_string($value)) {
                $value = "'{$value}'";
            }
            return "{$key} = {$value}";
        })->implode(', ');

        return $this->from("read_csv('{$path}', {$optionsString})", $as);
    }

    public function fromJsonFile($path, $options = [], $as = null)
    {
        if (empty($options)) {
            return $this->from("read_json_auto('{$path}')", $as);
        }

        $optionsJson = json_encode($options);
        return $this->from("read_json('{$path}', '{$optionsJson}')", $as);
    }

    public function toParquet($path, $options = [])
    {
        $sql = $this->grammar->compileCopy($this, $path, 'parquet', $options);
        
        return $this->connection->statement($sql, $this->getBindings());
    }

    public function toCsv($path, $options = [])
    {
        $sql = $this->grammar->compileCopy($this, $path, 'csv', $options);
        
        return $this->connection->statement($sql, $this->getBindings());
    }

    public function toJsonFile($path, $options = [])
    {
        $sql = $this->grammar->compileCopy($this, $path, 'json', $options);
        
        return $this->connection->statement($sql, $this->getBindings());
    }

    public function whereJsonPath($column, $path, $operator, $value)
    {
        $jsonPath = $column . "->>" . "'{$path}'";
        
        return $this->whereRaw("{$jsonPath} {$operator} ?", [$value]);
    }

    public function whereJsonContains($column, $value, $boolean = 'and', $not = false)
    {
        $type = 'JsonContains';

        $this->wheres[] = compact('type', 'column', 'value', 'boolean', 'not');

        $this->addBinding($value);

        return $this;
    }

    public function orWhereJsonContains($column, $value, $not = false)
    {
        return $this->whereJsonContains($column, $value, 'or', $not);
    }

    public function whereJsonDoesntContain($column, $value, $boolean = 'and')
    {
        return $this->whereJsonContains($column, $value, $boolean, true);
    }

    public function orWhereJsonDoesntContain($column, $value)
    {
        return $this->whereJsonDoesntContain($column, $value, 'or');
    }

    public function whereJsonLength($column, $operator, $value = null, $boolean = 'and', $path = null)
    {
        $type = 'JsonLength';

        list($value, $operator) = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean', 'path');

        $this->addBinding((int) $value);

        return $this;
    }

    public function orWhereJsonLength($column, $operator, $value = null, $path = null)
    {
        return $this->whereJsonLength($column, $operator, $value, 'or', $path);
    }

    public function windowFrame($function, $partitionBy = null, $orderBy = null, $frameStart = null, $frameEnd = null)
    {
        $frame = null;
        
        if ($frameStart && $frameEnd) {
            $frame = "rows between {$frameStart} and {$frameEnd}";
        } elseif ($frameStart) {
            $frame = "rows {$frameStart}";
        }

        return $this->window($function, $partitionBy, $orderBy, $frame);
    }

    public function insertUsing(array $columns, $query)
    {
        if ($query instanceof Closure) {
            $callback = $query;
            $query = $this->newQuery();
            $callback($query);
        }

        $sql = $query instanceof self ? $query->toSql() : (string) $query;

        $compiledSql = $this->grammar->compileInsertUsing($this, $columns, $sql);

        $bindings = $query instanceof self ? $query->getBindings() : [];

        return $this->connection->insert($compiledSql, $bindings);
    }

    public function upsert(array $values, $uniqueBy, $update = null)
    {
        if (empty($values)) {
            return 0;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $uniqueBy = Arr::wrap($uniqueBy);

        if (is_null($update)) {
            $update = array_keys(reset($values));
        }

        $bindings = $this->cleanBindings(Arr::flatten($values, 1));

        $sql = $this->grammar->compileUpsert($this, $values, $uniqueBy, $update);

        return $this->connection->affectingStatement($sql, $bindings);
    }
}