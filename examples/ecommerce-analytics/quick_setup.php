<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/database/migrations/create_tables.php';
require __DIR__ . '/database/seeders/DataGenerator.php';
require __DIR__ . '/app/Models/Customer.php';
require __DIR__ . '/app/Models/Product.php';
require __DIR__ . '/app/Models/Order.php';
require __DIR__ . '/app/Models/OrderItem.php';

use Database\Seeders\DataGenerator;
use Illuminate\Database\Capsule\Manager as Capsule;

output("Quick E-Commerce Analytics Setup", 'info');
output("==================================\n", 'info');

try {
    // Run migrations
    output("Running migrations...", 'info');
    $migration = new CreateTables();
    
    // Drop existing tables first
    $migration->down();
    
    // Create tables
    $migration->up();
    output("✓ Tables created successfully", 'success');
    
    // Generate minimal sample data for testing
    output("\nGenerating minimal sample data...", 'info');
    
    $generator = new DataGenerator();
    
    // Small dataset for quick testing
    $customerCount = 100;   // Number of customers
    $productCount = 20;     // Number of products  
    $orderCount = 200;      // Number of orders
    
    $generator->generate($customerCount, $productCount, $orderCount);
    
    // Display summary
    output("\n=== Quick Setup Complete ===", 'success');
    output("Database: " . __DIR__ . '/data/analytics.duckdb', 'info');
    output("Customers: " . number_format($customerCount), 'info');
    output("Products: " . number_format($productCount), 'info');
    output("Orders: " . number_format($orderCount), 'info');
    
    // Quick stats
    $stats = Capsule::connection('duckdb')->select("
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
    
    output("\nDashboard ready at: http://localhost:8000", 'success');
    
} catch (\Exception $e) {
    output("Error: " . $e->getMessage(), 'error');
    output("Stack trace: " . $e->getTraceAsString(), 'error');
}