<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Laraduck\EloquentDuckDB\DuckDB\DuckDB;

echo "Testing direct DuckDB connection...\n";

try {
    // Create in-memory database
    $db = DuckDB::create(':memory:');
    echo "✓ DuckDB instance created\n";
    
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
    
    echo "\nDirect DuckDB test successful!\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}