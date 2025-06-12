<?php

namespace Laraduck\EloquentDuckDB\Eloquent\Traits;

trait QueriesFiles
{
    public static function fromParquet($path, $as = null)
    {
        $instance = new static;
        
        return $instance->newQuery()->fromRaw(
            $as ? "'{$path}' AS {$as}" : "'{$path}'"
        );
    }

    public static function fromParquetGlob($pattern)
    {
        return static::fromParquet($pattern);
    }

    public function toParquet($path, $options = [])
    {
        $query = $this->toSql();
        $bindings = $this->getBindings();
        
        $optionsString = collect($options)->map(function ($value, $key) {
            if (is_bool($value)) {
                $value = $value ? 'TRUE' : 'FALSE';
            } elseif (is_string($value)) {
                $value = "'{$value}'";
            }
            return "{$key} {$value}";
        })->implode(', ');
        
        $sql = "COPY ({$query}) TO '{$path}' (FORMAT PARQUET" . 
               ($optionsString ? ", {$optionsString}" : "") . ")";
        
        return $this->getConnection()->statement($sql, $bindings);
    }

    public static function createTableFromParquet($tableName, $parquetPath, $options = [])
    {
        $instance = new static;
        
        $optionsString = '';
        if (!empty($options)) {
            $optionsString = ', ' . collect($options)->map(function ($value, $key) {
                return "{$key} = {$value}";
            })->implode(', ');
        }
        
        $sql = "CREATE TABLE {$tableName} AS SELECT * FROM read_parquet('{$parquetPath}'{$optionsString})";
        
        return $instance->getConnection()->statement($sql);
    }

    public static function scanParquetMetadata($path)
    {
        $instance = new static;
        
        return collect($instance->getConnection()->select(
            "SELECT * FROM parquet_metadata('{$path}')"
        ))->first();
    }

    public static function scanParquetSchema($path)
    {
        $instance = new static;
        
        return collect($instance->getConnection()->select(
            "SELECT * FROM parquet_schema('{$path}')"
        ));
    }

    public function exportToParquetPartitioned($basePath, $partitionBy, $options = [])
    {
        $query = $this->toSql();
        $bindings = $this->getBindings();
        
        $partitionClause = is_array($partitionBy) 
            ? implode(', ', $partitionBy) 
            : $partitionBy;
        
        $optionsString = collect($options)->map(function ($value, $key) {
            return "{$key} {$value}";
        })->implode(', ');
        
        $sql = "COPY ({$query}) TO '{$basePath}' (FORMAT PARQUET, PARTITION_BY ({$partitionClause})" .
               ($optionsString ? ", {$optionsString}" : "") . ")";
        
        return $this->getConnection()->statement($sql, $bindings);
    }

    public static function fromS3Parquet($bucket, $key, $credentials = [])
    {
        $instance = new static;
        
        if (!empty($credentials)) {
            foreach ($credentials as $setting => $value) {
                $instance->getConnection()->setSetting($setting, $value);
            }
        }
        
        $path = "s3://{$bucket}/{$key}";
        
        return static::fromParquet($path);
    }

    public function toS3Parquet($bucket, $key, $credentials = [], $options = [])
    {
        if (!empty($credentials)) {
            foreach ($credentials as $setting => $value) {
                $this->getConnection()->setSetting($setting, $value);
            }
        }
        
        $path = "s3://{$bucket}/{$key}";
        
        return $this->toParquet($path, $options);
    }

    public static function readParquetArrow($path)
    {
        $instance = new static;
        
        return $instance->newQuery()->fromRaw("arrow_scan('{$path}')");
    }

    public static function fromIceberg($path, $options = [])
    {
        $instance = new static;
        
        $optionsString = '';
        if (!empty($options)) {
            $optionsString = ', ' . collect($options)->map(function ($value, $key) {
                if (is_bool($value)) {
                    $value = $value ? 'TRUE' : 'FALSE';
                } elseif (is_string($value)) {
                    $value = "'{$value}'";
                }
                return "{$key} = {$value}";
            })->implode(', ');
        }
        
        return $instance->newQuery()->fromRaw("iceberg_scan('{$path}'{$optionsString})");
    }

    public static function fromDeltaLake($path, $options = [])
    {
        $instance = new static;
        
        $optionsString = '';
        if (!empty($options)) {
            $optionsString = ', ' . collect($options)->map(function ($value, $key) {
                return "{$key} = {$value}";
            })->implode(', ');
        }
        
        return $instance->newQuery()->fromRaw("delta_scan('{$path}'{$optionsString})");
    }
}