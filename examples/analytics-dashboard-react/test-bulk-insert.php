<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Enable query logging to see generated SQL
    DB::connection('duckdb')->enableQueryLog();
    
    // Test a small bulk insert
    $data = [
        [
            'id' => 99990,
            'customer_id' => 1,
            'product_id' => 1,
            'region' => 'North',
            'category' => 'Electronics',
            'brand' => 'TestBrand1',
            'price' => 100.00,
            'quantity' => 1,
            'revenue' => 100.00,
            'cost' => 50.00,
            'profit' => 50.00,
            'sale_date' => '2024-01-01 12:00:00',
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00',
        ],
        [
            'id' => 99991,
            'customer_id' => 2,
            'product_id' => 2,
            'region' => 'South',
            'category' => 'Clothing',
            'brand' => 'TestBrand2',
            'price' => 200.00,
            'quantity' => 2,
            'revenue' => 400.00,
            'cost' => 100.00,
            'profit' => 200.00,
            'sale_date' => '2024-01-02 12:00:00',
            'created_at' => '2024-01-02 12:00:00',
            'updated_at' => '2024-01-02 12:00:00',
        ]
    ];
    
    DB::connection('duckdb')->table('sales')->insert($data);
    
    echo "Bulk insert successful!\n";
    
    // Show query log
    $queries = DB::connection('duckdb')->getQueryLog();
    echo "Generated SQL:\n" . $queries[0]['query'] . "\n";
    
} catch (Exception $e) {
    echo "Bulk insert failed: " . $e->getMessage() . "\n";
    
    // Show query log for debugging
    $queries = DB::connection('duckdb')->getQueryLog();
    if (!empty($queries)) {
        echo "Last query attempted:\n" . $queries[count($queries)-1]['query'] . "\n";
    }
}