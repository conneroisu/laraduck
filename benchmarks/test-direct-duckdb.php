<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Laraduck\EloquentDuckDB\Database\DuckDBConnection;
use Laraduck\EloquentDuckDB\Connectors\DuckDBConnector;
use Laraduck\EloquentDuckDB\DuckDB\DuckDBCLI;

echo "Testing direct DuckDB connection...\n";

try {
    // Create temporary database
    $tempDb = tempnam(sys_get_temp_dir(), 'test_direct_') . '.duckdb';
    
    // Test connector
    $connector = new DuckDBConnector();
    $config = [
        'driver' => 'duckdb',
        'database' => $tempDb,
        'prefix' => '',
    ];
    
    $pdo = $connector->connect($config);
    echo "✓ Connector created PDO wrapper\n";
    
    // Create connection
    $connection = new DuckDBConnection($pdo, $tempDb, '', $config);
    echo "✓ DuckDBConnection created\n";
    
    // Test schema builder
    $connection->getSchemaBuilder()->create('test_table', function ($table) {
        $table->integer('id');
        $table->string('name');
    });
    echo "✓ Table created via schema builder\n";
    
    // Test insert
    $connection->table('test_table')->insert(['id' => 1, 'name' => 'Test']);
    echo "✓ Data inserted\n";
    
    // Test select
    $result = $connection->table('test_table')->first();
    echo "✓ Data retrieved: " . json_encode($result) . "\n";
    
    // Clean up
    unlink($tempDb);
    echo "\nDirect connection test successful!\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    if (isset($tempDb) && file_exists($tempDb)) {
        unlink($tempDb);
    }
}