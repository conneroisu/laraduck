<?php

/**
 * Demo script using SQLite to show the e-commerce analytics concepts
 * This demonstrates the analytics patterns that would work with DuckDB
 */

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

// Helper function
function output($message, $type = 'info') {
    $colors = [
        'info' => "\033[0;36m",
        'success' => "\033[0;32m",
        'warning' => "\033[0;33m",
        'error' => "\033[0;31m",
    ];
    
    $reset = "\033[0m";
    $prefix = match($type) {
        'success' => '✓',
        'error' => '✗',
        'warning' => '!',
        default => '→'
    };
    
    echo $colors[$type] . $prefix . " " . $message . $reset . PHP_EOL;
}

output("E-Commerce Analytics Demo (SQLite)", 'info');
output("==================================\n", 'info');

try {
    // Setup SQLite connection
    $capsule = new Capsule;
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);
    
    $capsule->setEventDispatcher(new Dispatcher(new Container));
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    
    // Create tables
    output("1. Creating tables...", 'info');
    
    Capsule::schema()->create('customers', function ($table) {
        $table->string('customer_id')->primary();
        $table->string('email');
        $table->string('segment');
        $table->date('registration_date');
    });
    
    Capsule::schema()->create('orders', function ($table) {
        $table->string('order_id')->primary();
        $table->string('customer_id');
        $table->timestamp('order_date');
        $table->string('status');
        $table->decimal('total_amount', 10, 2);
        $table->string('region');
    });
    
    Capsule::schema()->create('order_items', function ($table) {
        $table->string('order_id');
        $table->string('product_id');
        $table->integer('quantity');
        $table->decimal('unit_price', 10, 2);
        $table->decimal('total_price', 10, 2);
    });
    
    Capsule::schema()->create('products', function ($table) {
        $table->string('product_id')->primary();
        $table->string('name');
        $table->string('category');
        $table->decimal('price', 10, 2);
    });
    
    output("   ✓ Tables created", 'success');
    
    // Insert sample data
    output("\n2. Inserting sample data...", 'info');
    
    // Customers
    $customers = [
        ['customer_id' => 'C001', 'email' => 'john@example.com', 'segment' => 'Premium', 'registration_date' => '2023-01-15'],
        ['customer_id' => 'C002', 'email' => 'jane@example.com', 'segment' => 'Regular', 'registration_date' => '2023-02-20'],
        ['customer_id' => 'C003', 'email' => 'bob@example.com', 'segment' => 'Occasional', 'registration_date' => '2023-03-10'],
    ];
    
    Capsule::table('customers')->insert($customers);
    
    // Products
    $products = [
        ['product_id' => 'P001', 'name' => 'Laptop Pro', 'category' => 'Electronics', 'price' => 1299.99],
        ['product_id' => 'P002', 'name' => 'Wireless Mouse', 'category' => 'Electronics', 'price' => 49.99],
        ['product_id' => 'P003', 'name' => 'Office Chair', 'category' => 'Furniture', 'price' => 299.99],
        ['product_id' => 'P004', 'name' => 'Desk Lamp', 'category' => 'Furniture', 'price' => 79.99],
    ];
    
    Capsule::table('products')->insert($products);
    
    // Orders
    $orders = [
        ['order_id' => 'O001', 'customer_id' => 'C001', 'order_date' => '2024-01-10', 'status' => 'completed', 'total_amount' => 1349.98, 'region' => 'North America'],
        ['order_id' => 'O002', 'customer_id' => 'C002', 'order_date' => '2024-01-12', 'status' => 'completed', 'total_amount' => 379.98, 'region' => 'Europe'],
        ['order_id' => 'O003', 'customer_id' => 'C001', 'order_date' => '2024-01-15', 'status' => 'completed', 'total_amount' => 49.99, 'region' => 'North America'],
        ['order_id' => 'O004', 'customer_id' => 'C003', 'order_date' => '2024-01-18', 'status' => 'pending', 'total_amount' => 299.99, 'region' => 'Asia'],
    ];
    
    Capsule::table('orders')->insert($orders);
    
    // Order items
    $orderItems = [
        ['order_id' => 'O001', 'product_id' => 'P001', 'quantity' => 1, 'unit_price' => 1299.99, 'total_price' => 1299.99],
        ['order_id' => 'O001', 'product_id' => 'P002', 'quantity' => 1, 'unit_price' => 49.99, 'total_price' => 49.99],
        ['order_id' => 'O002', 'product_id' => 'P003', 'quantity' => 1, 'unit_price' => 299.99, 'total_price' => 299.99],
        ['order_id' => 'O002', 'product_id' => 'P004', 'quantity' => 1, 'unit_price' => 79.99, 'total_price' => 79.99],
        ['order_id' => 'O003', 'product_id' => 'P002', 'quantity' => 1, 'unit_price' => 49.99, 'total_price' => 49.99],
        ['order_id' => 'O004', 'product_id' => 'P003', 'quantity' => 1, 'unit_price' => 299.99, 'total_price' => 299.99],
    ];
    
    Capsule::table('order_items')->insert($orderItems);
    
    output("   ✓ Sample data inserted", 'success');
    
    // Run analytics queries
    output("\n3. Running analytics queries...", 'info');
    
    // Executive metrics
    $metrics = Capsule::table('orders')
        ->where('status', 'completed')
        ->selectRaw('COUNT(*) as total_orders')
        ->selectRaw('COUNT(DISTINCT customer_id) as unique_customers')
        ->selectRaw('SUM(total_amount) as total_revenue')
        ->selectRaw('AVG(total_amount) as avg_order_value')
        ->first();
    
    output("\n   Executive Metrics:", 'info');
    output("   - Total Orders: {$metrics->total_orders}", 'info');
    output("   - Unique Customers: {$metrics->unique_customers}", 'info');
    output("   - Total Revenue: $" . number_format($metrics->total_revenue, 2), 'info');
    output("   - Average Order Value: $" . number_format($metrics->avg_order_value, 2), 'info');
    
    // Revenue by category
    $categoryRevenue = Capsule::table('order_items as oi')
        ->join('products as p', 'oi.product_id', '=', 'p.product_id')
        ->join('orders as o', 'oi.order_id', '=', 'o.order_id')
        ->where('o.status', 'completed')
        ->selectRaw('p.category')
        ->selectRaw('SUM(oi.total_price) as revenue')
        ->selectRaw('COUNT(DISTINCT o.order_id) as order_count')
        ->groupBy('p.category')
        ->get();
    
    output("\n   Revenue by Category:", 'info');
    foreach ($categoryRevenue as $cat) {
        output("   - {$cat->category}: $" . number_format($cat->revenue, 2) . " ({$cat->order_count} orders)", 'info');
    }
    
    // Top products
    $topProducts = Capsule::table('order_items as oi')
        ->join('products as p', 'oi.product_id', '=', 'p.product_id')
        ->join('orders as o', 'oi.order_id', '=', 'o.order_id')
        ->where('o.status', 'completed')
        ->selectRaw('p.name')
        ->selectRaw('SUM(oi.quantity) as units_sold')
        ->selectRaw('SUM(oi.total_price) as revenue')
        ->groupBy('p.product_id', 'p.name')
        ->orderBy('revenue', 'desc')
        ->limit(3)
        ->get();
    
    output("\n   Top Products:", 'info');
    foreach ($topProducts as $product) {
        output("   - {$product->name}: {$product->units_sold} units, $" . number_format($product->revenue, 2), 'info');
    }
    
    // Customer segments
    $segments = Capsule::table('customers as c')
        ->leftJoin('orders as o', function($join) {
            $join->on('c.customer_id', '=', 'o.customer_id')
                 ->where('o.status', '=', 'completed');
        })
        ->selectRaw('c.segment')
        ->selectRaw('COUNT(DISTINCT c.customer_id) as customer_count')
        ->selectRaw('COUNT(DISTINCT o.order_id) as order_count')
        ->selectRaw('COALESCE(SUM(o.total_amount), 0) as revenue')
        ->groupBy('c.segment')
        ->get();
    
    output("\n   Customer Segments:", 'info');
    foreach ($segments as $segment) {
        $avgValue = $segment->customer_count > 0 ? $segment->revenue / $segment->customer_count : 0;
        output("   - {$segment->segment}: {$segment->customer_count} customers, $" . 
               number_format($segment->revenue, 2) . " revenue", 'info');
    }
    
    // Window function example (SQLite supports window functions)
    if (version_compare(SQLite3::version()['versionString'], '3.25.0', '>=')) {
        output("\n   Running Total Example:", 'info');
        
        $runningTotal = Capsule::select("
            SELECT 
                DATE(order_date) as date,
                total_amount,
                SUM(total_amount) OVER (ORDER BY order_date) as running_total
            FROM orders
            WHERE status = 'completed'
            ORDER BY order_date
        ");
        
        foreach ($runningTotal as $row) {
            output("   - {$row->date}: $" . number_format($row->total_amount, 2) . 
                   " (Running: $" . number_format($row->running_total, 2) . ")", 'info');
        }
    }
    
    output("\n✅ Demo completed successfully!", 'success');
    
    output("\n=== Key Concepts Demonstrated ===", 'info');
    output("1. Analytical Queries: Aggregations, grouping, joins", 'info');
    output("2. Business Metrics: Revenue, customer segments, product performance", 'info');
    output("3. Window Functions: Running totals (in supported SQLite versions)", 'info');
    output("4. Batch Operations: Bulk inserts for better performance", 'info');
    
    output("\nWith DuckDB, you would also get:", 'info');
    output("- Direct Parquet/CSV file querying", 'info');
    output("- QUALIFY clause for window function filtering", 'info');
    output("- Better performance for analytical workloads", 'info');
    output("- Columnar storage optimizations", 'info');
    output("- Advanced analytical functions", 'info');
    
} catch (\Exception $e) {
    output("Error: " . $e->getMessage(), 'error');
    output("Stack trace: " . $e->getTraceAsString(), 'error');
}