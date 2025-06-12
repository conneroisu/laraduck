<?php

namespace Laraduck\EloquentDuckDB\Benchmarks;

use Illuminate\Support\Facades\DB;

class ComparisonBenchmark
{
    protected array $results = [];
    protected $mysqlConnection;
    protected $postgresConnection;
    protected $sqliteConnection;
    protected $duckdbConnection;
    
    public function __construct()
    {
        // Initialize connections if available
        try {
            $this->mysqlConnection = DB::connection('mysql');
        } catch (\Exception $e) {
            $this->mysqlConnection = null;
        }
        
        try {
            $this->postgresConnection = DB::connection('pgsql');
        } catch (\Exception $e) {
            $this->postgresConnection = null;
        }
        
        try {
            $this->sqliteConnection = DB::connection('sqlite');
        } catch (\Exception $e) {
            $this->sqliteConnection = null;
        }
        
        $this->duckdbConnection = DB::connection('duckdb');
    }
    
    public function run(): void
    {
        echo "Running Database Comparison Benchmarks...\n\n";
        
        $this->setupAllDatabases();
        
        $benchmarks = [
            'compareInsertPerformance',
            'compareBulkInsertPerformance',
            'compareSelectPerformance',
            'compareAggregationPerformance',
            'compareJoinPerformance',
            'compareAnalyticalQueries',
        ];
        
        foreach ($benchmarks as $benchmark) {
            echo "\n" . str_repeat('=', 60) . "\n";
            echo "Running: " . ucwords(str_replace(['compare', 'Performance'], '', $benchmark)) . "\n";
            echo str_repeat('=', 60) . "\n";
            $this->$benchmark();
        }
        
        $this->displayComparisonResults();
        $this->cleanupAllDatabases();
    }
    
    protected function setupAllDatabases(): void
    {
        $connections = $this->getAvailableConnections();
        
        foreach ($connections as $name => $connection) {
            echo "Setting up {$name} database...\n";
            
            $connection->getSchemaBuilder()->dropIfExists('comparison_users');
            $connection->getSchemaBuilder()->dropIfExists('comparison_orders');
            
            $connection->getSchemaBuilder()->create('comparison_users', function ($table) {
                $table->bigInteger('id');
                $table->string('name');
                $table->string('email');
                $table->timestamp('created_at');
                $table->decimal('balance', 10, 2);
            });
            
            $connection->getSchemaBuilder()->create('comparison_orders', function ($table) {
                $table->bigInteger('id');
                $table->bigInteger('user_id');
                $table->decimal('amount', 10, 2);
                $table->string('status');
                $table->timestamp('created_at');
            });
        }
    }
    
    protected function cleanupAllDatabases(): void
    {
        $connections = $this->getAvailableConnections();
        
        foreach ($connections as $name => $connection) {
            $connection->getSchemaBuilder()->dropIfExists('comparison_users');
            $connection->getSchemaBuilder()->dropIfExists('comparison_orders');
        }
    }
    
    protected function getAvailableConnections(): array
    {
        $connections = ['duckdb' => $this->duckdbConnection];
        
        if ($this->mysqlConnection) {
            $connections['mysql'] = $this->mysqlConnection;
        }
        if ($this->postgresConnection) {
            $connections['postgres'] = $this->postgresConnection;
        }
        if ($this->sqliteConnection) {
            $connections['sqlite'] = $this->sqliteConnection;
        }
        
        return $connections;
    }
    
