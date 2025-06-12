<?php

namespace Laraduck\EloquentDuckDB\Eloquent\Traits;

use Closure;

trait SupportsAdvancedQueries
{
    public function withCte($name, $query, $columns = null, $materialized = true)
    {
        return $this->newQuery()->withCte($name, $query, $columns, $materialized);
    }

    public function withRecursiveCte($name, $baseQuery, $recursiveQuery, $columns = null)
    {
        return $this->newQuery()->withRecursiveCte($name, $baseQuery, $recursiveQuery, $columns);
    }

    public function window($function, $partitionBy = null, $orderBy = null, $frame = null)
    {
        return $this->newQuery()->window($function, $partitionBy, $orderBy, $frame);
    }

    public function windowFrame($function, $partitionBy = null, $orderBy = null, $frameStart = null, $frameEnd = null)
    {
        return $this->newQuery()->windowFrame($function, $partitionBy, $orderBy, $frameStart, $frameEnd);
    }

    public function qualify($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->newQuery()->qualify($column, $operator, $value, $boolean);
    }

    public function orQualify($column, $operator = null, $value = null)
    {
        return $this->newQuery()->orQualify($column, $operator, $value);
    }

    public function groupByAll()
    {
        return $this->newQuery()->groupByAll();
    }

    public function insertUsing(array $columns, $query)
    {
        return $this->newQuery()->insertUsing($columns, $query);
    }

    public function upsert(array $values, $uniqueBy, $update = null)
    {
        return $this->newQuery()->upsert($values, $uniqueBy, $update);
    }

    public function sample($value, $method = 'BERNOULLI')
    {
        return $this->newQuery()->sample($value, $method);
    }

    public function leadLag($column, $offset = 1, $default = null, $orderBy = null, $partitionBy = null)
    {
        $function = "LAG({$column}, {$offset}";
        
        if ($default !== null) {
            $function .= ", {$default}";
        }
        
        $function .= ")";
        
        return $this->window($function, $partitionBy, $orderBy);
    }

    public function lead($column, $offset = 1, $default = null, $orderBy = null, $partitionBy = null)
    {
        $function = "LEAD({$column}, {$offset}";
        
        if ($default !== null) {
            $function .= ", {$default}";
        }
        
        $function .= ")";
        
        return $this->window($function, $partitionBy, $orderBy);
    }

    public function rowNumber($orderBy = null, $partitionBy = null)
    {
        return $this->window('ROW_NUMBER()', $partitionBy, $orderBy);
    }

    public function rank($orderBy, $partitionBy = null)
    {
        return $this->window('RANK()', $partitionBy, $orderBy);
    }

    public function denseRank($orderBy, $partitionBy = null)
    {
        return $this->window('DENSE_RANK()', $partitionBy, $orderBy);
    }

    public function percentRank($orderBy, $partitionBy = null)
    {
        return $this->window('PERCENT_RANK()', $partitionBy, $orderBy);
    }

    public function ntile($buckets, $orderBy, $partitionBy = null)
    {
        return $this->window("NTILE({$buckets})", $partitionBy, $orderBy);
    }

    public function cumulativeDistribution($orderBy, $partitionBy = null)
    {
        return $this->window('CUME_DIST()', $partitionBy, $orderBy);
    }

    public function firstValue($column, $orderBy, $partitionBy = null, $frame = null)
    {
        return $this->window("FIRST_VALUE({$column})", $partitionBy, $orderBy, $frame);
    }

    public function lastValue($column, $orderBy, $partitionBy = null, $frame = null)
    {
        $frame = $frame ?? 'RANGE BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING';
        return $this->window("LAST_VALUE({$column})", $partitionBy, $orderBy, $frame);
    }

    public function nthValue($column, $n, $orderBy, $partitionBy = null, $frame = null)
    {
        return $this->window("NTH_VALUE({$column}, {$n})", $partitionBy, $orderBy, $frame);
    }

    public function windowAggregate($function, $column, $orderBy = null, $partitionBy = null, $frame = null)
    {
        $func = strtoupper($function) . "({$column})";
        return $this->window($func, $partitionBy, $orderBy, $frame);
    }

    public function runningTotal($column, $orderBy = null, $partitionBy = null)
    {
        $frame = 'ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW';
        return $this->windowAggregate('SUM', $column, $orderBy, $partitionBy, $frame);
    }

    public function runningAverage($column, $orderBy = null, $partitionBy = null)
    {
        $frame = 'ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW';
        return $this->windowAggregate('AVG', $column, $orderBy, $partitionBy, $frame);
    }

    public function movingAverage($column, $preceding, $following = 0, $orderBy = null, $partitionBy = null)
    {
        $frame = "ROWS BETWEEN {$preceding} PRECEDING AND {$following} FOLLOWING";
        return $this->windowAggregate('AVG', $column, $orderBy, $partitionBy, $frame);
    }

    public function scopeWithRunningTotal($query, $column, $as, $orderBy = null, $partitionBy = null)
    {
        $frame = 'ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW';
        $func = "SUM({$column}) OVER (";
        
        if ($partitionBy) {
            $func .= "PARTITION BY {$partitionBy} ";
        }
        
        if ($orderBy) {
            $func .= "ORDER BY {$orderBy} ";
        }
        
        $func .= "{$frame}) AS {$as}";
        
        return $query->selectRaw($func);
    }

    public function withRowNumbers($as = 'row_num', $orderBy = null, $partitionBy = null)
    {
        $func = "ROW_NUMBER() OVER (";
        
        if ($partitionBy) {
            $func .= "PARTITION BY {$partitionBy} ";
        }
        
        if ($orderBy) {
            $func .= "ORDER BY {$orderBy}";
        }
        
        $func .= ") AS {$as}";
        
        return $this->selectRaw($func);
    }

    public function topNPerGroup($n, $groupBy, $orderBy)
    {
        return $this->withRowNumbers('rn', $orderBy, $groupBy)
                    ->qualify('rn', '<=', $n);
    }

    public function withPercentiles($column, $percentiles = [0.25, 0.5, 0.75], $partitionBy = null)
    {
        $query = $this;
        
        foreach ($percentiles as $p) {
            $pct = $p * 100;
            $func = "PERCENTILE_CONT({$p}) WITHIN GROUP (ORDER BY {$column})";
            
            if ($partitionBy) {
                $func .= " OVER (PARTITION BY {$partitionBy})";
            }
            
            $query = $query->selectRaw("{$func} AS p{$pct}_{$column}");
        }
        
        return $query;
    }
}