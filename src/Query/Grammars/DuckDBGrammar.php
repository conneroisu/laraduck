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

    protected function compileCte(Builder $query)
    {
        if (!isset($query->ctes) || empty($query->ctes)) {
            return '';
        }

        $statements = [];

        foreach ($query->ctes as $cte) {
            $statements[] = $cte['name'] . ' as ' . ($cte['materialized'] ? 'materialized' : 'not materialized') . ' (' . $cte['query'] . ')';
        }

        return 'with ' . ($query->recursiveCte ? 'recursive ' : '') . implode(', ', $statements) . ' ';
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

    public function compileWindow(Builder $query, $window)
    {
        $over = [];

        if (isset($window['partition'])) {
            $over[] = 'partition by ' . $this->columnize($window['partition']);
        }

        if (isset($window['order'])) {
            // Handle order as string or array
            if (is_string($window['order'])) {
                $over[] = 'order by ' . $window['order'];
            } else {
                $over[] = 'order by ' . $this->compileOrders($query, $window['order']);
            }
        }

        if (isset($window['frame'])) {
            $over[] = $window['frame'];
        }

        return 'over (' . implode(' ', $over) . ')';
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

    public function parameter($value)
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        return parent::parameter($value);
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