<?php

namespace App\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Analytics\SalesAnalytics;
use Illuminate\Support\Facades\DB;

class FileOperations
{
    /**
     * Export data to various file formats
     */
    public static function exportExamples()
    {
        output("\n=== FILE EXPORT EXAMPLES ===\n", 'info');
        
        // 1. Export orders to Parquet
        output("1. Exporting completed orders to Parquet...", 'info');
        $exportPath = __DIR__ . '/../../exports/orders_export.parquet';
        
        Order::where('status', 'completed')
            ->where('order_date', '>=', now()->subDays(30))
            ->toParquet($exportPath, [
                'compression' => 'snappy',
                'row_group_size' => 100000
            ]);
            
        output("   ✓ Exported to: {$exportPath}", 'success');
        
        // 2. Export partitioned data
        output("\n2. Exporting orders partitioned by year and region...", 'info');
        $partitionedPath = __DIR__ . '/../../exports/orders_partitioned';
        
        Order::query()
            ->selectRaw('*, YEAR(order_date) as year')
            ->exportToParquetPartitioned($partitionedPath, ['year', 'region'], [
                'compression' => 'zstd',
                'compression_level' => 3
            ]);
            
        output("   ✓ Exported to: {$partitionedPath}/", 'success');
        
        // 3. Export analytics results to CSV
        output("\n3. Exporting sales analytics to CSV...", 'info');
        $salesTrend = SalesAnalytics::salesTrend('day', 30);
        
        DB::connection('duckdb')->statement("
            COPY (
                SELECT 
                    period::DATE as date,
                    order_count,
                    unique_customers,
                    revenue,
                    avg_order_value
                FROM (?) as sales_data
                ORDER BY period
            ) TO ? (FORMAT CSV, HEADER TRUE)
        ", [$salesTrend->toSql(), __DIR__ . '/../../exports/sales_trend.csv']);
        
        output("   ✓ Exported to: exports/sales_trend.csv", 'success');
        
        // 4. Export product catalog to JSON
        output("\n4. Exporting active products to JSON...", 'info');
        
        Product::where('is_active', true)
            ->toJsonFile(__DIR__ . '/../../exports/product_catalog.json', [
                'array' => false
            ]);
            
        output("   ✓ Exported to: exports/product_catalog.json", 'success');
        
        // 5. Export customer segments with RFM scores
        output("\n5. Exporting customer RFM analysis...", 'info');
        
        $rfmData = Customer::rfmAnalysis();
        $rfmData->toCsv(__DIR__ . '/../../exports/customer_rfm_segments.csv', [
            'header' => true,
            'delimiter' => ','
        ]);
        
        output("   ✓ Exported to: exports/customer_rfm_segments.csv", 'success');
        
        // 6. Export for S3 (example)
        output("\n6. Example S3 export (requires credentials):", 'info');
        output("   Order::toS3Parquet('my-bucket', 'analytics/orders.parquet')", 'info');
    }
    
    /**
     * Import data from various file formats
     */
    public static function importExamples()
    {
        output("\n=== FILE IMPORT EXAMPLES ===\n", 'info');
        
        // 1. Query Parquet file directly
        output("1. Querying Parquet file directly...", 'info');
        
        $parquetPath = __DIR__ . '/../../exports/orders_export.parquet';
        if (file_exists($parquetPath)) {
            $count = Order::fromParquet($parquetPath)
                ->where('total_amount', '>', 500)
                ->count();
                
            output("   ✓ Found {$count} high-value orders in Parquet file", 'success');
        } else {
            output("   ! Parquet file not found. Run export first.", 'warning');
        }
        
        // 2. Import CSV data
        output("\n2. Importing from CSV file...", 'info');
        
        // Create sample CSV
        $csvPath = __DIR__ . '/../../data/sample_products.csv';
        $csvContent = "sku,name,category,price\n";
        $csvContent .= "TEST001,Test Product 1,Electronics,99.99\n";
        $csvContent .= "TEST002,Test Product 2,Electronics,149.99\n";
        $csvContent .= "TEST003,Test Product 3,Clothing,39.99\n";
        
        file_put_contents($csvPath, $csvContent);
        
        $csvData = Product::fromCsv($csvPath, [
            'header' => true,
            'delimiter' => ','
        ])->get();
        
        output("   ✓ Read {$csvData->count()} products from CSV", 'success');
        
        // 3. Query multiple files with glob pattern
        output("\n3. Querying multiple Parquet files with glob...", 'info');
        
        $globPattern = __DIR__ . '/../../exports/orders_partitioned/**/*.parquet';
        if (glob($globPattern)) {
            $multiFileCount = Order::fromParquetGlob($globPattern)
                ->where('status', 'completed')
                ->count();
                
            output("   ✓ Found {$multiFileCount} orders across partitioned files", 'success');
        } else {
            output("   ! No partitioned files found. Run partitioned export first.", 'warning');
        }
        
        // 4. Import from HTTP/HTTPS
        output("\n4. Example HTTP import:", 'info');
        output("   Product::fromHttp('https://example.com/data.csv', 'csv')", 'info');
        
        // 5. Import from S3
        output("\n5. Example S3 import:", 'info');
        output("   Order::fromS3Parquet('my-bucket', 'analytics/orders.parquet')", 'info');
        
        // 6. Import JSON data
        output("\n6. Importing from JSON file...", 'info');
        
        $jsonPath = __DIR__ . '/../../data/sample_customers.json';
        $jsonData = [
            ['customer_id' => 'JSON001', 'email' => 'json1@example.com', 'first_name' => 'JSON', 'last_name' => 'User1'],
            ['customer_id' => 'JSON002', 'email' => 'json2@example.com', 'first_name' => 'JSON', 'last_name' => 'User2'],
        ];
        
        file_put_contents($jsonPath, json_encode($jsonData));
        
        $jsonCustomers = Customer::fromJsonFile($jsonPath, [
            'format' => 'array'
        ])->count();
        
        output("   ✓ Found {$jsonCustomers} customers in JSON file", 'success');
    }
    
    /**
     * Advanced file operations
     */
    public static function advancedFileOperations()
    {
        output("\n=== ADVANCED FILE OPERATIONS ===\n", 'info');
        
        // 1. Create and query external table
        output("1. Creating external table from Parquet files...", 'info');
        
        DB::connection('duckdb')->statement("
            CREATE OR REPLACE VIEW parquet_orders AS 
            SELECT * FROM read_parquet('exports/orders_export.parquet')
        ");
        
        $externalCount = DB::connection('duckdb')
            ->table('parquet_orders')
            ->count();
            
        output("   ✓ Created view with {$externalCount} records", 'success');
        
        // 2. Join file data with database tables
        output("\n2. Joining Parquet data with database tables...", 'info');
        
        $hybridQuery = DB::connection('duckdb')->select("
            SELECT 
                p.customer_id,
                c.email,
                COUNT(*) as parquet_orders,
                SUM(p.total_amount) as parquet_revenue
            FROM parquet_orders p
            JOIN customers c ON p.customer_id = c.customer_id
            GROUP BY p.customer_id, c.email
            ORDER BY parquet_revenue DESC
            LIMIT 5
        ");
        
        output("   ✓ Top 5 customers from Parquet data:", 'success');
        foreach ($hybridQuery as $row) {
            output("     - {$row->email}: {$row->parquet_orders} orders, \${$row->parquet_revenue}", 'info');
        }
        
        // 3. Copy data between formats
        output("\n3. Converting between file formats...", 'info');
        
        // Parquet to CSV
        DB::connection('duckdb')->statement("
            COPY (SELECT * FROM read_parquet('exports/orders_export.parquet'))
            TO 'exports/orders_converted.csv' (FORMAT CSV, HEADER TRUE)
        ");
        
        output("   ✓ Converted Parquet to CSV", 'success');
        
        // CSV to Parquet with compression
        if (file_exists(__DIR__ . '/../../exports/sales_trend.csv')) {
            DB::connection('duckdb')->statement("
                COPY (SELECT * FROM read_csv_auto('exports/sales_trend.csv'))
                TO 'exports/sales_trend_compressed.parquet' (FORMAT PARQUET, COMPRESSION ZSTD)
            ");
            
            output("   ✓ Converted CSV to compressed Parquet", 'success');
        }
        
        // 4. Metadata inspection
        output("\n4. Inspecting Parquet file metadata...", 'info');
        
        $parquetPath = __DIR__ . '/../../exports/orders_export.parquet';
        if (file_exists($parquetPath)) {
            $metadata = Order::scanParquetMetadata($parquetPath);
            
            output("   File: {$parquetPath}", 'info');
            output("   Rows: {$metadata->num_rows}", 'info');
            output("   Size: " . number_format($metadata->file_size / 1024 / 1024, 2) . " MB", 'info');
            
            $schema = Order::scanParquetSchema($parquetPath);
            output("   Columns: {$schema->count()}", 'info');
        }
        
        // 5. Efficient sampling from large files
        output("\n5. Sampling from large Parquet files...", 'info');
        
        $sample = Order::fromParquet($parquetPath)
            ->sample(0.01) // 1% sample
            ->limit(5)
            ->get();
            
        output("   ✓ Retrieved {$sample->count()} sample records", 'success');
    }
}