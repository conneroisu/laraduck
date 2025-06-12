<?php

namespace Laraduck\EloquentDuckDB\Query;

use Closure;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Enhanced Query Builder for DuckDB with comprehensive analytical capabilities.
 * 
 * Provides advanced analytical SQL features including window functions, CTEs,
 * PIVOT/UNPIVOT operations, time series analysis, and file-based queries.
 */
class Builder extends BaseBuilder
{
    public $sample;
    
    public $qualify = [];
    
    public $ctes = [];
    
    public $recursiveCte = false;
    
    public $pivot;
    
    public $unpivot;
    
    public $windowFunctions = [];
    
    public $timeSeriesColumns = [];
    
    public $analyticalCache = [];

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

    /**
     * Add PIVOT operation for analytical queries.
     */
    public function pivot(array $aggregates, $columns, $values = null)
    {
        $this->pivot = [
            'aggregates' => $aggregates,
            'columns' => Arr::wrap($columns),
            'values' => $values,
        ];

        return $this;
    }

    /**
     * Add UNPIVOT operation for analytical queries.
     */
    public function unpivot(array $columns, $valueColumn = 'value', $nameColumn = 'name')
    {
        $this->unpivot = [
            'columns' => $columns,
            'value_column' => $valueColumn,
            'name_column' => $nameColumn,
        ];

        return $this;
    }

    /**
     * Add comprehensive window function support.
     */
    public function windowFunction($function, $column = '*', array $options = [])
    {
        $window = [
            'function' => $function,
            'column' => $column,
            'partition' => $options['partition'] ?? null,
            'order' => $options['order'] ?? null,
            'frame' => $options['frame'] ?? null,
        ];

        $alias = $options['alias'] ?? strtolower($function) . '_result';
        
        return $this->selectRaw(
            $this->grammar->compileAnalyticalFunction($function, [$column], $window) . ' as ' . $alias
        );
    }

    /**
     * Add ROW_NUMBER window function.
     */
    public function rowNumber($partitionBy = null, $orderBy = null, $alias = 'row_number')
    {
        return $this->windowFunction('ROW_NUMBER', '', [
            'partition' => $partitionBy,
            'order' => $orderBy,
            'alias' => $alias,
        ]);
    }

    /**
     * Add RANK window function.
     */
    public function rank($column, $partitionBy = null, $orderBy = null, $alias = 'rank')
    {
        return $this->windowFunction('RANK', $column, [
            'partition' => $partitionBy,
            'order' => $orderBy ?: "{$column} DESC",
            'alias' => $alias,
        ]);
    }

    /**
     * Add DENSE_RANK window function.
     */
    public function denseRank($column, $partitionBy = null, $orderBy = null, $alias = 'dense_rank')
    {
        return $this->windowFunction('DENSE_RANK', $column, [
            'partition' => $partitionBy,
            'order' => $orderBy ?: "{$column} DESC",
            'alias' => $alias,
        ]);
    }

    /**
     * Add LAG window function for time series analysis.
     */
    public function lag($column, $offset = 1, $default = null, $partitionBy = null, $orderBy = null, $alias = null)
    {
        $alias = $alias ?: "lag_{$column}";
        $function = "LAG({$column}, {$offset}" . ($default !== null ? ", {$default}" : '') . ')';
        
        return $this->window($function, $partitionBy, $orderBy)->selectRaw($function . ' as ' . $alias);
    }

    /**
     * Add LEAD window function for time series analysis.
     */
    public function lead($column, $offset = 1, $default = null, $partitionBy = null, $orderBy = null, $alias = null)
    {
        $alias = $alias ?: "lead_{$column}";
        $function = "LEAD({$column}, {$offset}" . ($default !== null ? ", {$default}" : '') . ')';
        
        return $this->window($function, $partitionBy, $orderBy)->selectRaw($function . ' as ' . $alias);
    }

