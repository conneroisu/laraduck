<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Clear existing data first
    DB::connection('duckdb')->table('sales')->truncate();
    
    // Insert records one by one to avoid bulk insert column ordering issues
    $records = [
        [
            'id' => 1,
            'customer_id' => 1,
            'product_id' => 1,
            'region' => 'North',
            'category' => 'Electronics',
            'brand' => 'TechPro',
            'price' => 100.00,
            'quantity' => 2,
            'revenue' => 200.00,
            'cost' => 60.00,
            'profit' => 140.00,
            'sale_date' => '2024-01-15 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 2,
            'customer_id' => 2,
            'product_id' => 2,
            'region' => 'South',
            'category' => 'Clothing',
            'brand' => 'StyleMax',
            'price' => 50.00,
            'quantity' => 1,
            'revenue' => 50.00,
            'cost' => 25.00,
            'profit' => 25.00,
            'sale_date' => '2024-01-16 14:30:00',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 3,
            'customer_id' => 1,
            'product_id' => 3,
            'region' => 'East',
            'category' => 'Home & Garden',
            'brand' => 'HomeEssentials',
            'price' => 75.50,
            'quantity' => 3,
            'revenue' => 226.50,
            'cost' => 45.00,
            'profit' => 181.50,
            'sale_date' => '2024-02-01 09:15:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    ];
    
    foreach ($records as $record) {
        DB::connection('duckdb')->table('sales')->insert($record);
        echo "Inserted record ID: " . $record['id'] . "\n";
    }
    
    echo "Simple seeding completed successfully!\n";
    
    // Verify the data
    $count = DB::connection('duckdb')->table('sales')->count();
    echo "Total records in sales table: $count\n";
    
    // Show sample records
    $samples = DB::connection('duckdb')->table('sales')->limit(3)->get();
    foreach ($samples as $sample) {
        echo "Sample: ID {$sample->id}, Customer {$sample->customer_id}, Region {$sample->region}, Revenue $" . number_format($sample->revenue, 2) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}