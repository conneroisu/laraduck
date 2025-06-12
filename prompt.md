# Creating a DuckDB Eloquent ORM extension for Laravel from scratch

DuckDB is an embedded OLAP database designed for analytical workloads, offering unique features that present both opportunities and challenges for Laravel ORM integration. This comprehensive guide covers everything needed to build a DuckDB extension for Laravel's Eloquent ORM, from PHP bindings to implementation patterns.

## DuckDB PHP bindings and driver options

### Available PHP solutions

**As of 2025, there is no official native PHP extension for DuckDB**. However, the community has developed several FFI-based solutions, with **satur.io/duckdb-php** being the most mature and production-ready option.

```bash
composer require satur.io/duckdb
```

This library requires PHP 8.3+, ext-ffi, ext-bcmath, and ext-zend-opcache (recommended). It provides comprehensive features including:

- Direct C API access via FFI
- Prepared statements support
- Appender API for bulk inserts
- Cross-platform compatibility
- Remote file querying capabilities

Basic usage example:

```php
use Saturio\DuckDB\DuckDB;

// Persistent connection
$duckDB = DuckDB::create('my_database.db');
$duckDB->query('CREATE TABLE users (id INTEGER, name VARCHAR);');

// Prepared statements
$stmt = $duckDB->preparedStatement('SELECT * FROM users WHERE id = ?');
$stmt->bindParam(1, 1);
$result = $stmt->execute();
```

## Laravel database driver architecture

### Core components to implement

Creating a custom Laravel database driver requires implementing several interconnected components that work together to provide database abstraction:

**1. Connection Class** - The heart of the driver:

```php
<?php
namespace App\Database\DuckDB;

use Illuminate\Database\Connection;

class DuckDBConnection extends Connection
{
    protected $duckdb;

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);
        $this->duckdb = DuckDB::create($database);
    }

    protected function getDefaultQueryGrammar()
    {
        ($grammar = new DuckDBQueryGrammar)->setConnection($this);
        return $this->withTablePrefix($grammar);
    }

    protected function getDefaultSchemaGrammar()
    {
        ($grammar = new DuckDBSchemaGrammar)->setConnection($this);
        return $this->withTablePrefix($grammar);
    }

    protected function getDefaultPostProcessor()
    {
        return new DuckDBProcessor;
    }

    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }
        return new DuckDBSchemaBuilder($this);
    }
}
```

**2. Connector Class** - Establishes connections:

```php
<?php
namespace App\Database\DuckDB\Connectors;

use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Saturio\DuckDB\DuckDB;

class DuckDBConnector extends Connector implements ConnectorInterface
{
    public function connect(array $config)
    {
        $database = $config['database'] ?? ':memory:';

        // Create DuckDB instance
        $duckdb = DuckDB::create($database);

        // Enable extensions if configured
        if (isset($config['extensions'])) {
            foreach ($config['extensions'] as $extension) {
                $duckdb->query("INSTALL {$extension}; LOAD {$extension};");
            }
        }

        // Return a PDO-compatible wrapper
        return new DuckDBPDOWrapper($duckdb);
    }
}
```

**3. Service Provider Registration**:

```php
<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connection;
use App\Database\DuckDB\DuckDBConnection;
use App\Database\DuckDB\Connectors\DuckDBConnector;

class DuckDBServiceProvider extends ServiceProvider
{
    public function register()
    {
        Connection::resolverFor('duckdb', function ($connection, $database, $prefix, $config) {
            return new DuckDBConnection($connection, $database, $prefix, $config);
        });

        $this->app['db']->extend('duckdb', function ($config, $name) {
            $config['name'] = $name;
            $connector = new DuckDBConnector();
            $connection = $connector->connect($config);

            return new DuckDBConnection($connection, $config['database'], $config['prefix'] ?? '', $config);
        });
    }
}
```

## Eloquent ORM extension points

### Query Grammar implementation

The Query Grammar translates Laravel's query builder syntax into DuckDB-specific SQL:

