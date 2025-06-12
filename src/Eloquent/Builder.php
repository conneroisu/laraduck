<?php

namespace Laraduck\EloquentDuckDB\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Laraduck\EloquentDuckDB\Query\Builder as QueryBuilder;

class Builder extends EloquentBuilder
{
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getModel()->getConnection();

        return new QueryBuilder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );
    }

    public function sample($value, $method = 'BERNOULLI')
    {
        $this->query->sample($value, $method);
        return $this;
    }

    public function window($function, $partitionBy = null, $orderBy = null, $frame = null)
    {
        $this->query->window($function, $partitionBy, $orderBy, $frame);
        return $this;
    }

    public function qualify($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->query->qualify($column, $operator, $value, $boolean);
        return $this;
    }

    public function orQualify($column, $operator = null, $value = null)
    {
        $this->query->orQualify($column, $operator, $value);
        return $this;
    }

    public function groupByAll()
    {
        $this->query->groupByAll();
        return $this;
    }

    public function withCte($name, $query, $columns = null, $materialized = true)
    {
        $this->query->withCte($name, $query, $columns, $materialized);
        return $this;
    }

    public function withRecursiveCte($name, $baseQuery, $recursiveQuery, $columns = null)
    {
        $this->query->withRecursiveCte($name, $baseQuery, $recursiveQuery, $columns);
        return $this;
    }

    public function fromParquet($path, $as = null)
    {
        $this->query->fromParquet($path, $as);
        return $this;
    }

    public function fromCsv($path, $options = [], $as = null)
    {
        $this->query->fromCsv($path, $options, $as);
        return $this;
    }

    public function fromJsonFile($path, $options = [], $as = null)
    {
        $this->query->fromJsonFile($path, $options, $as);
        return $this;
    }

    public function toParquet($path, $options = [])
    {
        return $this->query->toParquet($path, $options);
    }

    public function toCsv($path, $options = [])
    {
        return $this->query->toCsv($path, $options);
    }

    public function toJsonFile($path, $options = [])
    {
        return $this->query->toJsonFile($path, $options);
    }

    public function whereJsonPath($column, $path, $operator, $value)
    {
        $this->query->whereJsonPath($column, $path, $operator, $value);
        return $this;
    }

    public function whereJsonContains($column, $value, $boolean = 'and', $not = false)
    {
        $this->query->whereJsonContains($column, $value, $boolean, $not);
        return $this;
    }

    public function windowFrame($function, $partitionBy = null, $orderBy = null, $frameStart = null, $frameEnd = null)
    {
        $this->query->windowFrame($function, $partitionBy, $orderBy, $frameStart, $frameEnd);
        return $this;
    }

    public function insertUsing(array $columns, $query)
    {
        return $this->query->insertUsing($columns, $query);
    }

    public function upsert(array $values, $uniqueBy, $update = null)
    {
        return $this->query->upsert($values, $uniqueBy, $update);
    }
}