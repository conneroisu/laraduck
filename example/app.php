<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;

// Setup database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'duckdb',
    'database' => ':memory:',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Define a sample model
class SalesData extends AnalyticalModel
{
    protected $table = 'sales';
    protected $allowSingleRowOperations = true;
    public $timestamps = false;
}

// Example usage
try {
    echo "Laravel DuckDB Example Application\n";
    echo "==================================\n\n";
    
    // Create table
    Capsule::schema()->create('sales', function ($table) {
        $table->integer('id');
        $table->string('product');
        $table->string('region');
        $table->decimal('amount', 10, 2);
        $table->date('sale_date');
    });
    
    echo "✓ Table created\n";
    
    // Insert sample data
    $data = [
        ['id' => 1, 'product' => 'Widget', 'region' => 'North', 'amount' => 100.50, 'sale_date' => '2024-01-15'],
        ['id' => 2, 'product' => 'Gadget', 'region' => 'South', 'amount' => 250.00, 'sale_date' => '2024-01-16'],
        ['id' => 3, 'product' => 'Widget', 'region' => 'North', 'amount' => 175.25, 'sale_date' => '2024-01-17'],
        ['id' => 4, 'product' => 'Gadget', 'region' => 'East', 'amount' => 300.00, 'sale_date' => '2024-01-18'],
        ['id' => 5, 'product' => 'Widget', 'region' => 'West', 'amount' => 225.75, 'sale_date' => '2024-01-19'],
    ];
    
    SalesData::insertBatch($data);
    echo "✓ Sample data inserted\n\n";
    
    // Basic query
    echo "1. Basic Query - All sales:\n";
    $allSales = SalesData::all();
    foreach ($allSales as $sale) {
        echo "   - {$sale->product} in {$sale->region}: \${$sale->amount}\n";
    }
    
    // Aggregation
    echo "\n2. Aggregation - Total sales by product:\n";
    $productTotals = SalesData::query()
        ->selectRaw('product, SUM(amount) as total')
        ->groupBy('product')
        ->get();
    
    foreach ($productTotals as $total) {
        echo "   - {$total->product}: \${$total->total}\n";
    }
    
    // Window function
    echo "\n3. Window Function - Running total:\n";
    $runningTotals = Capsule::table('sales')
        ->selectRaw('*, SUM(amount) OVER (ORDER BY sale_date) as running_total')
        ->orderBy('sale_date')
        ->get();
    
    foreach ($runningTotals as $row) {
        echo "   - {$row->sale_date}: \${$row->amount} (Running: \${$row->running_total})\n";
    }
    
    // Sample data
    echo "\n4. Sampling - 60% sample:\n";
    $sample = SalesData::samplePercent(60)->get();
    echo "   - Sampled {$sample->count()} out of 5 records\n";
    
    // Export example (would work with real file path)
    echo "\n5. Export capabilities:\n";
    echo "   - toParquet('/path/to/export.parquet')\n";
    echo "   - toCsv('/path/to/export.csv')\n";
    echo "   - toJson('/path/to/export.json')\n";
    
    // File query example (would work with real files)
    echo "\n6. File querying capabilities:\n";
    echo "   - fromParquet('/path/to/data.parquet')\n";
    echo "   - fromCsv('/path/to/data.csv')\n";
    echo "   - fromS3Parquet('bucket', 'key')\n";
    
    echo "\n✅ Example completed successfully!\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}