```php
<?php
namespace App\Database\DuckDB\Query\Grammars;

use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;
use Illuminate\Database\Query\Builder;

class DuckDBQueryGrammar extends BaseGrammar
{
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * Compile a "from" clause with file support.
     */
    protected function compileFrom(Builder $query, $from)
    {
        // Support querying files directly
        if (str_contains($from, '.parquet') || str_contains($from, '.csv')) {
            return "FROM '{$from}'";
        }

        return 'FROM ' . $this->wrapTable($from);
    }

    /**
     * Compile window function support.
     */
    public function compileWindow(Builder $query, $window)
    {
        $over = [];

        if (isset($window['partition'])) {
            $over[] = 'PARTITION BY ' . $this->columnize($window['partition']);
        }

        if (isset($window['order'])) {
            $over[] = 'ORDER BY ' . $this->compileOrderBy($query, $window['order']);
        }

        return 'OVER (' . implode(' ', $over) . ')';
    }

    /**
     * Compile QUALIFY clause for filtering window function results.
     */
    public function compileQualify(Builder $query)
    {
        if (!isset($query->qualify)) {
            return '';
        }

        return 'QUALIFY ' . $this->compileWheres($query, $query->qualify);
    }
}
```

### Schema Grammar for DuckDB

```php
<?php
namespace App\Database\DuckDB\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar as BaseGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

class DuckDBSchemaGrammar extends BaseGrammar
{
    protected $modifiers = ['Nullable', 'Default', 'Comment'];

    public function compileCreate(Blueprint $blueprint, Fluent $command)
    {
        $columns = implode(', ', $this->getColumns($blueprint));

        $sql = sprintf(
            'CREATE %sTABLE %s (%s)',
            $command->temporary ? 'TEMPORARY ' : '',
            $this->wrapTable($blueprint),
            $columns
        );

        return $sql;
    }

    /**
     * Create the column definition for DuckDB-specific types.
     */
    protected function typeList(Fluent $column)
    {
        return "{$column->type}[]";
    }

    protected function typeStruct(Fluent $column)
    {
        $fields = collect($column->fields)->map(function ($field, $name) {
            return "{$name} {$field}";
        })->implode(', ');

        return "STRUCT({$fields})";
    }

    protected function typeDecimal128(Fluent $column)
    {
        return "DECIMAL(38, {$column->scale})";
    }
}
```

## Implementation details for DuckDB features

### Analytical queries (OLAP)

Extend the query builder with analytical capabilities:

```php
<?php
namespace App\Database\DuckDB\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;

class DuckDBBuilder extends BaseBuilder
{
    protected $sample;
    protected $qualify = [];

    /**
     * Add a SAMPLE clause for statistical sampling.
     */
    public function sample($value, $method = 'BERNOULLI')
    {
        $this->sample = [
            'value' => $value,
            'method' => $method
        ];

        return $this;
    }

    /**
     * Add analytical window functions.
     */
    public function window($function, $partitionBy = null, $orderBy = null)
    {
        $window = [];

        if ($partitionBy) {
            $window['partition'] = is_array($partitionBy) ? $partitionBy : [$partitionBy];
        }

        if ($orderBy) {
            $window['order'] = $orderBy;
        }

        return $this->selectRaw("{$function} OVER (" . $this->grammar->compileWindow($this, $window) . ")");
    }

    /**
     * Add QUALIFY clause for filtering window results.
     */
    public function qualify($column, $operator = null, $value = null)
    {
        $this->qualify[] = compact('column', 'operator', 'value');
        return $this;
    }

    /**
     * Group by all non-aggregate columns (DuckDB feature).
     */
    public function groupByAll()
    {
        return $this->groupByRaw('ALL');
    }
}
```

### Parquet file support

Implement file-based models and query methods:

