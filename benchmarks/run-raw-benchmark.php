<?php

ini_set('memory_limit', '256M');
require_once __DIR__ . '/../vendor/autoload.php';

use Laraduck\EloquentDuckDB\DuckDB\DuckDBCLI;

echo "Running Raw DuckDB Benchmarks\n";
echo "=============================\n\n";

// Create temporary database
$tempDb = tempnam(sys_get_temp_dir(), 'benchmark_') . '.duckdb';

try {
    // Create DuckDB instance
    $db = DuckDBCLI::create($tempDb);
    
    // Benchmark results
    $results = [];
    
    // 1. Table Creation
    $start = microtime(true);
    $db->query("CREATE TABLE benchmark_users (
        id BIGINT,
        name VARCHAR,
        email VARCHAR,
        created_at TIMESTAMP,
        balance DECIMAL(10,2)
    )");
    $results['create_table'] = (microtime(true) - $start) * 1000;
    
    // 2. Single Row Inserts (100 rows)
    $start = microtime(true);
    for ($i = 1; $i <= 100; $i++) {
        $name = "User $i";
        $email = "user$i@example.com";
        $created = date('Y-m-d H:i:s');
        $balance = rand(100, 10000) / 100;
        $db->query("INSERT INTO benchmark_users VALUES ($i, '$name', '$email', '$created', $balance)");
    }
    $results['insert_100_single'] = (microtime(true) - $start) * 1000;
    
    // 3. Bulk Insert using VALUES (10k rows)
    $start = microtime(true);
    for ($batch = 0; $batch < 10; $batch++) {
        $values = [];
        for ($i = 1; $i <= 1000; $i++) {
            $id = 100 + ($batch * 1000) + $i;
            $name = "User $id";
            $email = "user$id@example.com";
            $created = date('Y-m-d H:i:s');
            $balance = rand(100, 10000) / 100;
            $values[] = "($id, '$name', '$email', '$created', $balance)";
        }
        $db->query("INSERT INTO benchmark_users VALUES " . implode(', ', $values));
    }
    $results['insert_10k_bulk'] = (microtime(true) - $start) * 1000;
    
    // 4. Simple Select
    $start = microtime(true);
    $result = $db->query("SELECT * FROM benchmark_users LIMIT 1000");
    while ($result->fetchRow()) {
        // Just fetching
    }
    $results['select_simple'] = (microtime(true) - $start) * 1000;
    
    // 5. Select with Where
    $start = microtime(true);
    $result = $db->query("SELECT * FROM benchmark_users WHERE balance > 50 LIMIT 1000");
    while ($result->fetchRow()) {
        // Just fetching
    }
    $results['select_where'] = (microtime(true) - $start) * 1000;
    
    // 6. Aggregation
    $start = microtime(true);
    $db->query("SELECT COUNT(*) FROM benchmark_users");
    $db->query("SELECT SUM(balance) FROM benchmark_users");
    $db->query("SELECT AVG(balance) FROM benchmark_users");
    $results['aggregation'] = (microtime(true) - $start) * 1000;
    
    // 7. Group By
    $start = microtime(true);
    $db->query("SELECT CAST(created_at AS DATE) as date, COUNT(*) as count, AVG(balance) as avg_balance FROM benchmark_users GROUP BY CAST(created_at AS DATE)");
    $results['group_by'] = (microtime(true) - $start) * 1000;
    
    // 8. Export to Parquet
    $start = microtime(true);
    $db->query("COPY benchmark_users TO 'benchmark_export.parquet' (FORMAT PARQUET)");
    $results['export_parquet'] = (microtime(true) - $start) * 1000;
    
    // 9. Read from Parquet
    $start = microtime(true);
    $result = $db->query("SELECT * FROM read_parquet('benchmark_export.parquet') LIMIT 1000");
    while ($result->fetchRow()) {
        // Just fetching
    }
    $results['read_parquet'] = (microtime(true) - $start) * 1000;
    
    // 10. Window Functions
    $start = microtime(true);
    $db->query("SELECT name, balance, ROW_NUMBER() OVER (ORDER BY balance DESC) as rank FROM benchmark_users LIMIT 100");
    $results['window_functions'] = (microtime(true) - $start) * 1000;
    
    // Display results
    echo "\nBenchmark Results:\n";
    echo str_repeat('-', 50) . "\n";
    printf("%-30s %15s\n", "Operation", "Time (ms)");
    echo str_repeat('-', 50) . "\n";
    
    foreach ($results as $operation => $time) {
        printf("%-30s %15.2f\n", str_replace('_', ' ', ucfirst($operation)), $time);
    }
    
    echo str_repeat('-', 50) . "\n";
    printf("%-30s %15.2f\n", "Total", array_sum($results));
    echo str_repeat('=', 50) . "\n";
    
    // Save results to JSON
    $jsonResults = [
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'duckdb_version' => 'v1.2.2',
        'results' => $results,
        'total_time_ms' => array_sum($results),
        'rows_processed' => 10100,
    ];
    
    file_put_contents('raw_benchmark_results.json', json_encode($jsonResults, JSON_PRETTY_PRINT));
    echo "\nResults saved to raw_benchmark_results.json\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    // Clean up
    if (file_exists($tempDb)) {
        unlink($tempDb);
    }
    if (file_exists('benchmark_export.parquet')) {
        unlink('benchmark_export.parquet');
    }
}