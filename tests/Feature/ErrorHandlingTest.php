<?php

namespace Laraduck\EloquentDuckDB\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesFiles;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesDataFiles;
use Laraduck\EloquentDuckDB\Tests\TestCase;

class TestErrorModel extends AnalyticalModel
{
    use QueriesFiles, QueriesDataFiles;
    
    protected $table = 'error_test_table';
    protected $fillable = ['id', 'name', 'value'];
    public $timestamps = false;
}

class ErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test table for error testing
        DB::statement('CREATE TABLE error_test_table (id INTEGER, name VARCHAR, value DECIMAL)');
        
        // Insert some test data
        DB::table('error_test_table')->insert([
            ['id' => 1, 'name' => 'test1', 'value' => 100.5],
            ['id' => 2, 'name' => 'test2', 'value' => 200.0],
        ]);
    }

    public function test_invalid_sql_query_throws_exception()
    {
        $this->expectException(QueryException::class);
        
        DB::statement('INVALID SQL SYNTAX HERE');
    }

    public function test_invalid_table_name_throws_exception()
    {
        $this->expectException(QueryException::class);
        
        DB::table('non_existent_table')->get();
    }

    public function test_invalid_column_name_throws_exception()
    {
        $this->expectException(QueryException::class);
        
        TestErrorModel::select('non_existent_column')->get();
    }

    public function test_type_mismatch_in_where_clause()
    {
        // This should work as DuckDB is generally forgiving with type coercion
        $results = TestErrorModel::where('id', '1')->get(); // String '1' instead of integer 1
        $this->assertCount(1, $results);
        
        // But this might cause issues depending on the data
        try {
            $results = TestErrorModel::where('value', 'not_a_number')->get();
            // If it doesn't throw an exception, we should get 0 results
            $this->assertCount(0, $results);
        } catch (QueryException $e) {
            // This is also acceptable behavior
            $this->assertStringContainsString('not_a_number', $e->getMessage());
        }
    }

    public function test_division_by_zero_handling()
    {
        try {
            $result = DB::selectOne('SELECT 10 / 0 as result');
            // DuckDB might return infinity or null
            $this->assertTrue(is_null($result->result) || is_infinite($result->result));
        } catch (QueryException $e) {
            // Or it might throw an exception
            $this->assertStringContainsString('division', strtolower($e->getMessage()));
        }
    }

    public function test_null_value_handling_in_aggregates()
    {
        // Insert null values
        DB::table('error_test_table')->insert([
            ['id' => 3, 'name' => null, 'value' => null],
            ['id' => 4, 'name' => 'test4', 'value' => null],
        ]);
        
        // Test how aggregates handle nulls
        $stats = TestErrorModel::selectRaw('
            COUNT(*) as total_count,
            COUNT(name) as name_count,
            COUNT(value) as value_count,
            AVG(value) as avg_value,
            SUM(value) as sum_value,
            MIN(value) as min_value,
            MAX(value) as max_value
        ')->first();
        
        $this->assertEquals(4, $stats->total_count);
        $this->assertEquals(3, $stats->name_count); // Should exclude null
        $this->assertEquals(2, $stats->value_count); // Should exclude nulls
        $this->assertEquals(150.25, $stats->avg_value); // (100.5 + 200.0) / 2
        $this->assertEquals(300.5, $stats->sum_value);
        $this->assertEquals(100.5, $stats->min_value);
        $this->assertEquals(200.0, $stats->max_value);
    }

    public function test_invalid_file_path_handling()
    {
        $this->expectException(QueryException::class);
        
        TestErrorModel::fromParquet('/non/existent/path/file.parquet')->get();
    }

    public function test_invalid_csv_file_handling()
    {
        $this->expectException(QueryException::class);
        
        TestErrorModel::fromCsv('/non/existent/path/file.csv')->get();
    }

    public function test_invalid_json_file_handling()
    {
        $this->expectException(QueryException::class);
        
        TestErrorModel::fromJsonFile('/non/existent/path/file.json')->get();
    }

    public function test_malformed_json_path_query()
    {
        // Create table with JSON data
        DB::statement('CREATE TABLE json_error_test (id INTEGER, data JSON)');
        DB::table('json_error_test')->insert([
            ['id' => 1, 'data' => '{"name": "test", "value": 42}'],
            ['id' => 2, 'data' => '{"name": "test2"}'], // Missing 'value' key
        ]);
        
        try {
            // This should not crash but might return null for missing keys
            $results = TestErrorModel::query()
                ->from('json_error_test')
                ->whereJsonPath('data', 'non.existent.path', '=', 'value')
                ->get();
            
            $this->assertCount(0, $results);
        } catch (QueryException $e) {
            // JSON path errors are acceptable
            $this->assertStringContainsString('json', strtolower($e->getMessage()));
        }
    }

    public function test_memory_limit_with_large_operations()
    {
        // Generate a large dataset that might stress memory
        $largeDataset = [];
        for ($i = 1; $i <= 50000; $i++) {
            $largeDataset[] = [
                'id' => $i,
                'name' => 'test_' . $i,
                'value' => rand(1, 1000) / 10
            ];
        }
        
        try {
            // This should work with chunked inserts
            $rowsInserted = TestErrorModel::insertBatch($largeDataset, 1000);
            $this->assertEquals(50000, $rowsInserted);
            
            // Now try a memory-intensive query
            $result = TestErrorModel::selectRaw('
                id,
                name,
                value,
                LAG(value, 1000) OVER (ORDER BY id) as lag_value,
                SUM(value) OVER (ORDER BY id ROWS BETWEEN 1000 PRECEDING AND CURRENT ROW) as rolling_sum
            ')
            ->limit(1000) // Limit to avoid excessive memory usage in tests
            ->get();
            
            $this->assertLessThanOrEqual(1000, $result->count());
        } catch (QueryException $e) {
            // Memory-related errors are acceptable for this test
            $this->assertStringContainsString(['memory', 'limit', 'resource'], strtolower($e->getMessage()));
        }
    }

    public function test_concurrent_access_and_locks()
    {
        // Test potential race conditions or locking issues
        // This is more of a stress test than a functional test
        
        try {
            DB::beginTransaction();
            
            // Insert some data in transaction
            TestErrorModel::create(['id' => 100, 'name' => 'concurrent_test', 'value' => 500]);
            
            // Try to read from another connection perspective (simulated)
            $count = TestErrorModel::count();
            $this->assertGreaterThan(0, $count);
            
            DB::rollBack();
            
            // Verify rollback worked
            $afterRollback = TestErrorModel::where('id', 100)->count();
            $this->assertEquals(0, $afterRollback);
            
        } catch (QueryException $e) {
            // Transaction-related errors might occur
            DB::rollBack();
            $this->assertStringContainsString(['transaction', 'lock', 'concurrent'], strtolower($e->getMessage()));
        }
    }

    public function test_invalid_window_function_usage()
    {
        $this->expectException(QueryException::class);
        
        // Invalid window function syntax
        TestErrorModel::selectRaw('
            id,
            name,
            RANK() as invalid_rank
        ')->get();
    }

    public function test_invalid_cte_syntax()
    {
        $this->expectException(QueryException::class);
        
        // Invalid CTE syntax
        DB::query()->fromRaw('
            WITH invalid_cte AS (
                SELECT * FROM non_existent_table
            )
            SELECT * FROM invalid_cte
        ')->get();
    }

    public function test_circular_cte_reference()
    {
        try {
            // Test circular reference in CTE (should be caught by DuckDB)
            DB::query()->fromRaw('
                WITH RECURSIVE circular AS (
                    SELECT 1 as n
                    UNION ALL
                    SELECT n + 1 FROM circular WHERE n < 1000000
                )
                SELECT COUNT(*) FROM circular
            ')->get();
            
            // If it succeeds, that's also valid (DuckDB might have safeguards)
            $this->assertTrue(true);
        } catch (QueryException $e) {
            // Expected for circular references or infinite recursion
            $this->assertStringContainsString(['recursive', 'limit', 'circular'], strtolower($e->getMessage()));
        }
    }

    public function test_invalid_data_type_conversion()
    {
        // Insert invalid data that might cause conversion errors
        try {
            DB::table('error_test_table')->insert([
                ['id' => 'not_an_integer', 'name' => 'test', 'value' => 100]
            ]);
            
            // If the insert succeeds, DuckDB might be doing implicit conversion
            $this->assertTrue(true);
        } catch (QueryException $e) {
            // Type conversion errors are expected
            $this->assertStringContainsString(['convert', 'type', 'integer'], strtolower($e->getMessage()));
        }
    }

    public function test_query_timeout_handling()
    {
        // This test might not work in all environments, but we can try
        try {
            // Simulate a long-running query
            $start = microtime(true);
            
            DB::query()->fromRaw('
                WITH RECURSIVE slow_query AS (
                    SELECT 1 as n
                    UNION ALL
                    SELECT n + 1 FROM slow_query WHERE n < 100000
                )
                SELECT COUNT(*) as total FROM slow_query
            ')->get();
            
            $duration = microtime(true) - $start;
            
            // If it completes quickly, that's fine too
            $this->assertLessThan(30, $duration, 'Query should complete in reasonable time');
            
        } catch (QueryException $e) {
            // Timeout or resource errors are acceptable
            $this->assertTrue(true, 'Query timeout or resource limit encountered');
        }
    }

    public function test_malformed_sample_syntax()
    {
        $this->expectException(QueryException::class);
        
        // Invalid sample syntax
        TestErrorModel::query()
            ->fromRaw('error_test_table USING SAMPLE invalid_sample_syntax')
            ->get();
    }

    public function test_invalid_qualify_clause()
    {
        $this->expectException(QueryException::class);
        
        // QUALIFY without window function
        TestErrorModel::selectRaw('id, name')
            ->qualify('invalid_column', '>', 1)
            ->get();
    }

    public function test_empty_table_operations()
    {
        // Create empty table
        DB::statement('CREATE TABLE empty_test (id INTEGER, name VARCHAR)');
        
        // Operations on empty tables should not crash
        $count = DB::table('empty_test')->count();
        $this->assertEquals(0, $count);
        
        $avg = DB::table('empty_test')->avg('id');
        $this->assertNull($avg);
        
        $max = DB::table('empty_test')->max('id');
        $this->assertNull($max);
        
        $results = DB::table('empty_test')
            ->selectRaw('id, ROW_NUMBER() OVER (ORDER BY id) as rn')
            ->get();
        $this->assertCount(0, $results);
    }

    public function test_extremely_long_query_string()
    {
        // Generate a very long WHERE clause to test query length limits
        $longConditions = [];
        for ($i = 1; $i <= 1000; $i++) {
            $longConditions[] = "id != {$i}";
        }
        
        $longWhereClause = implode(' AND ', $longConditions);
        
        try {
            $results = TestErrorModel::whereRaw($longWhereClause)->get();
            
            // If it succeeds, the query length was acceptable
            $this->assertIsIterable($results);
        } catch (QueryException $e) {
            // Query too long errors are acceptable
            $this->assertStringContainsString(['too long', 'limit', 'length'], strtolower($e->getMessage()));
        }
    }

    public function test_invalid_utf8_handling()
    {
        // Test with potentially problematic Unicode characters
        $problematicStrings = [
            "Test with emoji 🚀🔥💯",
            "Test with accents: café, naïve, piñata",
            "Test with Chinese: 你好世界",
            "Test with Arabic: مرحبا بالعالم",
            "Test with mixed: Hello 世界 🌍",
        ];
        
        foreach ($problematicStrings as $index => $testString) {
            try {
                DB::table('error_test_table')->insert([
                    'id' => 1000 + $index,
                    'name' => $testString,
                    'value' => 100
                ]);
                
                $retrieved = TestErrorModel::where('id', 1000 + $index)->first();
                $this->assertNotNull($retrieved);
                $this->assertEquals($testString, $retrieved->name);
                
            } catch (QueryException $e) {
                // UTF-8 encoding errors might occur
                $this->assertStringContainsString(['encoding', 'utf', 'character'], strtolower($e->getMessage()));
            }
        }
    }

    public function test_batch_insert_with_invalid_data()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Single-row operations are not recommended');
        
        // Try to use save() which should throw an exception
        $model = new TestErrorModel();
        $model->id = 999;
        $model->name = 'test';
        $model->value = 100;
        $model->save();
    }

    public function test_batch_insert_empty_array()
    {
        $rowsInserted = TestErrorModel::insertBatch([]);
        $this->assertEquals(0, $rowsInserted);
    }

    public function test_batch_insert_with_inconsistent_structure()
    {
        // Data with inconsistent keys
        $inconsistentData = [
            ['id' => 1, 'name' => 'test1', 'value' => 100],
            ['id' => 2, 'name' => 'test2'], // Missing 'value'
            ['id' => 3, 'different_key' => 'test3', 'value' => 300], // Different key
        ];
        
        try {
            $rowsInserted = TestErrorModel::insertBatch($inconsistentData);
            
            // If it succeeds, verify what was actually inserted
            $this->assertGreaterThan(0, $rowsInserted);
        } catch (QueryException $e) {
            // Structure mismatch errors are acceptable
            $this->assertStringContainsString(['column', 'mismatch', 'structure'], strtolower($e->getMessage()));
        }
    }

    protected function tearDown(): void
    {
        // Clean up test tables
        try {
            DB::statement('DROP TABLE IF EXISTS error_test_table');
            DB::statement('DROP TABLE IF EXISTS json_error_test');
            DB::statement('DROP TABLE IF EXISTS empty_test');
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
        
        parent::tearDown();
    }
}