    /**
     * Add cumulative sum with window function.
     */
    public function cumulativeSum($column, $partitionBy = null, $orderBy = null, $alias = null)
    {
        $alias = $alias ?: "cumsum_{$column}";
        return $this->windowFunction('SUM', $column, [
            'partition' => $partitionBy,
            'order' => $orderBy ?: $column,
            'frame' => ['type' => 'ROWS', 'start' => 'UNBOUNDED PRECEDING'],
            'alias' => $alias,
        ]);
    }

    /**
     * Add moving average window function.
     */
    public function movingAverage($column, $window = 7, $partitionBy = null, $orderBy = null, $alias = null)
    {
        $alias = $alias ?: "mavg_{$column}";
        return $this->windowFunction('AVG', $column, [
            'partition' => $partitionBy,
            'order' => $orderBy ?: $column,
            'frame' => [
                'type' => 'ROWS',
                'start' => ['value' => $window - 1, 'type' => 'PRECEDING'],
                'end' => 'CURRENT ROW'
            ],
            'alias' => $alias,
        ]);
    }

    /**
     * Add percentile calculations.
     */
    public function percentile($column, $percentile = 0.5, $alias = null)
    {
        $alias = $alias ?: "p" . ($percentile * 100) . "_{$column}";
        return $this->selectRaw("PERCENTILE_CONT({$percentile}) WITHIN GROUP (ORDER BY {$column}) as {$alias}");
    }

    /**
     * Add median calculation (50th percentile).
     */
    public function median($column, $alias = null)
    {
        return $this->percentile($column, 0.5, $alias ?: "median_{$column}");
    }

