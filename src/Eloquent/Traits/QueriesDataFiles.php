<?php

namespace Laraduck\EloquentDuckDB\Eloquent\Traits;

use Illuminate\Support\Str;

trait QueriesDataFiles
{
    public static function fromCsv($path, $options = [])
    {
        $instance = new static;
        
        if (empty($options)) {
            return $instance->newQuery()->fromRaw("read_csv_auto('{$path}')");
        }
        
        $optionsString = collect($options)->map(function ($value, $key) {
            if (is_bool($value)) {
                $value = $value ? 'TRUE' : 'FALSE';
            } elseif (is_array($value)) {
                $value = "['" . implode("', '", $value) . "']";
            } elseif (is_string($value) && !is_numeric($value)) {
                $value = "'{$value}'";
            }
            return "{$key} = {$value}";
        })->implode(', ');
        
        return $instance->newQuery()->fromRaw("read_csv('{$path}', {$optionsString})");
    }

    public static function fromJsonFile($path, $options = [])
    {
        $instance = new static;
        
        if (empty($options)) {
            return $instance->newQuery()->fromRaw("read_json_auto('{$path}')");
        }
        
        $optionsString = collect($options)->map(function ($value, $key) {
            if (is_bool($value)) {
                $value = $value ? 'TRUE' : 'FALSE';
            } elseif (is_string($value)) {
                $value = "'{$value}'";
            }
            return "{$key} = {$value}";
        })->implode(', ');
        
        return $instance->newQuery()->fromRaw("read_json('{$path}', {$optionsString})");
    }

    public function whereJsonPath($column, $path, $operator, $value)
    {
        $jsonPath = "{$column}->>'$.{$path}'";
        return $this->whereRaw("{$jsonPath} {$operator} ?", [$value]);
    }

    public function whereJsonContainsKey($column, $key)
    {
        return $this->whereRaw("json_contains({$column}, '$.\"{$key}\"')");
    }

    public function selectJsonExtract($column, $path, $as = null)
    {
        $extraction = "json_extract({$column}, '$.{$path}')";
        
        if ($as) {
            $extraction .= " AS {$as}";
        }
        
        return $this->addSelect($this->raw($extraction));
    }

    public function toCsv($path, $options = [])
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
        
        $sql = "COPY ({$query}) TO '{$path}' (FORMAT CSV" . 
               ($optionsString ? ", {$optionsString}" : "") . ")";
        
        return $this->getConnection()->statement($sql, $bindings);
    }

    public function toJsonFile($path, $options = [])
    {
        $query = $this->toSql();
        $bindings = $this->getBindings();
        
        $optionsString = collect($options)->map(function ($value, $key) {
            return "{$key} {$value}";
        })->implode(', ');
        
        $sql = "COPY ({$query}) TO '{$path}' (FORMAT JSON" . 
               ($optionsString ? ", {$optionsString}" : "") . ")";
        
        return $this->getConnection()->statement($sql, $bindings);
    }

    public static function fromExcel($path, $sheet = null, $options = [])
    {
        $instance = new static;
        
        $functionName = 'read_excel';
        $params = ["'{$path}'"];
        
        if ($sheet !== null) {
            $params[] = "sheet = '{$sheet}'";
        }
        
        foreach ($options as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'TRUE' : 'FALSE';
            } elseif (is_string($value)) {
                $value = "'{$value}'";
            }
            $params[] = "{$key} = {$value}";
        }
        
        $function = $functionName . '(' . implode(', ', $params) . ')';
        
        return $instance->newQuery()->fromRaw($function);
    }

    public static function fromGoogleSheets($url, $options = [])
    {
        $instance = new static;
        
        $optionsString = collect($options)->map(function ($value, $key) {
            return "{$key} = {$value}";
        })->implode(', ');
        
        $function = "read_csv_auto('{$url}'" . ($optionsString ? ", {$optionsString}" : "") . ")";
        
        return $instance->newQuery()->fromRaw($function);
    }

    public static function fromHttp($url, $format = 'auto', $options = [])
    {
        $instance = new static;
        
        $extension = $instance->getConnection()->getDuckDB();
        if ($extension) {
            $extension->query("INSTALL httpfs; LOAD httpfs;");
        }
        
        switch ($format) {
            case 'csv':
                return static::fromCsv($url, $options);
            case 'json':
                return static::fromJsonFile($url, $options);
            case 'parquet':
                return static::fromParquet($url);
            case 'auto':
            default:
                $fileExtension = Str::afterLast(parse_url($url, PHP_URL_PATH), '.');
                switch ($fileExtension) {
                    case 'csv':
                    case 'tsv':
                        return static::fromCsv($url, $options);
                    case 'json':
                    case 'jsonl':
                        return static::fromJsonFile($url, $options);
                    case 'parquet':
                        return static::fromParquet($url);
                    default:
                        return static::fromCsv($url, $options);
                }
        }
    }

    public static function readCsvMetadata($path)
    {
        $instance = new static;
        
        return collect($instance->getConnection()->select(
            "SELECT * FROM csv_metadata('{$path}')"
        ))->first();
    }

    public static function sniffCsvSchema($path, $sampleSize = 1000)
    {
        $instance = new static;
        
        return collect($instance->getConnection()->select(
            "SELECT * FROM sniff_csv('{$path}', sample_size = {$sampleSize})"
        ))->first();
    }

    public function exportJsonLines($path)
    {
        return $this->toJsonFile($path, ['array' => true]);
    }

    public static function fromJsonLines($path)
    {
        return static::fromJsonFile($path, ['format' => 'newline_delimited']);
    }

    public static function fromTsv($path, $options = [])
    {
        $options['delimiter'] = '\t';
        return static::fromCsv($path, $options);
    }

    public function toTsv($path, $options = [])
    {
        $options['delimiter'] = '\t';
        return $this->toCsv($path, $options);
    }
}