```php
<?php
namespace App\Database\DuckDB\Eloquent;

trait QueriesFiles
{
    /**
     * Query Parquet files directly.
     */
    public static function fromParquet($path, $as = null)
    {
        $instance = new static;

        return $instance->newQuery()->fromRaw(
            $as ? "'{$path}' AS {$as}" : "'{$path}'"
        );
    }

    /**
     * Query multiple Parquet files with glob patterns.
     */
    public static function fromParquetGlob($pattern)
    {
        return static::fromParquet($pattern);
    }

    /**
     * Write query results to Parquet file.
     */
    public function toParquet($path, $options = [])
    {
        $query = $this->toSql();
        $bindings = $this->getBindings();

        $optionsString = collect($options)->map(function ($value, $key) {
            return "{$key} = {$value}";
        })->implode(', ');

        $sql = "COPY ({$query}) TO '{$path}' (FORMAT PARQUET" .
               ($optionsString ? ", {$optionsString}" : "") . ")";

        return $this->getConnection()->statement($sql, $bindings);
    }
}
```

### CSV/JSON file querying

```php
<?php
namespace App\Database\DuckDB\Eloquent;

trait QueriesDataFiles
{
    /**
     * Query CSV files with automatic schema detection.
     */
    public static function fromCsv($path, $options = [])
    {
        $instance = new static;

        $optionsString = collect($options)->map(function ($value, $key) {
            return "{$key} = {$value}";
        })->implode(', ');

        $function = $optionsString
            ? "read_csv('{$path}', {$optionsString})"
            : "read_csv_auto('{$path}')";

        return $instance->newQuery()->fromRaw($function);
    }

    /**
     * Query JSON files with automatic schema inference.
     */
    public static function fromJson($path, $options = [])
    {
        $instance = new static;

        $function = empty($options)
            ? "read_json_auto('{$path}')"
            : "read_json('{$path}', " . json_encode($options) . ")";

        return $instance->newQuery()->fromRaw($function);
    }

    /**
     * Query nested JSON data with arrow operators.
     */
    public function whereJsonPath($column, $path, $operator, $value)
    {
        $jsonPath = "{$column}->>'{$path}'";
        return $this->whereRaw("{$jsonPath} {$operator} ?", [$value]);
    }
}
```

### Window functions and CTEs

```php
<?php
namespace App\Database\DuckDB\Query;

trait SupportsAdvancedQueries
{
    protected $ctes = [];

    /**
     * Add a common table expression to the query.
     */
    public function withCte($name, $query, $columns = null, $materialized = true)
    {
        $columns = $columns ? '(' . implode(', ', $columns) . ')' : '';
        $materialized = $materialized ? 'MATERIALIZED' : 'NOT MATERIALIZED';

        if ($query instanceof \Closure) {
            $callback = $query;
            $query = $this->forSubQuery();
            $callback($query);
        }

        $sql = $query instanceof self ? $query->toSql() : $query;

        $this->ctes[] = "{$name}{$columns} AS {$materialized} ({$sql})";

        if ($query instanceof self) {
            $this->addBinding($query->getBindings(), 'cte');
        }

        return $this;
    }

    /**
     * Add a recursive CTE.
     */
    public function withRecursiveCte($name, $baseQuery, $recursiveQuery, $columns = null)
    {
        $this->ctes[] = "RECURSIVE {$name}" .
                       ($columns ? '(' . implode(', ', $columns) . ')' : '') .
                       " AS ({$baseQuery} UNION {$recursiveQuery})";

        return $this;
    }

    /**
     * Advanced window function with frame specification.
     */
    public function windowFrame($function, $partitionBy, $orderBy, $frame)
    {
        $over = [];

        if ($partitionBy) {
            $over[] = 'PARTITION BY ' . $this->grammar->columnize($partitionBy);
        }

        if ($orderBy) {
            $over[] = 'ORDER BY ' . $orderBy;
        }

        if ($frame) {
            $over[] = $frame; // e.g., "ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING"
        }

        return $this->selectRaw("{$function} OVER (" . implode(' ', $over) . ")");
    }
}
```

## Package structure for Laravel database driver extension

### Recommended directory structure

