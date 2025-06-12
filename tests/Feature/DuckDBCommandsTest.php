<?php

namespace Laraduck\EloquentDuckDB\Tests\Feature;

use Laraduck\EloquentDuckDB\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DuckDBCommandsTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test table with some data
        try {
            DB::statement('CREATE TABLE test_table (id INTEGER, name VARCHAR, value DOUBLE)');
            DB::statement("INSERT INTO test_table VALUES (1, 'Test 1', 10.5), (2, 'Test 2', 20.3), (3, 'Test 3', 30.7)");
        } catch (\Exception $e) {
            // Table might already exist, ignore
        }
    }

    public function testDuckDBInfoCommand()
    {
        $exitCode = Artisan::call('duckdb:info');
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('DuckDB Connection Information', $output);
        $this->assertStringContainsString('Database Information', $output);
        $this->assertStringContainsString('Memory Usage', $output);
    }

    public function testDuckDBInfoCommandWithSpecificConnection()
    {
        $exitCode = Artisan::call('duckdb:info', ['connection' => 'duckdb']);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Connection Name', $output);
        $this->assertStringContainsString('duckdb', $output);
    }

    public function testDuckDBInfoCommandWithInvalidConnection()
    {
        $exitCode = Artisan::call('duckdb:info', ['connection' => 'invalid']);
        
        $this->assertEquals(1, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('not configured', $output);
    }

    public function testDuckDBFileCommandQuery()
    {
        // Create a test CSV file
        $csvContent = "id,name,value\n1,Test 1,10.5\n2,Test 2,20.3\n";
        $csvPath = sys_get_temp_dir() . '/test_data.csv';
        file_put_contents($csvPath, $csvContent);

        $exitCode = Artisan::call('duckdb:file', [
            'action' => 'query',
            '--file' => $csvPath,
            '--format' => 'csv',
            '--limit' => 10,
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Querying csv file', $output);
        $this->assertStringContainsString('Test 1', $output);

        // Clean up
        unlink($csvPath);
    }

    public function testDuckDBFileCommandInspect()
    {
        // Create a test CSV file
        $csvContent = "id,name,value\n1,Test 1,10.5\n2,Test 2,20.3\n";
        $csvPath = sys_get_temp_dir() . '/test_inspect.csv';
        file_put_contents($csvPath, $csvContent);

        $exitCode = Artisan::call('duckdb:file', [
            'action' => 'inspect',
            '--file' => $csvPath,
            '--format' => 'csv',
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('File Information', $output);
        $this->assertStringContainsString('File Schema', $output);
        $this->assertStringContainsString('Sample Data', $output);

        // Clean up
        unlink($csvPath);
    }

    public function testDuckDBFileCommandExport()
    {
        $exportPath = sys_get_temp_dir() . '/test_export.csv';
        
        // Remove file if it exists
        if (file_exists($exportPath)) {
            unlink($exportPath);
        }

        $exitCode = Artisan::call('duckdb:file', [
            'action' => 'export',
            '--file' => $exportPath,
            '--table' => 'test_table',
            '--format' => 'csv',
            '--header' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Export completed', $output);
        $this->assertTrue(file_exists($exportPath));

        // Verify exported content
        $content = file_get_contents($exportPath);
        $this->assertStringContainsString('Test 1', $content);
        $this->assertStringContainsString('Test 2', $content);

        // Clean up
        unlink($exportPath);
    }

    public function testDuckDBFileCommandImport()
    {
        // Create a test CSV file
        $csvContent = "id,name,value\n4,Test 4,40.1\n5,Test 5,50.2\n";
        $csvPath = sys_get_temp_dir() . '/test_import.csv';
        file_put_contents($csvPath, $csvContent);

        $exitCode = Artisan::call('duckdb:file', [
            'action' => 'import',
            '--file' => $csvPath,
            '--table' => 'imported_table',
            '--format' => 'csv',
            '--header' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Imported', $output);
        $this->assertStringContainsString('rows', $output);

        // Verify imported data
        $count = DB::table('imported_table')->count();
        $this->assertEquals(2, $count);

        $record = DB::table('imported_table')->where('id', 4)->first();
        $this->assertNotNull($record);
        $this->assertEquals('Test 4', $record->name);

        // Clean up
        unlink($csvPath);
    }

    public function testDuckDBFileCommandWithNonExistentFile()
    {
        $exitCode = Artisan::call('duckdb:file', [
            'action' => 'query',
            '--file' => '/non/existent/file.csv',
            '--format' => 'csv',
        ]);

        $this->assertEquals(1, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('File not found', $output);
    }

    public function testDuckDBOptimizeCommand()
    {
        $exitCode = Artisan::call('duckdb:optimize');
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Optimizing DuckDB database', $output);
        $this->assertStringContainsString('Optimization completed', $output);
    }

    public function testDuckDBOptimizeCommandWithAnalyzeOnly()
    {
        $exitCode = Artisan::call('duckdb:optimize', ['--analyze' => true]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Running ANALYZE', $output);
        $this->assertStringContainsString('ANALYZE completed', $output);
    }

    public function testDuckDBOptimizeCommandWithSpecificTable()
    {
        $exitCode = Artisan::call('duckdb:optimize', [
            '--analyze' => true,
            '--table' => 'test_table',
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Analyzed table: test_table', $output);
    }

    public function testDuckDBOptimizeCommandWithStats()
    {
        $exitCode = Artisan::call('duckdb:optimize', [
            '--stats' => true,
            '--analyze' => true,
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Pre-Optimization Statistics', $output);
        $this->assertStringContainsString('Post-Optimization Statistics', $output);
        $this->assertStringContainsString('Tables:', $output);
    }

    public function testDuckDBOptimizeCommandWithInvalidConnection()
    {
        $exitCode = Artisan::call('duckdb:optimize', ['--connection' => 'invalid']);
        
        $this->assertEquals(1, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('not configured', $output);
    }

    public function testDuckDBFileCommandInvalidAction()
    {
        $exitCode = Artisan::call('duckdb:file', ['action' => 'invalid']);
        
        $this->assertEquals(1, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Invalid action: invalid', $output);
        $this->assertStringContainsString('Available actions:', $output);
    }
}