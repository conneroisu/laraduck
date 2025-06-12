<?php

namespace Laraduck\EloquentDuckDB\Benchmarks;

use Illuminate\Support\Facades\DB;

class BenchmarkRunner
{
    protected array $results = [];
    protected string $outputFormat = 'table';
    
    public function run(): void
    {
        $this->setupBenchmarkEnvironment();
        
        echo "Running Laraduck Benchmarks...\n\n";
        
        $benchmarks = [
            'testInsertPerformance',
            'testBulkInsertPerformance',
            'testSelectPerformance',
            'testAggregationPerformance',
            'testJoinPerformance',
            'testWindowFunctionPerformance',
            'testParquetReadPerformance',
            'testParquetWritePerformance',
            'testCsvReadPerformance',
            'testJsonQueryPerformance',
        ];
        
        foreach ($benchmarks as $benchmark) {
            $this->runBenchmark($benchmark);
        }
        
        $this->displayResults();
        $this->saveResults();
        $this->cleanupBenchmarkEnvironment();
    }
    
    protected function setupBenchmarkEnvironment(): void
    {
        // Create benchmark tables
        DB::connection('duckdb')->getSchemaBuilder()->create('benchmark_users', function ($table) {
            $table->bigInteger('id');
            $table->string('name');
            $table->string('email');
            $table->timestamp('created_at');
            $table->decimal('balance', 10, 2);
        });
        
        DB::connection('duckdb')->getSchemaBuilder()->create('benchmark_orders', function ($table) {
            $table->bigInteger('id');
            $table->bigInteger('user_id');
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->timestamp('created_at');
        });
        
        DB::connection('duckdb')->getSchemaBuilder()->create('benchmark_products', function ($table) {
            $table->bigInteger('id');
            $table->string('name');
            $table->string('category');
            $table->decimal('price', 10, 2);
            $table->integer('stock');
        });
    }
    
    protected function cleanupBenchmarkEnvironment(): void
    {
        DB::connection('duckdb')->getSchemaBuilder()->dropIfExists('benchmark_users');
        DB::connection('duckdb')->getSchemaBuilder()->dropIfExists('benchmark_orders');
        DB::connection('duckdb')->getSchemaBuilder()->dropIfExists('benchmark_products');
        
        // Clean up any generated files
        $files = ['benchmark_data.parquet', 'benchmark_data.csv', 'benchmark_export.parquet'];
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    protected function runBenchmark(string $method): void
    {
        echo "Running {$method}...\n";
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $this->$method();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $this->results[$method] = [
            'time' => round(($endTime - $startTime) * 1000, 2), // ms
            'memory' => round(($endMemory - $startMemory) / 1024 / 1024, 2), // MB
        ];
    }
    
    protected function testInsertPerformance(): void
    {
        for ($i = 1; $i <= 10000; $i++) {
            DB::connection('duckdb')->table('benchmark_users')->insert([
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'created_at' => now(),
                'balance' => rand(100, 10000) / 100,
            ]);
        }
    }
    
    protected function testBulkInsertPerformance(): void
    {
        $data = [];
        for ($i = 10001; $i <= 110000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'created_at' => now(),
                'balance' => rand(100, 10000) / 100,
            ];
            
            if (count($data) === 1000) {
                DB::connection('duckdb')->table('benchmark_users')->insert($data);
                $data = [];
            }
        }
    }
    
    protected function testSelectPerformance(): void
    {
        // Simple select
        DB::connection('duckdb')->table('benchmark_users')->limit(1000)->get();
        
        // Select with where
        DB::connection('duckdb')->table('benchmark_users')
            ->where('balance', '>', 50)
            ->limit(1000)
            ->get();
        
        // Select with multiple conditions
        DB::connection('duckdb')->table('benchmark_users')
            ->where('balance', '>', 50)
            ->where('created_at', '>=', now()->subDays(7))
            ->limit(1000)
            ->get();
    }
    
    protected function testAggregationPerformance(): void
    {
        // Count
        DB::connection('duckdb')->table('benchmark_users')->count();
        
        // Sum
        DB::connection('duckdb')->table('benchmark_users')->sum('balance');
        
        // Average
        DB::connection('duckdb')->table('benchmark_users')->avg('balance');
        
        // Group by with aggregation
        DB::connection('duckdb')->table('benchmark_users')
            ->select(DB::connection('duckdb')->raw('DATE(created_at) as date'), DB::connection('duckdb')->raw('COUNT(*) as count'), DB::connection('duckdb')->raw('AVG(balance) as avg_balance'))
            ->groupBy('date')
            ->get();
    }
    
