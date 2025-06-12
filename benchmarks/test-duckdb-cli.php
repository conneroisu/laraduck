<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Laraduck\EloquentDuckDB\DuckDB\DuckDBCLI;

echo "Testing DuckDB CLI wrapper...\n";

try {
    // Create temporary database file
    $tempDb = tempnam(sys_get_temp_dir(), 'duckdb_test_') . '.db';
    $db = DuckDBCLI::create($tempDb);
    echo "✓ DuckDB CLI instance created\n";
    
    // Create a table
    $db->query("CREATE TABLE test (id INTEGER, name VARCHAR)");
    echo "✓ Table created\n";
    
    // Insert data
    $db->query("INSERT INTO test VALUES (1, 'Alice'), (2, 'Bob')");
    echo "✓ Data inserted\n";
    
    // Query data
    $result = $db->query("SELECT * FROM test");
    echo "✓ Query executed\n";
    
    // Fetch results
    $rows = [];
    while ($row = $result->fetchRow()) {
        $rows[] = $row;
    }
    
    echo "✓ Results fetched: " . json_encode($rows) . "\n";
    
    // Test prepared statement
    $stmt = $db->preparedStatement("SELECT * FROM test WHERE id = ?");
    $stmt->bindParam(1, 2);
    $result2 = $stmt->execute();
    
    $row = $result2->fetchRow();
    echo "✓ Prepared statement works: " . json_encode($row) . "\n";
    
    echo "\nDuckDB CLI test successful!\n";
    
    // Clean up
    if (isset($tempDb) && file_exists($tempDb)) {
        unlink($tempDb);
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Clean up on error
    if (isset($tempDb) && file_exists($tempDb)) {
        unlink($tempDb);
    }
}