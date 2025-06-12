<?php

namespace Laraduck\EloquentDuckDB\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesFiles;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesDataFiles;
use Laraduck\EloquentDuckDB\Eloquent\Traits\SupportsAdvancedQueries;
use RuntimeException;

abstract class AnalyticalModel extends Model
{
    use QueriesFiles, QueriesDataFiles, SupportsAdvancedQueries;

    protected $allowSingleRowOperations = false;

    protected $bulkInsertChunkSize = 10000;

    public static function insertBatch(array $data, $chunkSize = null)
    {
        $instance = new static;
        $chunkSize = $chunkSize ?? $instance->bulkInsertChunkSize;

        $affectedRows = 0;

        collect($data)->chunk($chunkSize)->each(function ($chunk) use ($instance, &$affectedRows) {
            $values = $chunk->toArray();
            
            if (!empty($values)) {
                $columns = array_keys(reset($values));
                
                $placeholders = array_map(function ($row) {
                    return '(' . implode(', ', array_fill(0, count($row), '?')) . ')';
                }, $values);
                
                $sql = sprintf(
                    'INSERT INTO %s (%s) VALUES %s',
                    $instance->getTable(),
                    implode(', ', $columns),
                    implode(', ', $placeholders)
                );
                
                $bindings = [];
                foreach ($values as $row) {
                    foreach ($row as $value) {
                        $bindings[] = $value;
                    }
                }
                
                $result = $instance->getConnection()->statement($sql, $bindings);
                if ($result) {
                    $affectedRows += count($values);
                }
            }
        });

        return $affectedRows;
    }

    public function scopeSelectColumns($query, array $columns)
    {
        return $query->select($columns);
    }

    public function save(array $options = [])
    {
        if (!$this->allowsSingleRowOperations()) {
            throw new RuntimeException(
                'Single-row operations are not recommended for analytical models. ' .
                'Use batch operations instead or set $allowSingleRowOperations = true.'
            );
        }

        return parent::save($options);
    }

    public function update(array $attributes = [], array $options = [])
    {
        if (!$this->allowsSingleRowOperations()) {
            throw new RuntimeException(
                'Single-row operations are not recommended for analytical models. ' .
                'Use batch operations instead or set $allowSingleRowOperations = true.'
            );
        }

        return parent::update($attributes, $options);
    }

    public function delete()
    {
        if (!$this->allowsSingleRowOperations()) {
            throw new RuntimeException(
                'Single-row operations are not recommended for analytical models. ' .
                'Use batch operations instead or set $allowSingleRowOperations = true.'
            );
        }

        return parent::delete();
    }

    protected function allowsSingleRowOperations()
    {
        return $this->allowSingleRowOperations;
    }

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function aggregate($function, $column = '*', $groupBy = null)
    {
        $query = $this->newQuery();

        if ($groupBy) {
            $query->groupBy($groupBy);
        }

        return $query->aggregate($function, [$column]);
    }

    public function pivot($rowGroups, $columnGroups, $aggregates)
    {
        $query = $this->newQuery();
        
        $pivotClause = 'PIVOT (';
        
        foreach ($aggregates as $agg => $col) {
            $pivotClause .= "{$agg}({$col}) ";
        }
        
        $pivotClause .= 'FOR ' . implode(', ', $columnGroups);
        $pivotClause .= ' IN (' . $this->getPivotValues($columnGroups) . ')';
        $pivotClause .= ')';
        
        return $query->selectRaw('*')
                    ->fromRaw("({$query->toSql()}) {$pivotClause}")
                    ->addBinding($query->getBindings());
    }

    protected function getPivotValues($columns)
    {
        return "'*'";
    }

    public function unpivot($columns, $nameColumn = 'name', $valueColumn = 'value')
    {
        $query = $this->newQuery();
        
        $unpivotClause = "UNPIVOT ({$valueColumn} FOR {$nameColumn} IN (" . 
                        implode(', ', $columns) . '))';
        
        return $query->selectRaw('*')
                    ->fromRaw("({$query->toSql()}) {$unpivotClause}")
                    ->addBinding($query->getBindings());
    }

    public function summarize(...$columns)
    {
        $query = $this->newQuery();
        
        if (empty($columns)) {
            return $query->selectRaw('SUMMARIZE(*)')->get();
        }
        
        $columnList = implode(', ', $columns);
        return $query->selectRaw("SUMMARIZE({$columnList})")->get();
    }

    public function describe()
    {
        return $this->getConnection()->select("DESCRIBE {$this->getTable()}");
    }

    public function samplePercent($percent, $method = 'BERNOULLI')
    {
        return $this->newQuery()->sample($percent / 100, $method);
    }

    public function sampleRows($rows, $method = 'RESERVOIR')
    {
        return $this->newQuery()->sample($rows, $method);
    }

    public function chunkByColumn($column, $chunkSize, callable $callback)
    {
        $lastValue = null;
        
        do {
            $query = $this->newQuery()->orderBy($column);
            
            if ($lastValue !== null) {
                $query->where($column, '>', $lastValue);
            }
            
            $results = $query->limit($chunkSize)->get();
            
            if ($results->isEmpty()) {
                break;
            }
            
            if ($callback($results) === false) {
                break;
            }
            
            $lastValue = $results->last()->{$column};
            
        } while ($results->count() === $chunkSize);
    }

    public function withTimeTravel($timestamp)
    {
        $table = $this->getTable();
        
        return $this->newQuery()->fromRaw("{$table} AS OF TIMESTAMP '{$timestamp}'");
    }
}