    protected function testJoinPerformance(): void
    {
        // Insert some orders
        $orders = [];
        for ($i = 1; $i <= 50000; $i++) {
            $orders[] = [
                'id' => $i,
                'user_id' => rand(1, 10000),
                'amount' => rand(100, 10000) / 100,
                'status' => ['pending', 'completed', 'cancelled'][rand(0, 2)],
                'created_at' => now()->subDays(rand(0, 30)),
            ];
            
            if (count($orders) === 1000) {
                DB::connection('duckdb')->table('benchmark_orders')->insert($orders);
                $orders = [];
            }
        }
        
        // Join query
        DB::connection('duckdb')->table('benchmark_users')
            ->join('benchmark_orders', 'benchmark_users.id', '=', 'benchmark_orders.user_id')
            ->select('benchmark_users.name', 'benchmark_users.email', DB::connection('duckdb')->raw('SUM(benchmark_orders.amount) as total_spent'))
            ->groupBy('benchmark_users.id', 'benchmark_users.name', 'benchmark_users.email')
            ->having('total_spent', '>', 100)
            ->limit(100)
            ->get();
    }
    
    protected function testWindowFunctionPerformance(): void
    {
        DB::connection('duckdb')->select("
            SELECT 
                name,
                balance,
                ROW_NUMBER() OVER (ORDER BY balance DESC) as rank,
                AVG(balance) OVER (ORDER BY balance ROWS BETWEEN 10 PRECEDING AND 10 FOLLOWING) as moving_avg
            FROM benchmark_users
            LIMIT 1000
        ");
    }
    
    protected function testParquetReadPerformance(): void
    {
        // First create a parquet file
        DB::connection('duckdb')->statement("COPY benchmark_users TO 'benchmark_data.parquet' (FORMAT PARQUET)");
        
        // Read from parquet
        DB::connection('duckdb')->select("SELECT * FROM read_parquet('benchmark_data.parquet') LIMIT 1000");
    }
    
    protected function testParquetWritePerformance(): void
    {
        DB::connection('duckdb')->statement("COPY (SELECT * FROM benchmark_users WHERE balance > 50) TO 'benchmark_export.parquet' (FORMAT PARQUET)");
    }
    
    protected function testCsvReadPerformance(): void
    {
        // First create a CSV file
        DB::connection('duckdb')->statement("COPY benchmark_users TO 'benchmark_data.csv' (FORMAT CSV, HEADER)");
        
        // Read from CSV
        DB::connection('duckdb')->select("SELECT * FROM read_csv_auto('benchmark_data.csv') LIMIT 1000");
    }
    
    protected function testJsonQueryPerformance(): void
    {
        // Create a table with JSON data
        DB::connection('duckdb')->getSchemaBuilder()->create('benchmark_json', function ($table) {
            $table->bigInteger('id');
            $table->json('data');
        });
        
        // Insert JSON data
        $jsonData = [];
        for ($i = 1; $i <= 10000; $i++) {
            $jsonData[] = [
                'id' => $i,
                'data' => json_encode([
                    'user_id' => $i,
                    'preferences' => [
                        'theme' => ['light', 'dark'][rand(0, 1)],
                        'notifications' => rand(0, 1) === 1,
                    ],
                    'tags' => array_map(fn($n) => "tag{$n}", range(1, rand(1, 5))),
                ]),
            ];
            
            if (count($jsonData) === 1000) {
                DB::connection('duckdb')->table('benchmark_json')->insert($jsonData);
                $jsonData = [];
            }
        }
        
        // Query JSON data
        DB::connection('duckdb')->select("
            SELECT 
                id,
                json_extract_string(data, '$.preferences.theme') as theme,
                json_array_length(json_extract(data, '$.tags')) as tag_count
            FROM benchmark_json
            WHERE json_extract_string(data, '$.preferences.theme') = 'dark'
            LIMIT 1000
        ");
        
        DB::connection('duckdb')->getSchemaBuilder()->dropIfExists('benchmark_json');
    }
    
    protected function displayResults(): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "BENCHMARK RESULTS\n";
        echo str_repeat('=', 80) . "\n\n";
        
        $format = "%-35s %15s %15s\n";
        printf($format, "Benchmark", "Time (ms)", "Memory (MB)");
        echo str_repeat('-', 80) . "\n";
        
        foreach ($this->results as $benchmark => $result) {
            $name = ucwords(str_replace(['test', 'Performance'], '', $benchmark));
            printf($format, $name, $result['time'], $result['memory']);
        }
        
        echo "\n";
        
        // Calculate totals
        $totalTime = array_sum(array_column($this->results, 'time'));
        $totalMemory = array_sum(array_column($this->results, 'memory'));
        
        echo str_repeat('-', 80) . "\n";
        printf($format, "TOTAL", $totalTime, $totalMemory);
        echo str_repeat('=', 80) . "\n";
    }
    
    protected function saveResults(): void
    {
        $results = [
            'timestamp' => now()->toIso8601String(),
            'php_version' => PHP_VERSION,
            'results' => $this->results,
            'summary' => [
                'total_time_ms' => array_sum(array_column($this->results, 'time')),
                'total_memory_mb' => array_sum(array_column($this->results, 'memory')),
                'average_time_ms' => round(array_sum(array_column($this->results, 'time')) / count($this->results), 2),
                'average_memory_mb' => round(array_sum(array_column($this->results, 'memory')) / count($this->results), 2),
            ],
        ];
        
        file_put_contents('benchmark_results.json', json_encode($results, JSON_PRETTY_PRINT));
        echo "\nResults saved to benchmark_results.json\n";
    }
}