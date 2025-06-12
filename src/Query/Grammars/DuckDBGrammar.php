<?php

namespace Laraduck\EloquentDuckDB\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DuckDBGrammar extends BaseGrammar
{
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    protected $selectComponents = [
        'cte',
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'qualify',
        'limit',
        'offset',
        'sample',
        'pivot',
        'unpivot',
    ];

    public function compileSelect(Builder $query)
    {
        if ($query->unions && $query->aggregate) {
            return $this->compileUnionAggregate($query);
        }

        $original = $query->columns;

        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        $sql = trim($this->concatenate(
            $this->compileComponents($query)
        ));

        if ($query->unions) {
            $sql = $this->wrapUnion($sql).' '.$this->compileUnions($query);
        }

        $query->columns = $original;

        return $sql;
    }

    protected function compileFrom(Builder $query, $from)
    {
        // Handle Expression objects
        $originalFrom = $from;
        if ($from instanceof \Illuminate\Database\Query\Expression) {
            $from = $from->getValue($this);
        }
        
        $table = $this->wrapTable($originalFrom);

        if ($this->isFunctionTable($from)) {
            return "from {$from}";
        }

        if ($this->isFileTable($from)) {
            return "from '{$from}'";
        }

        return "from {$table}";
    }

    protected function isFileTable($table)
    {
        // Handle Expression objects
        if ($table instanceof \Illuminate\Database\Query\Expression) {
            $table = $table->getValue($this);
        }
        
        if (!is_string($table)) {
            return false;
        }
        
        $extensions = ['parquet', 'csv', 'json', 'tsv', 'xlsx'];
        
        foreach ($extensions as $ext) {
            if (str_contains($table, ".{$ext}")) {
                return true;
            }
        }

        return false;
    }