```
src/
├── Config/
│   └── duckdb.php
├── Connectors/
│   └── DuckDBConnector.php
├── Database/
│   ├── DuckDBConnection.php
│   └── DuckDBPDOWrapper.php
├── Eloquent/
│   ├── AnalyticalModel.php
│   ├── Builder.php
│   └── Traits/
│       ├── QueriesFiles.php
│       ├── QueriesDataFiles.php
│       └── SupportsAdvancedQueries.php
├── Query/
│   ├── Builder.php
│   ├── Grammars/
│   │   └── DuckDBGrammar.php
│   └── Processors/
│       └── DuckDBProcessor.php
├── Schema/
│   ├── Builder.php
│   ├── Blueprint.php
│   └── Grammars/
│       └── DuckDBGrammar.php
└── DuckDBServiceProvider.php

tests/
├── Feature/
│   ├── ConnectionTest.php
│   ├── QueryBuilderTest.php
│   └── SchemaBuilderTest.php
└── Unit/
    ├── GrammarTest.php
    └── ProcessorTest.php
```

### Configuration file (config/duckdb.php)

```php
<?php

return [
    'connections' => [
        'duckdb' => [
            'driver' => 'duckdb',
            'database' => env('DUCKDB_DATABASE', ':memory:'),
            'read_only' => env('DUCKDB_READ_ONLY', false),
            'threads' => env('DUCKDB_THREADS', null),
            'memory_limit' => env('DUCKDB_MEMORY_LIMIT', null),
            'extensions' => [
                'httpfs',
                'parquet',
                'json',
            ],
            'settings' => [
                's3_region' => env('AWS_DEFAULT_REGION'),
                's3_access_key_id' => env('AWS_ACCESS_KEY_ID'),
                's3_secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ],
    ],
];
```

## Challenges adapting columnar databases for ORMs

### Paradigm mismatch

The fundamental challenge lies in the architectural differences between OLAP (DuckDB) and OLTP (traditional databases) systems:

1. **Data Organization**: DuckDB stores data by columns, optimized for analytical queries, while ORMs assume row-based storage
2. **Write Performance**: Columnar databases are inherently slower for single-row writes, which conflicts with typical ORM patterns
3. **Transaction Semantics**: DuckDB's transaction model differs from traditional ACID expectations

### Mitigation strategies

```php
<?php
namespace App\Database\DuckDB\Eloquent;

abstract class AnalyticalModel extends Model
{
    /**
     * Optimize for bulk operations over single-row operations.
     */
    public static function insertBatch(array $data, $chunkSize = 10000)
    {
        $instance = new static;

        return collect($data)->chunk($chunkSize)->each(function ($chunk) use ($instance) {
            $instance->getConnection()
                    ->table($instance->getTable())
                    ->insertUsing(array_keys($chunk->first()), $chunk->toArray());
        });
    }

    /**
     * Encourage column specification for projection pushdown.
     */
    public function scopeSelectColumns($query, array $columns)
    {
        return $query->select($columns);
    }

    /**
     * Disable single-row operations by default.
     */
    public function save(array $options = [])
    {
        if (!$this->allowsSingleRowOperations()) {
            throw new \RuntimeException(
                'Single-row operations are not recommended for analytical models. ' .
                'Use batch operations instead.'
            );
        }

        return parent::save($options);
    }

    protected function allowsSingleRowOperations()
    {
        return false;
    }
}
```

## Performance considerations and best practices

### Query optimization

1. **Column Selection**: Always specify columns explicitly to leverage columnar storage advantages
2. **Predicate Pushdown**: Ensure filters are applied at the storage layer
3. **Batch Operations**: Group operations to minimize columnar reorganization overhead

```php
// Optimized query pattern
User::selectColumns(['id', 'name', 'email'])
    ->where('created_at', '>=', '2024-01-01')
    ->chunkById(10000, function ($users) {
        // Process in batches
    });

// Analytical aggregation
User::query()
    ->selectRaw('DATE_TRUNC(\'month\', created_at) as month')
    ->selectRaw('COUNT(*) as user_count')
    ->groupByRaw('DATE_TRUNC(\'month\', created_at)')
    ->get();
```

