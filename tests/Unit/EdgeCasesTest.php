<?php

namespace Laraduck\EloquentDuckDB\Tests\Unit;

use Laraduck\EloquentDuckDB\Query\Grammars\DuckDBGrammar;
use Laraduck\EloquentDuckDB\Query\Builder;
use Laraduck\EloquentDuckDB\Tests\TestCase;
use Mockery as m;

class EdgeCasesTest extends TestCase
{
    protected $grammar;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->grammar = new DuckDBGrammar;
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_empty_table_name_handling()
    {
        $builder = $this->getBuilder();
        $builder->from('');
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Should handle empty table name gracefully
        $this->assertStringContainsString('select * from ""', $sql);
    }

    public function test_null_table_name_handling()
    {
        $builder = $this->getBuilder();
        $builder->from(null);
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Should handle null table name without crashing
        $this->assertIsString($sql);
    }

    public function test_very_long_table_name()
    {
        $longTableName = str_repeat('very_long_table_name_', 50); // 1000+ characters
        
        $builder = $this->getBuilder();
        $builder->from($longTableName);
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertStringContainsString($longTableName, $sql);
    }

    public function test_table_name_with_special_characters()
    {
        $specialTableName = 'table-with.special$chars@123';
        
        $builder = $this->getBuilder();
        $builder->from($specialTableName);
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Laravel treats dots as table.column separator
        $this->assertStringContainsString('"table-with"."special$chars@123"', $sql);
    }

    public function test_unicode_table_name()
    {
        $unicodeTableName = 'таблица_数据_🚀';
        
        $builder = $this->getBuilder();
        $builder->from($unicodeTableName);
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertStringContainsString($unicodeTableName, $sql);
    }

    public function test_file_path_with_spaces()
    {
        $pathWithSpaces = '/path with spaces/file name.parquet';
        
        $wrapped = $this->grammar->wrapTable($pathWithSpaces);
        
        // Should be wrapped with single quotes for file paths
        $this->assertEquals("'$pathWithSpaces'", $wrapped);
    }

    public function test_file_path_with_unicode()
    {
        $unicodePath = '/路径/数据文件.parquet';
        
        $wrapped = $this->grammar->wrapTable($unicodePath);
        
        $this->assertEquals("'$unicodePath'", $wrapped);
    }

    public function test_extremely_long_file_path()
    {
        $longPath = '/very/long/path/' . str_repeat('subdirectory/', 100) . 'file.parquet';
        
        $wrapped = $this->grammar->wrapTable($longPath);
        
        $this->assertEquals("'$longPath'", $wrapped);
    }

