<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/database/migrations/create_tables.php';
require __DIR__ . '/database/seeders/DataGenerator.php';

use Database\Seeders\DataGenerator;

output("E-Commerce Analytics Example - Setup", 'info');
output("====================================\n", 'info');

try {
    // Run migrations
    output("Running migrations...", 'info');
    $migration = new CreateTables();
    
    // Drop existing tables first
    $migration->down();
    
    // Create tables
    $migration->up();
    output("✓ Tables created successfully", 'success');
    
    // Generate sample data
    output("\nGenerating sample data...", 'info');
    output("This may take a few minutes for large datasets.\n", 'warning');
    
    $generator = new DataGenerator();
    
    // You can adjust these numbers for different dataset sizes
    $customerCount = 5000;   // Number of customers
    $productCount = 200;     // Number of products  
    $orderCount = 25000;     // Number of orders
    
    $generator->generate($customerCount, $productCount, $orderCount);
    
    // Display summary
    output("\n=== Setup Complete ===", 'success');
    output("Database: " . __DIR__ . '/data/analytics.duckdb', 'info');
    output("Customers: " . number_format($customerCount), 'info');
    output("Products: " . number_format($productCount), 'info');
    output("Orders: " . number_format($orderCount), 'info');
    
    // Quick stats
    $stats = \Illuminate\Support\Facades\DB::connection('duckdb')->select("
        SELECT 
            (SELECT COUNT(*) FROM customers) as customers,
            (SELECT COUNT(*) FROM products) as products,
            (SELECT COUNT(*) FROM orders) as orders,
            (SELECT COUNT(*) FROM order_items) as order_items,
            (SELECT SUM(total_amount) FROM orders WHERE status = 'completed') as total_revenue
    ")[0];
    
    output("\nActual counts:", 'info');
    output("- Customers: " . number_format($stats->customers), 'info');
    output("- Products: " . number_format($stats->products), 'info');
    output("- Orders: " . number_format($stats->orders), 'info');
    output("- Order Items: " . number_format($stats->order_items), 'info');
    output("- Total Revenue: $" . number_format($stats->total_revenue, 2), 'info');
    
    output("\nNext steps:", 'success');
    output("1. Run 'php analytics.php' to see analytics examples", 'info');
    output("2. Run 'php file_operations.php' to see file import/export examples", 'info');
    output("3. Run 'php reports.php' to generate comprehensive reports", 'info');
    
} catch (\Exception $e) {
    output("Error: " . $e->getMessage(), 'error');
    output("Stack trace: " . $e->getTraceAsString(), 'error');
}