### Caching strategies

```php
<?php
namespace App\Database\DuckDB\Cache;

class AnalyticalQueryCache
{
    protected $cache;
    protected $defaultTtl = 3600; // 1 hour

    public function remember($key, $query, $ttl = null)
    {
        return $this->cache->remember($key, $ttl ?? $this->defaultTtl, function () use ($query) {
            return $query->get();
        });
    }

    public function rememberForever($key, $query)
    {
        return $this->cache->rememberForever($key, function () use ($query) {
            return $query->get();
        });
    }

    public function invalidatePattern($pattern)
    {
        // Invalidate all cache keys matching pattern
        $this->cache->flush(function ($key) use ($pattern) {
            return preg_match($pattern, $key);
        });
    }
}
```

## Testing strategies for the extension

### Unit testing the grammar

```php
<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Database\DuckDB\Query\Grammars\DuckDBGrammar;
use Illuminate\Database\Query\Builder;

class DuckDBGrammarTest extends TestCase
{
    protected $grammar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grammar = new DuckDBGrammar;
    }

    public function test_compile_parquet_from_clause()
    {
        $builder = $this->getBuilder();
        $builder->from('data.parquet');

        $sql = $this->grammar->compileSelect($builder);

        $this->assertEquals("select * from 'data.parquet'", $sql);
    }

    public function test_compile_window_function()
    {
        $builder = $this->getBuilder();
        $builder->selectRaw('ROW_NUMBER() OVER (PARTITION BY department ORDER BY salary DESC)');

        $sql = $this->grammar->compileSelect($builder);

        $this->assertStringContainsString('OVER (PARTITION BY', $sql);
    }

    public function test_compile_qualify_clause()
    {
        $builder = $this->getBuilder();
        $builder->from('employees')
                ->qualify = [['column' => 'row_num', 'operator' => '<=', 'value' => 3]];

        $sql = $this->grammar->compileSelect($builder);

        $this->assertStringContainsString('QUALIFY', $sql);
    }

    protected function getBuilder()
    {
        $connection = $this->createMock(\Illuminate\Database\Connection::class);
        $processor = $this->createMock(\Illuminate\Database\Query\Processors\Processor::class);

        return new Builder($connection, $this->grammar, $processor);
    }
}
```

### Integration testing

```php
<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Database\DuckDB\Eloquent\AnalyticalModel;

class DuckDBIntegrationTest extends TestCase
{
    public function test_can_query_parquet_files()
    {
        $results = AnalyticalModel::fromParquet('tests/fixtures/data.parquet')
            ->where('year', 2024)
            ->get();

        $this->assertNotEmpty($results);
        $this->assertEquals(2024, $results->first()->year);
    }

    public function test_can_perform_window_functions()
    {
        $results = TestModel::query()
            ->window('ROW_NUMBER()', ['department'], ['salary DESC'])
            ->qualify('ROW_NUMBER()', '<=', 3)
            ->get();

        $this->assertCount(3, $results->groupBy('department')->first());
    }

    public function test_batch_insert_performance()
    {
        $data = factory(TestModel::class, 10000)->make()->toArray();

        $start = microtime(true);
        TestModel::insertBatch($data);
        $duration = microtime(true) - $start;

        $this->assertLessThan(5, $duration); // Should complete in under 5 seconds
    }
}
```

## Conclusion

Building a DuckDB Eloquent ORM extension for Laravel requires careful consideration of the fundamental differences between analytical and transactional databases. The key to success lies in:

1. **Embracing the analytical paradigm** while maintaining familiar Laravel patterns
2. **Optimizing for bulk operations** and columnar data access patterns
3. **Exposing DuckDB's unique features** through intuitive ORM methods
4. **Providing clear performance guidelines** to developers

By following the architectural patterns and implementation details outlined in this guide, developers can create a powerful extension that brings DuckDB's analytical capabilities to the Laravel ecosystem while maintaining the elegance and simplicity that Laravel developers expect.
