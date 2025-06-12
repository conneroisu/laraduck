<?php

namespace Laraduck\EloquentDuckDB\Schema\Grammars;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar as BaseGrammar;
use Illuminate\Support\Fluent;

class DuckDBGrammar extends BaseGrammar
{
    protected $modifiers = ['Nullable', 'Default', 'Comment'];

    protected $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    public function compileCreate(Blueprint $blueprint, Fluent $command)
    {
        $columns = implode(', ', $this->getColumns($blueprint));

        $sql = $command->temporary ? 'create temporary table' : 'create table';

        $sql .= ' '.$this->wrapTable($blueprint)." ($columns";

        // DuckDB doesn't support inline foreign keys in CREATE TABLE
        // $sql = $this->addForeignKeys($sql, $blueprint);

        // DuckDB supports inline primary keys in CREATE TABLE (as of v1.2+)
        $sql .= $this->addPrimaryKeys($this->getCommandByName($blueprint, 'primary'));

        $sql .= ')';

        return $sql;
    }

    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        $columns = implode(', ', $this->getColumns($blueprint));

        return 'alter table '.$this->wrapTable($blueprint)." add column $columns";
    }

    /**
     * Get the primary key syntax for a table creation statement.
     *
     * @param  \Illuminate\Support\Fluent|null  $primary
     * @return string|null
     */
    protected function addPrimaryKeys($primary)
    {
        if (! is_null($primary)) {
            return ", primary key ({$this->columnize($primary->columns)})";
        }
    }

    public function compilePrimary(Blueprint $blueprint, Fluent $command)
    {
        // Primary keys are handled inline during table creation
        // No need for separate ALTER TABLE statement
        return null;
    }

    public function compileUnique(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('create unique index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $this->columnize($command->columns)
        );
    }

    public function compileIndex(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('create index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $this->columnize($command->columns)
        );
    }

    public function compileForeign(Blueprint $blueprint, Fluent $command)
    {
        $sql = sprintf('alter table %s add constraint %s foreign key (%s) references %s (%s)',
            $this->wrapTable($blueprint),
            $this->wrap($command->index),
            $this->columnize($command->columns),
            $this->wrapTable($command->on),
            $this->columnize((array) $command->references)
        );

        if (!is_null($command->onDelete)) {
            $sql .= " on delete {$command->onDelete}";
        }

        if (!is_null($command->onUpdate)) {
            $sql .= " on update {$command->onUpdate}";
        }

        return $sql;
    }

    public function compileDrop(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table '.$this->wrapTable($blueprint);
    }

    public function compileDropIfExists(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table if exists '.$this->wrapTable($blueprint);
    }

    public function compileDropColumn(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->prefixArray('drop column', $this->wrapArray($command->columns));

        return 'alter table '.$this->wrapTable($blueprint).' '.implode(', ', $columns);
    }

    public function compileDropPrimary(Blueprint $blueprint, Fluent $command)
    {
        return 'alter table '.$this->wrapTable($blueprint).' drop constraint primary';
    }

    public function compileDropUnique(Blueprint $blueprint, Fluent $command)
    {
        return 'drop index '.$this->wrap($command->index);
    }

    public function compileDropIndex(Blueprint $blueprint, Fluent $command)
    {
        return 'drop index '.$this->wrap($command->index);
    }

    public function compileDropForeign(Blueprint $blueprint, Fluent $command)
    {
        return 'alter table '.$this->wrapTable($blueprint).' drop constraint '.$this->wrap($command->index);
    }

    public function compileRename(Blueprint $blueprint, Fluent $command)
    {
        return 'alter table '.$this->wrapTable($blueprint).' rename to '.$this->wrapTable($command->to);
    }

    public function compileRenameColumn(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return 'alter table '.$this->wrapTable($blueprint).' rename column '.$this->wrap($command->from).' to '.$this->wrap($command->to);
    }

    public function compileEnableForeignKeyConstraints()
    {
        // DuckDB doesn't use pragma commands like SQLite
        return '';
    }

    public function compileDisableForeignKeyConstraints()
    {
        // DuckDB doesn't use pragma commands like SQLite
        return '';
    }

    public function compileTableExists()
    {
        return "select table_name from information_schema.tables where table_schema = 'main' and table_name = ?";
    }

    public function compileColumnListing()
    {
        return "select column_name from information_schema.columns where table_schema = 'main' and table_name = ?";
    }

    protected function typeChar(Fluent $column)
    {
        return "varchar({$column->length})";
    }

    protected function typeString(Fluent $column)
    {
        return "varchar({$column->length})";
    }

    protected function typeTinyText(Fluent $column)
    {
        return 'varchar';
    }

    protected function typeText(Fluent $column)
    {
        return 'varchar';
    }

    protected function typeMediumText(Fluent $column)
    {
        return 'varchar';
    }

    protected function typeLongText(Fluent $column)
    {
        return 'varchar';
    }

    protected function typeInteger(Fluent $column)
    {
        return 'integer';
    }

    protected function typeBigInteger(Fluent $column)
    {
        return 'bigint';
    }

    protected function typeMediumInteger(Fluent $column)
    {
        return 'integer';
    }

    protected function typeTinyInteger(Fluent $column)
    {
        return 'tinyint';
    }

    protected function typeSmallInteger(Fluent $column)
    {
        return 'smallint';
    }

    protected function typeFloat(Fluent $column)
    {
        return $this->typeDouble($column);
    }

    protected function typeDouble(Fluent $column)
    {
        if ($column->total && $column->places) {
            return "double({$column->total}, {$column->places})";
        }

        return 'double';
    }

    protected function typeDecimal(Fluent $column)
    {
        return "decimal({$column->total}, {$column->places})";
    }

    protected function typeBoolean(Fluent $column)
    {
        return 'boolean';
    }

    protected function typeEnum(Fluent $column)
    {
        return sprintf(
            "enum(%s)",
            $this->quoteString(array_map(function ($value) {
                return "'$value'";
            }, $column->allowed))
        );
    }

    protected function typeJson(Fluent $column)
    {
        return 'json';
    }

    protected function typeJsonb(Fluent $column)
    {
        return 'json';
    }

    protected function typeDate(Fluent $column)
    {
        return 'date';
    }

    protected function typeDateTime(Fluent $column)
    {
        return $column->precision ? "timestamp({$column->precision})" : 'timestamp';
    }

    protected function typeDateTimeTz(Fluent $column)
    {
        return $this->typeDateTime($column);
    }

    protected function typeTime(Fluent $column)
    {
        return $column->precision ? "time({$column->precision})" : 'time';
    }

    protected function typeTimeTz(Fluent $column)
    {
        return $this->typeTime($column);
    }

    protected function typeTimestamp(Fluent $column)
    {
        return $this->typeDateTime($column);
    }

    protected function typeTimestampTz(Fluent $column)
    {
        return $this->typeDateTime($column);
    }

    protected function typeYear(Fluent $column)
    {
        return 'integer';
    }

    protected function typeBinary(Fluent $column)
    {
        return 'blob';
    }

    protected function typeUuid(Fluent $column)
    {
        return 'uuid';
    }

    protected function typeIpAddress(Fluent $column)
    {
        return 'varchar(45)';
    }

    protected function typeMacAddress(Fluent $column)
    {
        return 'varchar(17)';
    }

    protected function typeGeometry(Fluent $column)
    {
        return 'geometry';
    }

    protected function typePoint(Fluent $column)
    {
        return 'point';
    }

    protected function typeLineString(Fluent $column)
    {
        return 'linestring';
    }

    protected function typePolygon(Fluent $column)
    {
        return 'polygon';
    }

    protected function typeGeometryCollection(Fluent $column)
    {
        return 'geometrycollection';
    }

    protected function typeMultiPoint(Fluent $column)
    {
        return 'multipoint';
    }

    protected function typeMultiLineString(Fluent $column)
    {
        return 'multilinestring';
    }

    protected function typeMultiPolygon(Fluent $column)
    {
        return 'multipolygon';
    }

    protected function typeList(Fluent $column)
    {
        return "{$column->type}[]";
    }

    protected function typeStruct(Fluent $column)
    {
        $fields = collect($column->fields)->map(function ($field, $name) {
            return "{$name} {$field}";
        })->implode(', ');

        return "struct({$fields})";
    }

    protected function typeDecimal128(Fluent $column)
    {
        return "decimal(38, {$column->scale})";
    }

    protected function typeHugeInt(Fluent $column)
    {
        return 'hugeint';
    }

    protected function typeInterval(Fluent $column)
    {
        return 'interval';
    }

    protected function typeBlob(Fluent $column)
    {
        return 'blob';
    }

    protected function modifyDefault(Blueprint $blueprint, Fluent $column)
    {
        if (!is_null($column->default)) {
            return ' default '.$this->getDefaultValue($column->default);
        }
    }

    protected function modifyNullable(Blueprint $blueprint, Fluent $column)
    {
        // DuckDB doesn't support NOT NULL constraints in this context
        return '';
    }

    protected function modifyComment(Blueprint $blueprint, Fluent $column)
    {
        return '';
    }

    protected function getDefaultValue($value)
    {
        if ($value instanceof \Illuminate\Database\Query\Expression) {
            return $value;
        }

        return is_bool($value)
            ? ($value ? 'true' : 'false')
            : $this->quoteString($value);
    }

    protected function wrapValue($value)
    {
        if ($value !== '*') {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}