    protected function isFunctionTable($table)
    {
        // Handle Expression objects
        if ($table instanceof \Illuminate\Database\Query\Expression) {
            $table = $table->getValue($this);
        }
        
        if (!is_string($table)) {
            return false;
        }
        
        $functions = ['read_csv', 'read_json', 'read_parquet', 'read_csv_auto', 'read_json_auto'];
        
        foreach ($functions as $func) {
            if (str_starts_with($table, $func)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compile Common Table Expressions (CTEs) with enhanced DuckDB support.
     */
    protected function compileCte(Builder $query)
    {
        if (!isset($query->ctes) || empty($query->ctes)) {
            return '';
        }

        $statements = [];

        foreach ($query->ctes as $cte) {
            $cteClause = $cte['name'];
            
            // Add column list if specified
            if (!empty($cte['columns'])) {
                $cteClause .= ' (' . implode(', ', $cte['columns']) . ')';
            }
            
            // Add materialization hint for DuckDB optimization
            $materialization = '';
            if (isset($cte['materialized'])) {
                $materialization = $cte['materialized'] ? ' MATERIALIZED' : ' NOT MATERIALIZED';
            }
            
            $cteClause .= ' AS' . $materialization . ' (' . $cte['query'] . ')';
            $statements[] = $cteClause;
        }

        $withClause = 'WITH ';
        
        // Add RECURSIVE keyword if any CTE is recursive
        if (isset($query->recursiveCte) && $query->recursiveCte) {
            $withClause .= 'RECURSIVE ';
        }
        
        return $withClause . implode(', ', $statements) . ' ';
    }

    protected function compileQualify(Builder $query)
    {
        if (!isset($query->qualify) || empty($query->qualify)) {
            return '';
        }

        return 'qualify ' . $this->compileWheres($query, $query->qualify);
    }

    protected function compileSample(Builder $query)
    {
        if (!isset($query->sample)) {
            return '';
        }

        $sample = $query->sample;
        
        if (is_numeric($sample['value'])) {
            if ($sample['value'] < 1) {
                $percent = $sample['value'] * 100;
                return "using sample {$percent} percent ({$sample['method']})";
            } else {
                return "using sample {$sample['value']} rows ({$sample['method']})";
            }
        }

        return "using sample {$sample['value']} ({$sample['method']})";
    }

    public function compileGroupByAll(Builder $query)
    {
        return 'group by all';
    }

    /**
     * Compile a window function with comprehensive frame support.
     */
    public function compileWindow(Builder $query, $window)
    {
        $over = [];

        // PARTITION BY clause
        if (isset($window['partition'])) {
            $partitions = is_array($window['partition']) ? $window['partition'] : [$window['partition']];
            $over[] = 'PARTITION BY ' . $this->columnize($partitions);
        }

        // ORDER BY clause
        if (isset($window['order'])) {
            if (is_string($window['order'])) {
                $over[] = 'ORDER BY ' . $window['order'];
            } elseif (is_array($window['order'])) {
                $orderClauses = [];
                foreach ($window['order'] as $order) {
                    if (is_array($order)) {
                        $column = $order['column'];
                        $direction = strtoupper($order['direction'] ?? 'ASC');
                        $nulls = isset($order['nulls']) ? ' NULLS ' . strtoupper($order['nulls']) : '';
                        $orderClauses[] = $this->wrap($column) . ' ' . $direction . $nulls;
                    } else {
                        $orderClauses[] = $order;
                    }
                }
                $over[] = 'ORDER BY ' . implode(', ', $orderClauses);
            }
        }

        // Window frame specification
        if (isset($window['frame'])) {
            $over[] = $this->compileWindowFrame($window['frame']);
        }

        return 'OVER (' . implode(' ', $over) . ')';
    }

    /**
     * Compile window frame specification.
     */
    protected function compileWindowFrame($frame)
    {
        if (is_string($frame)) {
            return $frame;
        }

        if (!is_array($frame)) {
            return '';
        }

        $frameClause = [];

        // Frame type (ROWS, RANGE, GROUPS)
        $type = strtoupper($frame['type'] ?? 'ROWS');
        $frameClause[] = $type;

        // Frame bounds
        if (isset($frame['start']) || isset($frame['end'])) {
            if (isset($frame['start']) && isset($frame['end'])) {
                $start = $this->compileFrameBound($frame['start']);
                $end = $this->compileFrameBound($frame['end']);
                $frameClause[] = "BETWEEN {$start} AND {$end}";
            } elseif (isset($frame['start'])) {
                $frameClause[] = $this->compileFrameBound($frame['start']);
            }
        } else {
            // Default to UNBOUNDED PRECEDING
            $frameClause[] = 'UNBOUNDED PRECEDING';
        }

        // Frame exclusion
        if (isset($frame['exclude'])) {
            $exclude = strtoupper($frame['exclude']);
            $frameClause[] = "EXCLUDE {$exclude}";
        }

        return implode(' ', $frameClause);
    }

    /**
     * Compile a frame bound specification.
     */
    protected function compileFrameBound($bound)
    {
        if (is_string($bound)) {
            return strtoupper($bound);
        }

        if (is_array($bound)) {
            $type = strtoupper($bound['type'] ?? 'PRECEDING');
            $value = $bound['value'] ?? 'UNBOUNDED';
            
            if ($value === 'UNBOUNDED') {
                return "UNBOUNDED {$type}";
            } elseif ($value === 'CURRENT') {
                return 'CURRENT ROW';
            } else {
                return "{$value} {$type}";
            }
        }

        return 'CURRENT ROW';
    }

    protected function whereJsonContains(Builder $query, $where)
    {
        $not = $where['not'] ? 'not ' : '';

        $column = $this->wrap($where['column']);

        $value = $this->parameter($where['value']);

        return $not . $column . ' @> ' . $value;
    }

    protected function whereJsonLength(Builder $query, $where)
    {
        $column = $this->wrap($where['column']);

        $path = $where['path'] ? "->>'{$where['path']}'" : '';

        return 'json_array_length(' . $column . $path . ') ' . $where['operator'] . ' ' . $where['value'];
    }

    public function compileCreateTable(Builder $query, $command, $connection)
    {
        $sql = parent::compileCreateTable($query, $command, $connection);

        if (isset($command->as)) {
            $sql .= ' as ' . $command->as;
        }

        return $sql;
    }

    public function compileCopy(Builder $query, $path, $format = 'parquet', $options = [])
    {
        $optionsString = collect($options)->map(function ($value, $key) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            return "{$key} {$value}";
        })->implode(', ');

        $formatClause = "format {$format}";
        if ($optionsString) {
            $formatClause .= ", {$optionsString}";
        }

        return "copy ({$this->compileSelect($query)}) to '{$path}' ({$formatClause})";
    }

    protected function compileJsonUpdateColumn($key, $value)
    {
        return "{$key} = json_patch({$key}, '{$value}')";
    }

    public function compileInsertUsing(Builder $query, array $columns, $sql)
    {
        return "insert into {$this->wrapTable($query->from)} ({$this->columnize($columns)}) {$sql}";
    }

    /**
     * Compile PIVOT clause for analytical queries.
     */
    protected function compilePivot(Builder $query)
    {
        if (!isset($query->pivot)) {
            return '';
        }

        $pivot = $query->pivot;
        $pivotClause = 'PIVOT (';
        
        // Aggregate functions
        $aggregates = [];
        foreach ($pivot['aggregates'] as $func => $column) {
            if (is_numeric($func)) {
                // If key is numeric, assume it's just a function name
                $aggregates[] = $column;
            } else {
                // Function with column
                $aggregates[] = "{$func}({$column})";
            }
        }
        $pivotClause .= implode(', ', $aggregates);
        
        // FOR clause
        $pivotClause .= ' FOR ' . implode(', ', $pivot['columns']);
        
        // IN clause with values
        if (isset($pivot['values'])) {
            $values = [];
            foreach ($pivot['values'] as $value) {
                $values[] = is_string($value) ? "'{$value}'" : $value;
            }
            $pivotClause .= ' IN (' . implode(', ', $values) . ')';
        } else {
            // Use star for all values
            $pivotClause .= ' IN (SELECT DISTINCT ' . implode(', ', $pivot['columns']) . ' FROM ' . $query->from . ')';
        }
        
        $pivotClause .= ')';
        
        return $pivotClause;
    }

    /**
     * Compile UNPIVOT clause for analytical queries.
     */
    protected function compileUnpivot(Builder $query)
    {
        if (!isset($query->unpivot)) {
            return '';
        }

        $unpivot = $query->unpivot;
        $unpivotClause = 'UNPIVOT (';
        
        // Value and name columns
        $valueColumn = $unpivot['value_column'] ?? 'value';
        $nameColumn = $unpivot['name_column'] ?? 'name';
        
        $unpivotClause .= "{$valueColumn} FOR {$nameColumn} IN (";
        
        // Columns to unpivot
        $columns = [];
        foreach ($unpivot['columns'] as $column) {
            $columns[] = $this->wrap($column);
        }
        $unpivotClause .= implode(', ', $columns);
        
        $unpivotClause .= '))';
        
        return $unpivotClause;
    }

    /**
     * Compile advanced DuckDB-specific functions.
     */
    public function compileAnalyticalFunction($function, $columns = [], $over = null)
    {
        $function = strtoupper($function);
        $columnList = $this->columnize($columns);
        
        $sql = "{$function}({$columnList})";
        
        if ($over) {
            $sql .= ' ' . $this->compileWindow(null, $over);
        }
        
        return $sql;
    }

    /**
     * Compile time series functions.
     */
    public function compileTimeSeriesFunction($function, $timeColumn, $valueColumn, $options = [])
    {
        $function = strtoupper($function);
        
        switch ($function) {
            case 'TIME_BUCKET':
                $interval = $options['interval'] ?? '1 hour';
                return "TIME_BUCKET(INTERVAL '{$interval}', {$this->wrap($timeColumn)})";
                
            case 'RANGE_BUCKET':
                $start = $options['start'] ?? 0;
                $end = $options['end'] ?? 100;
                $step = $options['step'] ?? 10;
                return "RANGE_BUCKET({$this->wrap($valueColumn)}, {$start}, {$end}, {$step})";
                
            case 'LAG':
            case 'LEAD':
                $offset = $options['offset'] ?? 1;
                $default = isset($options['default']) ? $this->parameter($options['default']) : 'NULL';
                $over = $options['over'] ?? null;
                $sql = "{$function}({$this->wrap($valueColumn)}, {$offset}, {$default})";
                if ($over) {
                    $sql .= ' ' . $this->compileWindow(null, $over);
                }
                return $sql;
                
            default:
                return "{$function}({$this->wrap($valueColumn)})";
        }
    }

    /**
     * Compile sequence generation functions.
     */
    public function compileSequenceFunction($function, $options = [])
    {
        $function = strtoupper($function);
        
        switch ($function) {
            case 'GENERATE_SERIES':
                $start = $options['start'] ?? 1;
                $stop = $options['stop'] ?? 10;
                $step = $options['step'] ?? 1;
                return "GENERATE_SERIES({$start}, {$stop}, {$step})";
                
            case 'RANGE':
                $start = $options['start'] ?? 0;
                $stop = $options['stop'] ?? 10;
                $step = $options['step'] ?? 1;
                return "RANGE({$start}, {$stop}, {$step})";
                
            default:
                return $function . '()';
        }
    }

    /**
     * Compile array and list functions.
     */
    public function compileArrayFunction($function, $array, $options = [])
    {
        $function = strtoupper($function);
        $arrayExpr = is_string($array) ? $this->wrap($array) : json_encode($array);
        
        switch ($function) {
            case 'ARRAY_LENGTH':
            case 'LEN':
                return "LEN({$arrayExpr})";
                
            case 'ARRAY_EXTRACT':
            case 'LIST_EXTRACT':
                $index = $options['index'] ?? 1;
                return "LIST_EXTRACT({$arrayExpr}, {$index})";
                
            case 'UNNEST':
                return "UNNEST({$arrayExpr})";
                
            case 'ARRAY_AGG':
            case 'LIST':
                $column = $options['column'] ?? '*';
                $orderBy = isset($options['order_by']) ? " ORDER BY {$options['order_by']}" : '';
                return "LIST({$this->wrap($column)}{$orderBy})";
                
            default:
                return "{$function}({$arrayExpr})";
        }
    }

    /**
     * Compile struct and map functions.
     */
    public function compileStructFunction($function, $struct, $options = [])
    {
        $function = strtoupper($function);
        $structExpr = is_string($struct) ? $this->wrap($struct) : $struct;
        
        switch ($function) {
            case 'STRUCT_EXTRACT':
                $key = $options['key'] ?? 'key';
                return "STRUCT_EXTRACT({$structExpr}, '{$key}')";
                
            case 'STRUCT_PACK':
                $fields = [];
                foreach ($options['fields'] as $key => $value) {
                    $fields[] = "'{$key}': {$this->wrap($value)}";
                }
                return 'STRUCT_PACK(' . implode(', ', $fields) . ')';
                
            default:
                return "{$function}({$structExpr})";
        }
    }

    /**
     * Enhanced parameter handling for DuckDB data types.
     */
    public function parameter($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return "TIMESTAMP '" . $value->format('Y-m-d H:i:s') . "'";
        }
        
        if (is_array($value)) {
            return '[' . implode(', ', array_map([$this, 'parameter'], $value)) . ']';
        }
        
        if ($value instanceof \DateInterval) {
            return "INTERVAL '" . $value->format('%R%Y-%M-%D %H:%I:%S') . "'";
        }

        return parent::parameter($value);
    }

    /**
     * Compile INSERT with conflict resolution.
     */
    public function compileInsertOrIgnore(Builder $query, array $values)
    {
        $insert = $this->compileInsert($query, $values);
        return str_replace('INSERT INTO', 'INSERT OR IGNORE INTO', $insert);
    }

    /**
     * Compile INSERT with ON CONFLICT clause.
     */
    public function compileInsertOnConflict(Builder $query, array $values, $conflict)
    {
        $insert = $this->compileInsert($query, $values);
        
        if (is_string($conflict)) {
            return $insert . ' ON CONFLICT ' . $conflict;
        }
        
        if (is_array($conflict)) {
            $conflictClause = ' ON CONFLICT';
            
            if (isset($conflict['columns'])) {
                $columns = is_array($conflict['columns']) ? $conflict['columns'] : [$conflict['columns']];
                $conflictClause .= ' (' . $this->columnize($columns) . ')';
            }
            
            $action = strtoupper($conflict['action'] ?? 'DO NOTHING');
            $conflictClause .= ' ' . $action;
            
            if ($action === 'DO UPDATE SET' && isset($conflict['update'])) {
                $updates = [];
                foreach ($conflict['update'] as $column => $value) {
                    $updates[] = $this->wrap($column) . ' = ' . $this->parameter($value);
                }
                $conflictClause .= ' ' . implode(', ', $updates);
            }
            
            return $insert . $conflictClause;
        }
        
        return $insert;
    }

    public function wrapTable($table)
    {
        // Handle Expression objects by getting their value
        if ($table instanceof \Illuminate\Database\Query\Expression) {
            $table = $table->getValue($this);
        }
        
        if ($this->isFunctionTable($table)) {
            return $table;
        }
        
        if ($this->isFileTable($table)) {
            return "'{$table}'";
        }

        return parent::wrapTable($table);
    }

    protected function wrapValue($value)
    {
        if ($value !== '*' && !$this->isFunctionTable($value) && !$this->isFileTable($value)) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}