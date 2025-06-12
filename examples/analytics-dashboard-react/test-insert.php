<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Test a simple insert with explicit column order
    $result = DB::connection('duckdb')->table('sales')->insert([
        'id' => 99999,
        'customer_id' => 1,
        'product_id' => 1,
        'region' => 'North',
        'category' => 'Electronics',
        'brand' => 'TestBrand',
        'price' => 100.00,
        'quantity' => 1,
        'revenue' => 100.00,
        'cost' => 50.00,
        'profit' => 50.00,
        'sale_date' => '2024-01-01 12:00:00',
        'created_at' => '2024-01-01 12:00:00',
        'updated_at' => '2024-01-01 12:00:00',
    ]);
    
    echo "Single insert successful!\n";
    
    // Check the record
    $record = DB::connection('duckdb')->table('sales')->where('id', 99999)->first();
    echo "Retrieved record: " . json_encode($record) . "\n";
    
} catch (Exception $e) {
    echo "Insert failed: " . $e->getMessage() . "\n";
}