    protected function runOnConnection($connection, string $name, callable $callback): array
    {
        echo "  Running on {$name}... ";
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        try {
            $callback($connection);
            $success = true;
        } catch (\Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $success = false;
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $result = [
            'time' => round(($endTime - $startTime) * 1000, 2),
            'memory' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'success' => $success,
        ];
        
        if ($success) {
            echo "Done ({$result['time']}ms)\n";
        }
        
        return $result;
    }
    
    protected function compareInsertPerformance(): void
    {
        $connections = $this->getAvailableConnections();
        $results = [];
        
        foreach ($connections as $name => $connection) {
            $results[$name] = $this->runOnConnection($connection, $name, function($conn) {
                for ($i = 1; $i <= 1000; $i++) {
                    $conn->table('comparison_users')->insert([
                        'id' => $i,
                        'name' => "User {$i}",
                        'email' => "user{$i}@example.com",
                        'created_at' => now(),
                        'balance' => rand(100, 10000) / 100,
                    ]);
                }
            });
        }
        
        $this->results['Single Insert (1000 rows)'] = $results;
    }
    
    protected function compareBulkInsertPerformance(): void
    {
        $connections = $this->getAvailableConnections();
        $results = [];
        
        foreach ($connections as $name => $connection) {
            $results[$name] = $this->runOnConnection($connection, $name, function($conn) {
                $data = [];
                for ($i = 1001; $i <= 11000; $i++) {
                    $data[] = [
                        'id' => $i,
                        'name' => "User {$i}",
                        'email' => "user{$i}@example.com",
                        'created_at' => now(),
                        'balance' => rand(100, 10000) / 100,
                    ];
                    
                    if (count($data) === 1000) {
                        $conn->table('comparison_users')->insert($data);
                        $data = [];
                    }
                }
            });
        }
        
        $this->results['Bulk Insert (10k rows)'] = $results;
    }
    
    protected function compareSelectPerformance(): void
    {
        $connections = $this->getAvailableConnections();
        $results = [];
        
        foreach ($connections as $name => $connection) {
            $results[$name] = $this->runOnConnection($connection, $name, function($conn) {
                // Simple select
                $conn->table('comparison_users')->limit(1000)->get();
                
                // Select with where
                $conn->table('comparison_users')
                    ->where('balance', '>', 50)
                    ->limit(1000)
                    ->get();
                
                // Select with multiple conditions
                $conn->table('comparison_users')
                    ->where('balance', '>', 50)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->limit(1000)
                    ->get();
            });
        }
        
        $this->results['Select Queries'] = $results;
    }
    
    protected function compareAggregationPerformance(): void
    {
        $connections = $this->getAvailableConnections();
        $results = [];
        
        foreach ($connections as $name => $connection) {
            $results[$name] = $this->runOnConnection($connection, $name, function($conn) {
                // Count
                $conn->table('comparison_users')->count();
                
                // Sum
                $conn->table('comparison_users')->sum('balance');
                
                // Average
                $conn->table('comparison_users')->avg('balance');
                
                // Group by with aggregation
                $conn->table('comparison_users')
                    ->select($conn->raw('DATE(created_at) as date'), 
                            $conn->raw('COUNT(*) as count'), 
                            $conn->raw('AVG(balance) as avg_balance'))
                    ->groupBy('date')
                    ->get();
            });
        }
        
        $this->results['Aggregation Queries'] = $results;
    }
    
    protected function compareJoinPerformance(): void
    {
        // First insert orders data
        $connections = $this->getAvailableConnections();
        
        foreach ($connections as $name => $connection) {
            $orders = [];
            for ($i = 1; $i <= 5000; $i++) {
                $orders[] = [
                    'id' => $i,
                    'user_id' => rand(1, 1000),
                    'amount' => rand(100, 10000) / 100,
                    'status' => ['pending', 'completed', 'cancelled'][rand(0, 2)],
                    'created_at' => now()->subDays(rand(0, 30)),
                ];
                
                if (count($orders) === 500) {
                    $connection->table('comparison_orders')->insert($orders);
                    $orders = [];
                }
            }
        }
        
        $results = [];
        
        foreach ($connections as $name => $connection) {
            $results[$name] = $this->runOnConnection($connection, $name, function($conn) {
                $conn->table('comparison_users')
                    ->join('comparison_orders', 'comparison_users.id', '=', 'comparison_orders.user_id')
                    ->select('comparison_users.name', 'comparison_users.email', 
                            $conn->raw('SUM(comparison_orders.amount) as total_spent'))
                    ->groupBy('comparison_users.id', 'comparison_users.name', 'comparison_users.email')
                    ->having('total_spent', '>', 100)
                    ->limit(100)
                    ->get();
            });
        }
        
        $this->results['Join Queries'] = $results;
    }
    
    protected function compareAnalyticalQueries(): void
    {
        $connections = $this->getAvailableConnections();
        $results = [];
        
        foreach ($connections as $name => $connection) {
            $results[$name] = $this->runOnConnection($connection, $name, function($conn) use ($name) {
                // Note: Window functions might not be supported in all databases
                if ($name === 'duckdb' || $name === 'postgres') {
                    $conn->select("
                        SELECT 
                            name,
                            balance,
                            ROW_NUMBER() OVER (ORDER BY balance DESC) as rank,
                            AVG(balance) OVER (ORDER BY balance ROWS BETWEEN 10 PRECEDING AND 10 FOLLOWING) as moving_avg
                        FROM comparison_users
                        LIMIT 100
                    ");
                } else {
                    // Fallback for databases without window function support
                    $conn->table('comparison_users')
                        ->orderBy('balance', 'desc')
                        ->limit(100)
                        ->get();
                }
            });
        }
        
        $this->results['Analytical Queries'] = $results;
    }
    
    protected function displayComparisonResults(): void
    {
        echo "\n\n" . str_repeat('=', 100) . "\n";
        echo "COMPARISON BENCHMARK RESULTS\n";
        echo str_repeat('=', 100) . "\n\n";
        
        foreach ($this->results as $benchmark => $databases) {
            echo str_pad($benchmark, 30) . " | ";
            
            foreach ($databases as $db => $result) {
                if ($result['success']) {
                    echo str_pad("{$db}: {$result['time']}ms", 20) . " | ";
                } else {
                    echo str_pad("{$db}: FAILED", 20) . " | ";
                }
            }
            echo "\n";
        }
        
        echo "\n" . str_repeat('=', 100) . "\n";
        
        // Save detailed results
        file_put_contents('comparison_results.json', json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\nDetailed results saved to comparison_results.json\n";
    }
}