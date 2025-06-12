<?php

namespace Laraduck\EloquentDuckDB\Query\Processors;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;

class DuckDBProcessor extends Processor
{
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $query->getConnection()->insert($sql, $values);

        $id = $query->getConnection()->getPdo()->lastInsertId($sequence);

        return is_numeric($id) ? (int) $id : $id;
    }

    public function processColumnListing($results)
    {
        return array_map(function ($result) {
            return $result->column_name;
        }, $results);
    }

    public function processColumns($results)
    {
        return array_map(function ($result) {
            return [
                'name' => $result->column_name,
                'type_name' => $result->data_type,
                'type' => $result->data_type,
                'collation' => $result->collation_name ?? null,
                'nullable' => $result->is_nullable === 'YES',
                'default' => $result->column_default,
                'auto_increment' => false,
                'comment' => null,
            ];
        }, $results);
    }

    public function processTables($results)
    {
        return array_map(function ($result) {
            return [
                'name' => $result->table_name,
                'schema' => $result->table_schema ?? null,
                'size' => null,
                'comment' => null,
                'collation' => null,
                'engine' => null,
            ];
        }, $results);
    }

    public function processIndexes($results)
    {
        return array_map(function ($result) {
            return [
                'name' => $result->index_name,
                'columns' => explode(',', $result->column_names),
                'type' => $result->index_type,
                'unique' => $result->is_unique,
                'primary' => $result->is_primary,
            ];
        }, $results);
    }

    public function processViews($results)
    {
        return array_map(function ($result) {
            return [
                'name' => $result->table_name,
                'schema' => $result->table_schema ?? null,
                'definition' => $result->view_definition ?? null,
            ];
        }, $results);
    }

    public function processForeignKeys($results)
    {
        return array_map(function ($result) {
            return [
                'name' => $result->constraint_name,
                'columns' => explode(',', $result->column_names),
                'foreign_schema' => $result->foreign_schema,
                'foreign_table' => $result->foreign_table,
                'foreign_columns' => explode(',', $result->foreign_column_names),
                'on_update' => $result->update_rule,
                'on_delete' => $result->delete_rule,
            ];
        }, $results);
    }
}