<?php

ini_set('memory_limit', '512M');

require_once __DIR__ . '/../vendor/autoload.php';

use Laraduck\EloquentDuckDB\Database\DuckDBConnection;
use Laraduck\EloquentDuckDB\Connectors\DuckDBConnector;

echo "Running Simple DuckDB Benchmarks\n";
echo "================================\n\n";

// Create temporary database
$tempDb = tempnam(sys_get_temp_dir(), 'benchmark_') . '.duckdb';

try {
    // Create connection
    $connector = new DuckDBConnector();
    $config = [
        'driver' => 'duckdb',
        'database' => $tempDb,
        'prefix' => '',
    ];
    
    $pdo = $connector->connect($config);
    $connection = new DuckDBConnection($pdo, $tempDb, '', $config);
    
    // Benchmark results
    $results = [];
    
    // 1. Table Creation
    $start = microtime(true);
    $connection->getSchemaBuilder()->create('benchmark_users', function ($table) {
        $table->bigInteger('id');
        $table->string('name');
        $table->string('email');
        $table->timestamp('created_at');
        $table->decimal('balance', 10, 2);
    });
    $results['create_table'] = (microtime(true) - $start) * 1000;
    
    // 2. Single Row Inserts (100 rows for memory efficiency)
    $start = microtime(true);
    for ($i = 1; $i <= 100; $i++) {
        $connection->table('benchmark_users')->insert([
            'id' => $i,
            'name' => "User $i",
            'email' => "user$i@example.com",
            'created_at' => date('Y-m-d H:i:s'),
            'balance' => rand(100, 10000) / 100,
        ]);
    }
    $results['insert_100_single'] = (microtime(true) - $start) * 1000;
    
    // 3. Bulk Insert (10k rows)
    $start = microtime(true);
    $data = [];
    for ($i = 101; $i <= 10100; $i++) {
        $data[] = [
            'id' => $i,
            'name' => "User $i",
            'email' => "user$i@example.com",
            'created_at' => date('Y-m-d H:i:s'),
            'balance' => rand(100, 10000) / 100,
        ];
        
        if (count($data) === 1000) {
            $connection->table('benchmark_users')->insert($data);
            $data = [];
            // Force garbage collection
            gc_collect_cycles();
        }
    }
    $results['insert_10k_bulk'] = (microtime(true) - $start) * 1000;
    
    // 4. Simple Select
    $start = microtime(true);
    $connection->table('benchmark_users')->limit(1000)->get();
    $results['select_simple'] = (microtime(true) - $start) * 1000;
    
    // 5. Select with Where
    $start = microtime(true);
    $connection->table('benchmark_users')
        ->where('balance', '>', 50)
        ->limit(1000)
        ->get();
    $results['select_where'] = (microtime(true) - $start) * 1000;
    
    // 6. Aggregation
    $start = microtime(true);
    $connection->table('benchmark_users')->count();
    $connection->table('benchmark_users')->sum('balance');
    $connection->table('benchmark_users')->avg('balance');
    $results['aggregation'] = (microtime(true) - $start) * 1000;
    
    // 7. Group By
    $start = microtime(true);
    $connection->table('benchmark_users')
        ->select($connection->raw('DATE(created_at) as date'), $connection->raw('COUNT(*) as count'), $connection->raw('AVG(balance) as avg_balance'))
        ->groupBy($connection->raw('DATE(created_at)'))
        ->get();
    $results['group_by'] = (microtime(true) - $start) * 1000;
    
    // 8. Export to Parquet
    $start = microtime(true);
    $connection->statement("COPY benchmark_users TO 'benchmark_export.parquet' (FORMAT PARQUET)");
    $results['export_parquet'] = (microtime(true) - $start) * 1000;
    
    // 9. Read from Parquet
    $start = microtime(true);
    $connection->select("SELECT * FROM read_parquet('benchmark_export.parquet') LIMIT 1000");
    $results['read_parquet'] = (microtime(true) - $start) * 1000;
    
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
        'results' => $results,
        'total_time_ms' => array_sum($results),
    ];
    
    file_put_contents('simple_benchmark_results.json', json_encode($jsonResults, JSON_PRETTY_PRINT));
    echo "\nResults saved to simple_benchmark_results.json\n";
    
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