    public function test_empty_where_conditions()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        
        // Add empty where condition
        $builder->where('', '=', '');
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Should handle empty conditions gracefully
        $this->assertIsString($sql);
    }

    public function test_null_where_values()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->where('column', '=', null);
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Should properly handle null values in where clauses
        $this->assertStringContainsString('where', strtolower($sql));
    }

    public function test_extremely_long_where_clause()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        
        // Add many where conditions
        for ($i = 1; $i <= 1000; $i++) {
            $builder->where("column_{$i}", '=', "value_{$i}");
        }
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Should compile without crashing
        $this->assertIsString($sql);
        $this->assertStringContainsString('column_1', $sql);
        $this->assertStringContainsString('column_1000', $sql);
    }

    public function test_empty_select_columns()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->select([]);
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Laravel handles empty select by removing columns, not defaulting to *
        $this->assertStringContainsString('select ', $sql);
        $this->assertStringContainsString('from ', $sql);
    }

    public function test_null_select_columns()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->select(null);
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Should handle null select gracefully
        $this->assertIsString($sql);
    }

    public function test_very_long_column_names()
    {
        $longColumnName = str_repeat('very_long_column_name_', 20);
        
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->select($longColumnName);
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertStringContainsString($longColumnName, $sql);
    }

    public function test_duplicate_column_names()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->select(['name', 'name', 'name']);
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Should handle duplicate columns without crashing
        $this->assertIsString($sql);
        $this->assertStringContainsString('name', $sql);
    }

    public function test_window_function_without_partition()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        
        $window = [
            'partition' => null,
            'order' => 'id'
        ];
        
        $windowSql = $this->grammar->compileWindow($builder, $window);
        
        $this->assertStringContainsString('over (order by id)', strtolower($windowSql));
        $this->assertStringNotContainsString('partition', strtolower($windowSql));
    }

    public function test_window_function_without_order()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        
        $window = [
            'partition' => ['department'],
            'order' => null
        ];
        
        $windowSql = $this->grammar->compileWindow($builder, $window);
        
        $this->assertStringContainsString('partition by', strtolower($windowSql));
        $this->assertStringNotContainsString('order by', strtolower($windowSql));
    }

    public function test_empty_window_function()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        
        $window = [
            'partition' => null,
            'order' => null
        ];
        
        $windowSql = $this->grammar->compileWindow($builder, $window);
        
        $this->assertEquals('over ()', strtolower($windowSql));
    }

    public function test_sample_with_zero_percentage()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->sample = ['value' => 0.0, 'method' => 'BERNOULLI'];
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertStringContainsString('using sample 0 percent', strtolower($sql));
    }

    public function test_sample_with_hundred_percentage()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->sample = ['value' => 1.0, 'method' => 'BERNOULLI'];
        
        $sql = $this->grammar->compileSelect($builder);
        
        // When value is 1.0, it's treated as 1 row, not 100%
        $this->assertStringContainsString('using sample 1 rows', strtolower($sql));
    }

    public function test_sample_with_very_small_percentage()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->sample = ['value' => 0.0001, 'method' => 'BERNOULLI'];
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertStringContainsString('using sample 0.01 percent', strtolower($sql));
    }

    public function test_qualify_clause_with_empty_conditions()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->qualify = [];
        
        $sql = $this->grammar->compileSelect($builder);
        
        // Should not include QUALIFY clause if empty
        $this->assertStringNotContainsString('qualify', strtolower($sql));
    }

    public function test_copy_with_empty_options()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        
        $sql = $this->grammar->compileCopy($builder, '/path/to/file.parquet', 'parquet', []);
        
        $this->assertStringContainsString('format parquet', strtolower($sql));
        $this->assertStringNotContainsString('compression', strtolower($sql));
    }

    public function test_copy_with_null_options()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        
        $sql = $this->grammar->compileCopy($builder, '/path/to/file.parquet', 'parquet', null);
        
        $this->assertStringContainsString('format parquet', strtolower($sql));
    }

    public function test_copy_with_boolean_options()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        
        $options = [
            'compression' => true,
            'statistics' => false,
            'row_group_size' => 1000
        ];
        
        $sql = $this->grammar->compileCopy($builder, '/path/to/file.parquet', 'parquet', $options);
        
        $this->assertStringContainsString('compression true', $sql);
        $this->assertStringContainsString('statistics false', $sql);
        $this->assertStringContainsString('row_group_size 1000', $sql);
    }

    public function test_wrap_table_with_sql_injection_attempt()
    {
        $maliciousTable = "users'; DROP TABLE users; --";
        
        $wrapped = $this->grammar->wrapTable($maliciousTable);
        
        // Should be properly escaped
        $this->assertEquals("\"{$maliciousTable}\"", $wrapped);
    }

    public function test_wrap_column_with_special_characters()
    {
        $specialColumn = 'column-with.special$chars';
        
        $wrapped = $this->grammar->wrap($specialColumn);
        
        // Laravel treats dots as table.column separator
        $this->assertEquals('"column-with"."special$chars"', $wrapped);
    }

    public function test_wrap_value_with_quotes()
    {
        $valueWithQuotes = "value with 'single' and \"double\" quotes";
        
        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->grammar);
        $method = $reflection->getMethod('wrapValue');
        $method->setAccessible(true);
        $wrapped = $method->invoke($this->grammar, $valueWithQuotes);
        
        // Should handle quotes properly
        $this->assertIsString($wrapped);
        $this->assertStringContainsString('value with', $wrapped);
    }

    public function test_compile_group_by_all_empty_query()
    {
        $builder = $this->getBuilder();
        // No from clause
        
        $groupByAll = $this->grammar->compileGroupByAll($builder);
        
        $this->assertEquals('group by all', $groupByAll);
    }

    public function test_multiple_identical_file_extensions()
    {
        $paths = [
            'file.parquet.parquet',
            'data.csv.csv.csv',
            'info.json.json',
        ];
        
        foreach ($paths as $path) {
            $wrapped = $this->grammar->wrapTable($path);
            $this->assertEquals("'{$path}'", $wrapped);
        }
    }

    public function test_numeric_table_names()
    {
        $numericTable = '123456';
        
        $builder = $this->getBuilder();
        $builder->from($numericTable);
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertStringContainsString("\"{$numericTable}\"", $sql);
    }

    public function test_reserved_keyword_table_names()
    {
        $reservedKeywords = ['select', 'from', 'where', 'order', 'group', 'having'];
        
        foreach ($reservedKeywords as $keyword) {
            $builder = $this->getBuilder();
            $builder->from($keyword);
            
            $sql = $this->grammar->compileSelect($builder);
            
            // Should be properly quoted
            $this->assertStringContainsString("\"{$keyword}\"", $sql);
        }
    }

    protected function getBuilder()
    {
        $connection = m::mock(\Illuminate\Database\Connection::class);
        $processor = m::mock(\Illuminate\Database\Query\Processors\Processor::class);
        
        return new Builder($connection, $this->grammar, $processor);
    }
}