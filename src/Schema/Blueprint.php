<?php

namespace Laraduck\EloquentDuckDB\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;

class Blueprint extends BaseBlueprint
{
    public function list($column, $type = 'varchar')
    {
        return $this->addColumn('list', $column, compact('type'));
    }

    public function struct($column, array $fields)
    {
        return $this->addColumn('struct', $column, compact('fields'));
    }

    public function decimal128($column, $scale = 2)
    {
        return $this->addColumn('decimal128', $column, compact('scale'));
    }

    public function hugeInt($column)
    {
        return $this->addColumn('hugeInt', $column);
    }

    public function interval($column)
    {
        return $this->addColumn('interval', $column);
    }

    public function blob($column)
    {
        return $this->addColumn('blob', $column);
    }

    public function map($column, $keyType = 'varchar', $valueType = 'varchar')
    {
        return $this->addColumn('map', $column, compact('keyType', 'valueType'));
    }

    public function union($column, array $types)
    {
        return $this->addColumn('union', $column, compact('types'));
    }

    public function enum8($column, array $values)
    {
        return $this->addColumn('enum8', $column, ['allowed' => $values]);
    }

    public function enum16($column, array $values)
    {
        return $this->addColumn('enum16', $column, ['allowed' => $values]);
    }

    public function enum32($column, array $values)
    {
        return $this->addColumn('enum32', $column, ['allowed' => $values]);
    }

    public function bitString($column, $length = null)
    {
        return $this->addColumn('bitString', $column, compact('length'));
    }

    public function int128($column)
    {
        return $this->addColumn('int128', $column);
    }

    public function uInt8($column)
    {
        return $this->addColumn('uInt8', $column);
    }

    public function uInt16($column)
    {
        return $this->addColumn('uInt16', $column);
    }

    public function uInt32($column)
    {
        return $this->addColumn('uInt32', $column);
    }

    public function uInt64($column)
    {
        return $this->addColumn('uInt64', $column);
    }

    public function uInt128($column)
    {
        return $this->addColumn('uInt128', $column);
    }

    public function generated($column, $expression)
    {
        return $this->addColumn('generated', $column, compact('expression'));
    }

    public function temporaryTable()
    {
        $this->temporary = true;
        return $this;
    }

    public function createAs($query)
    {
        $this->addCommand('createAs', compact('query'));
        return $this;
    }

    public function sequence($column, $start = 1, $increment = 1)
    {
        return $this->addColumn('sequence', $column, compact('start', 'increment'));
    }
}