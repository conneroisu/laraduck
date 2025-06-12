<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

try {
    echo "Testing dashboard queries...\n";
    
    // Enable query logging
    DB::connection('duckdb')->enableQueryLog();
    
    // Test 1: Basic count
    echo "1. Testing basic count: ";
    $count = Sale::count();
    echo "$count records\n";
    
    // Test 2: Date range query  
    echo "2. Testing date range query: ";
    $dateRange = Sale::dateRange('2024-01-01', '2024-12-31')->count();
    echo "$dateRange records in 2024\n";
    
    // Test 3: Basic aggregation
    echo "3. Testing basic aggregation: ";
    $basic = Sale::query()
        ->select([
            DB::raw('SUM(revenue) as total_revenue'),
            DB::raw('COUNT(*) as total_sales'),
            DB::raw('AVG(revenue) as avg_revenue'),
        ])
        ->first();
    echo "Revenue: $" . number_format($basic->total_revenue, 2) . ", Sales: $basic->total_sales, Avg: $" . number_format($basic->avg_revenue, 2) . "\n";
    
    // Test 4: Summarize by period (simple version)
    echo "4. Testing period summary: ";
    $period = Sale::summarizeByPeriod('day', '2024-01-01', '2024-12-31')->first();
    if ($period) {
        echo "Found period data\n";
    } else {
        echo "No period data\n";
    }
    
    // Show all queries that were executed
    $queries = DB::connection('duckdb')->getQueryLog();
    echo "\nExecuted queries:\n";
    foreach ($queries as $i => $query) {
        echo ($i + 1) . ". " . $query['query'] . "\n";
        if (!empty($query['bindings'])) {
            echo "   Bindings: " . json_encode($query['bindings']) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Show queries that were attempted
    $queries = DB::connection('duckdb')->getQueryLog();
    if (!empty($queries)) {
        echo "\nLast attempted query:\n";
        $lastQuery = end($queries);
        echo $lastQuery['query'] . "\n";
        if (!empty($lastQuery['bindings'])) {
            echo "Bindings: " . json_encode($lastQuery['bindings']) . "\n";
        }
    }
}