    /**
     * Add quartile calculations.
     */
    public function quartiles($column)
    {
        return $this->selectRaw("
            PERCENTILE_CONT(0.25) WITHIN GROUP (ORDER BY {$column}) as q1_{$column},
            PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY {$column}) as q2_{$column},
            PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY {$column}) as q3_{$column}
        ");
    }

    /**
     * Add time bucket aggregation for time series data.
     */
    public function timeBucket($timeColumn, $interval = '1 hour', $aggregates = [], $alias = 'bucket')
    {
        $bucketExpr = "TIME_BUCKET(INTERVAL '{$interval}', {$timeColumn}) as {$alias}";
        $this->selectRaw($bucketExpr);
        
        foreach ($aggregates as $func => $column) {
            $aggAlias = is_numeric($func) ? strtolower($column) . '_' . $func : strtolower($func) . '_' . $column;
            $this->selectRaw("{$func}({$column}) as {$aggAlias}");
        }
        
        return $this->groupBy($alias);
    }

    /**
     * Add window function with range between dates.
     */
    public function dateRange($column, $days = 7, $direction = 'PRECEDING')
    {
        return $this->windowFrame(
            "SUM({$column})",
            null,
            $column,
            "INTERVAL '{$days}' DAYS {$direction}",
            'CURRENT ROW'
        );
    }

    /**
     * Add TOP N analysis per group.
     */
    public function topN($column, $n = 10, $partitionBy = null, $orderBy = null)
    {
        $orderBy = $orderBy ?: "{$column} DESC";
        return $this->rowNumber($partitionBy, $orderBy)->qualify('row_number', '<=', $n);
    }

    /**
     * Add statistical functions.
     */
    public function stats($column)
    {
        return $this->selectRaw("
            COUNT({$column}) as count_{$column},
            MIN({$column}) as min_{$column},
            MAX({$column}) as max_{$column},
            AVG({$column}) as avg_{$column},
            STDDEV({$column}) as stddev_{$column},
            VARIANCE({$column}) as variance_{$column}
        ");
    }

    /**
     * Add correlation analysis between two columns.
     */
    public function correlation($column1, $column2, $alias = null)
    {
        $alias = $alias ?: "corr_{$column1}_{$column2}";
        return $this->selectRaw("CORR({$column1}, {$column2}) as {$alias}");
    }

    /**
     * Add linear regression slope and intercept.
     */
    public function regression($xColumn, $yColumn)
    {
        return $this->selectRaw("
            REGR_SLOPE({$yColumn}, {$xColumn}) as slope,
            REGR_INTERCEPT({$yColumn}, {$xColumn}) as intercept,
            REGR_R2({$yColumn}, {$xColumn}) as r_squared
        ");
    }

    /**
     * Add APPROX_COUNT_DISTINCT for large datasets.
     */
    public function approxCountDistinct($column, $alias = null)
    {
        $alias = $alias ?: "approx_count_distinct_{$column}";
        return $this->selectRaw("APPROX_COUNT_DISTINCT({$column}) as {$alias}");
    }

    /**
     * Add histogram generation.
     */
    public function histogram($column, $bins = 10, $min = null, $max = null)
    {
        $minExpr = $min !== null ? $min : "MIN({$column})";
        $maxExpr = $max !== null ? $max : "MAX({$column})";
        
        return $this->selectRaw("
            HISTOGRAM({$column}) as histogram,
            WIDTH_BUCKET({$column}, {$minExpr}, {$maxExpr}, {$bins}) as bin
        ")->groupBy('bin');
    }

    /**
     * Add time series decomposition helpers.
     */
    public function seasonalDecomposition($valueColumn, $timeColumn, $period = 24)
    {
        return $this->selectRaw("
            {$timeColumn},
            {$valueColumn},
            AVG({$valueColumn}) OVER (
                ORDER BY {$timeColumn} 
                ROWS BETWEEN {$period} PRECEDING AND {$period} FOLLOWING
            ) as trend,
            {$valueColumn} - AVG({$valueColumn}) OVER (
                ORDER BY {$timeColumn} 
                ROWS BETWEEN {$period} PRECEDING AND {$period} FOLLOWING
            ) as residual
        ");
    }

    /**
     * Add cohort analysis support.
     */
    public function cohortAnalysis($userColumn, $dateColumn, $eventColumn = null)
    {
        $eventExpr = $eventColumn ? "COUNT({$eventColumn})" : 'COUNT(*)';
        
        return $this->selectRaw("
            DATE_TRUNC('month', MIN({$dateColumn})) OVER (PARTITION BY {$userColumn}) as cohort_month,
            DATE_TRUNC('month', {$dateColumn}) as period_month,
            {$eventExpr} as events
        ")->groupBy('cohort_month', 'period_month');
    }

    /**
     * Add funnel analysis support.
     */
    public function funnelAnalysis($stepColumn, $userColumn, array $steps)
    {
        $caseStatements = [];
        foreach ($steps as $index => $step) {
            $caseStatements[] = "SUM(CASE WHEN {$stepColumn} = '{$step}' THEN 1 ELSE 0 END) as step_{$index}_count";
        }
        
        return $this->selectRaw(implode(', ', $caseStatements));
    }

    /**
     * Add advanced sampling methods.
     */
    public function stratifiedSample($strataColumn, $sampleSize = 1000)
    {
        return $this->window('ROW_NUMBER()', [$strataColumn], 'RANDOM()')
                   ->qualify('ROW_NUMBER()', '<=', $sampleSize);
    }

    /**
     * Add time-weighted calculations.
     */
    public function timeWeightedAverage($valueColumn, $timeColumn, $weightColumn = null)
    {
        $weight = $weightColumn ?: "EXTRACT(EPOCH FROM LEAD({$timeColumn}) OVER (ORDER BY {$timeColumn}) - {$timeColumn})";
        
        return $this->selectRaw("
            SUM({$valueColumn} * {$weight}) / SUM({$weight}) as time_weighted_avg
        ");
    }

    /**
     * Add change detection for time series.
     */
    public function changeDetection($valueColumn, $timeColumn, $threshold = 0.1)
    {
        return $this->selectRaw("
            {$timeColumn},
            {$valueColumn},
            LAG({$valueColumn}) OVER (ORDER BY {$timeColumn}) as previous_value,
            ABS({$valueColumn} - LAG({$valueColumn}) OVER (ORDER BY {$timeColumn})) / 
            LAG({$valueColumn}) OVER (ORDER BY {$timeColumn}) as change_rate,
            CASE WHEN ABS({$valueColumn} - LAG({$valueColumn}) OVER (ORDER BY {$timeColumn})) / 
                 LAG({$valueColumn}) OVER (ORDER BY {$timeColumn}) > {$threshold} 
                 THEN 1 ELSE 0 END as is_anomaly
        ");
    }

    /**
     * Add array and list operations.
     */
    public function arrayAgg($column, $orderBy = null, $alias = null)
    {
        $alias = $alias ?: "array_{$column}";
        $orderClause = $orderBy ? " ORDER BY {$orderBy}" : '';
        return $this->selectRaw("LIST({$column}{$orderClause}) as {$alias}");
    }

    /**
     * Add struct creation for complex data types.
     */
    public function structPack(array $fields, $alias = 'struct_data')
    {
        $fieldPairs = [];
        foreach ($fields as $key => $column) {
            $fieldPairs[] = "'{$key}': {$column}";
        }
        
        return $this->selectRaw("STRUCT_PACK(" . implode(', ', $fieldPairs) . ") as {$alias}");
    }

    /**
     * Add geographical distance calculations.
     */
    public function geoDistance($lat1, $lon1, $lat2, $lon2, $unit = 'km', $alias = 'distance')
    {
        $earthRadius = $unit === 'miles' ? 3959 : 6371;
        
        return $this->selectRaw("
            {$earthRadius} * ACOS(
                COS(RADIANS({$lat1})) * COS(RADIANS({$lat2})) * 
                COS(RADIANS({$lon2}) - RADIANS({$lon1})) + 
                SIN(RADIANS({$lat1})) * SIN(RADIANS({$lat2}))
            ) as {$alias}
        ");
    }

    /**
     * Add advanced date/time operations.
     */
    public function dateAdd($dateColumn, $interval, $unit = 'days', $alias = null)
    {
        $alias = $alias ?: "{$dateColumn}_plus_{$interval}_{$unit}";
        return $this->selectRaw("{$dateColumn} + INTERVAL '{$interval} {$unit}' as {$alias}");
    }

    /**
     * Add business day calculations.
     */
    public function businessDays($startDate, $endDate, $alias = 'business_days')
    {
        return $this->selectRaw("
            CASE WHEN {$endDate} >= {$startDate} THEN
                (EXTRACT(DAYS FROM {$endDate} - {$startDate}) + 1) -
                (EXTRACT(DAYS FROM {$endDate} - {$startDate}) / 7) * 2 -
                CASE WHEN EXTRACT(DOW FROM {$startDate}) = 0 THEN 1 ELSE 0 END -
                CASE WHEN EXTRACT(DOW FROM {$endDate}) = 6 THEN 1 ELSE 0 END
            ELSE 0 END as {$alias}
        ");
    }

    /**
     * Add performance monitoring for analytical queries.
     */
    public function explain($analyze = false)
    {
        $sql = ($analyze ? 'EXPLAIN ANALYZE ' : 'EXPLAIN ') . $this->toSql();
        return $this->connection->select($sql, $this->getBindings());
    }

    /**
     * Add query caching for expensive analytical operations.
     */
    public function cached($key = null, $ttl = 3600)
    {
        if (!$key) {
            $key = md5($this->toSql() . serialize($this->getBindings()));
        }
        
        $this->analyticalCache = [
            'key' => $key,
            'ttl' => $ttl,
        ];
        
        return $this;
    }

    /**
     * Add table sampling for large datasets.
     */
    public function tableSample($percentage, $method = 'SYSTEM')
    {
        $this->from = $this->from . " TABLESAMPLE {$method} ({$percentage})";
        return $this;
    }
}