<?php

namespace Laraduck\EloquentDuckDB\Tests\Unit;

use Laraduck\EloquentDuckDB\Query\Builder;
use Laraduck\EloquentDuckDB\Query\Grammars\DuckDBGrammar;
use Laraduck\EloquentDuckDB\Tests\TestCase;
use Mockery as m;

class DuckDBGrammarTest extends TestCase
{
    protected $grammar;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->grammar = new DuckDBGrammar;
    }
    
    public function test_compile_select_with_parquet_file()
    {
        $builder = $this->getBuilder();
        $builder->from('data.parquet');
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertEquals("select * from 'data.parquet'", $sql);
    }
    
    public function test_compile_select_with_csv_function()
    {
        $builder = $this->getBuilder();
        $builder->from("read_csv_auto('/path/to/file.csv')");
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertEquals("select * from read_csv_auto('/path/to/file.csv')", $sql);
    }
    
    public function test_compile_window_function()
    {
        $builder = $this->getBuilder();
        $builder->from('employees');
        
        $window = [
            'partition' => ['department'],
            'order' => 'salary DESC'
        ];
        
        $windowSql = $this->grammar->compileWindow($builder, $window);
        
        $this->assertEquals('over (partition by "department" order by salary DESC)', $windowSql);
    }
    
    public function test_compile_qualify_clause()
    {
        $builder = $this->getBuilder();
        $builder->from('employees');
        $builder->qualify = [
            ['type' => 'Basic', 'column' => 'row_num', 'operator' => '<=', 'value' => 3, 'boolean' => 'and']
        ];
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertStringContainsString('qualify', $sql);
    }
    
    public function test_compile_sample_clause()
    {
        $builder = $this->getBuilder();
        $builder->from('users');
        $builder->sample = ['value' => 0.1, 'method' => 'BERNOULLI'];
        
        $sql = $this->grammar->compileSelect($builder);
        
        $this->assertStringContainsString('using sample 10 percent (BERNOULLI)', $sql);
    }
    
    public function test_compile_group_by_all()
    {
        $groupByAll = $this->grammar->compileGroupByAll($this->getBuilder());
        
        $this->assertEquals('group by all', $groupByAll);
    }
    
    public function test_compile_copy_to_parquet()
    {
        $builder = $this->getBuilder();
        $builder->from('users')->where('active', true);
        
        $sql = $this->grammar->compileCopy($builder, '/path/to/output.parquet', 'parquet', ['compression' => 'snappy']);
        
        $this->assertStringContainsString("copy (select * from", $sql);
        $this->assertStringContainsString("to '/path/to/output.parquet'", $sql);
        $this->assertStringContainsString("format parquet", $sql);
        $this->assertStringContainsString("compression snappy", $sql);
    }
    
    public function test_wrap_file_tables()
    {
        $wrapped = $this->grammar->wrapTable('data.parquet');
        $this->assertEquals("'data.parquet'", $wrapped);
        
        $wrapped = $this->grammar->wrapTable('regular_table');
        $this->assertEquals('"regular_table"', $wrapped);
    }
    
    protected function getBuilder()
    {
        $connection = m::mock(\Illuminate\Database\Connection::class);
        $processor = m::mock(\Illuminate\Database\Query\Processors\Processor::class);
        
        return new Builder($connection, $this->grammar, $processor);
    }
}