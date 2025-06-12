<?php

require_once __DIR__ . '/bootstrap.php';

echo "Testing DuckDB connection...\n";

try {
    // Test DB facade
    $conn = DB::connection('duckdb');
    echo "✓ DB facade connection works\n";
    
    // Test creating a table
    $conn->getSchemaBuilder()->create('test_table', function ($table) {
        $table->integer('id');
        $table->string('name');
    });
    echo "✓ Table creation works\n";
    
    // Test inserting data
    $conn->table('test_table')->insert(['id' => 1, 'name' => 'Test']);
    echo "✓ Insert works\n";
    
    // Test selecting data
    $result = $conn->table('test_table')->first();
    echo "✓ Select works: " . json_encode($result) . "\n";
    
    // Clean up
    $conn->getSchemaBuilder()->drop('test_table');
    echo "✓ Drop table works\n";
    
    echo "\nConnection test successful!\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}