<?php

namespace Laraduck\EloquentDuckDB\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesFiles;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesDataFiles;
use Laraduck\EloquentDuckDB\Tests\TestCase;

class SalesAnalytics extends AnalyticalModel
{
    use QueriesFiles, QueriesDataFiles;
    
    protected $table = 'sales_analytics';
    protected $fillable = ['product_id', 'customer_id', 'amount', 'sale_date', 'region'];
    public $timestamps = false;
}

class FileQueriesIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test table for comparisons
        DB::statement('CREATE TABLE sales_analytics (
            product_id INTEGER,
            customer_id INTEGER,
            amount DECIMAL(10,2),
            sale_date DATE,
            region VARCHAR
        )');
        
        // Insert test data
        DB::table('sales_analytics')->insert([
            ['product_id' => 1, 'customer_id' => 101, 'amount' => 299.99, 'sale_date' => '2024-01-15', 'region' => 'North'],
            ['product_id' => 2, 'customer_id' => 102, 'amount' => 199.50, 'sale_date' => '2024-01-16', 'region' => 'South'],
            ['product_id' => 1, 'customer_id' => 103, 'amount' => 299.99, 'sale_date' => '2024-01-17', 'region' => 'North'],
            ['product_id' => 3, 'customer_id' => 104, 'amount' => 399.00, 'sale_date' => '2024-01-18', 'region' => 'East'],
            ['product_id' => 2, 'customer_id' => 105, 'amount' => 199.50, 'sale_date' => '2024-01-19', 'region' => 'West'],
        ]);
    }

    public function test_json_querying_integration()
    {
        // Create a table with JSON data
        DB::statement('CREATE TABLE json_data (id INTEGER, metadata JSON)');
        
        DB::table('json_data')->insert([
            ['id' => 1, 'metadata' => '{"status": "active", "tags": ["priority", "urgent"], "user": {"name": "John", "role": "admin"}}'],
            ['id' => 2, 'metadata' => '{"status": "inactive", "tags": ["regular"], "user": {"name": "Jane", "role": "user"}}'],
            ['id' => 3, 'metadata' => '{"status": "active", "tags": ["priority"], "user": {"name": "Bob", "role": "moderator"}}'],
        ]);

        // Test JSON path queries
        $activeRecords = SalesAnalytics::query()
            ->from('json_data')
            ->whereJsonPath('metadata', 'status', '=', 'active')
            ->get();
        
        $this->assertCount(2, $activeRecords);
        
        // Test JSON contains key
        $recordsWithTags = SalesAnalytics::query()
            ->from('json_data')
            ->whereJsonContainsKey('metadata', 'tags')
            ->get();
        
        $this->assertCount(3, $recordsWithTags);
        
        // Test JSON extraction
        $userNames = SalesAnalytics::query()
            ->from('json_data')
            ->selectJsonExtract('metadata', 'user.name', 'username')
            ->get();
        
        $this->assertCount(3, $userNames);
        $userNameValues = $userNames->pluck('username')->toArray();
        $this->assertContains('John', $userNameValues);
        $this->assertContains('Jane', $userNameValues);
        $this->assertContains('Bob', $userNameValues);
    }

    public function test_batch_operations_with_chunked_data()
    {
        $largeDataset = [];
        for ($i = 1; $i <= 1000; $i++) {
            $largeDataset[] = [
                'product_id' => ($i % 10) + 1,
                'customer_id' => $i + 1000,
                'amount' => rand(100, 1000) / 10,
                'sale_date' => '2024-01-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT),
                'region' => ['North', 'South', 'East', 'West'][$i % 4],
            ];
        }
        
        // Test chunked batch insert
        $rowsInserted = SalesAnalytics::insertBatch($largeDataset, 100);
        
        $this->assertEquals(1000, $rowsInserted);
        $this->assertEquals(1005, SalesAnalytics::count()); // 5 original + 1000 new
        
        // Verify data integrity
        $totalAmount = SalesAnalytics::sum('amount');
        $this->assertGreaterThan(0, $totalAmount);
        
        $distinctProducts = SalesAnalytics::distinct('product_id')->count();
        $this->assertGreaterThanOrEqual(10, $distinctProducts);
    }

    public function test_advanced_analytical_queries()
    {
        // Create time series data
        DB::statement('CREATE TABLE metrics (timestamp TIMESTAMP, metric_name VARCHAR, value DECIMAL(10,2), device_id VARCHAR)');
        
        $timeSeriesData = [];
        $startTime = now()->subDays(7);
        
        for ($i = 0; $i < 168; $i++) { // 24 hours * 7 days
            foreach (['cpu', 'memory', 'disk'] as $metric) {
                foreach (['device1', 'device2', 'device3'] as $device) {
                    $timeSeriesData[] = [
                        'timestamp' => $startTime->copy()->addHours($i)->format('Y-m-d H:i:s'),
                        'metric_name' => $metric,
                        'value' => rand(10, 100),
                        'device_id' => $device,
                    ];
                }
            }
        }
        
        DB::table('metrics')->insert($timeSeriesData);
        
        // Test window functions for running averages
        $runningAverages = SalesAnalytics::query()
            ->from('metrics')
            ->selectRaw('
                timestamp,
                device_id,
                metric_name,
                value,
                AVG(value) OVER (
                    PARTITION BY device_id, metric_name 
                    ORDER BY timestamp 
                    ROWS BETWEEN 2 PRECEDING AND CURRENT ROW
                ) as moving_avg_3h
            ')
            ->where('metric_name', 'cpu')
            ->orderBy('device_id')
            ->orderBy('timestamp')
            ->limit(10)
            ->get();
        
        $this->assertCount(10, $runningAverages);
        $this->assertNotNull($runningAverages->first()->moving_avg_3h);
        
        // Test QUALIFY clause for top values per group
        $topMetricsPerDevice = SalesAnalytics::query()
            ->from('metrics')
            ->selectRaw('
                device_id,
                metric_name,
                timestamp,
                value,
                ROW_NUMBER() OVER (PARTITION BY device_id ORDER BY value DESC) as rank
            ')
            ->qualify('rank', '<=', 3)
            ->orderBy('device_id')
            ->orderBy('rank')
            ->get();
        
        $this->assertGreaterThan(0, $topMetricsPerDevice->count());
        $deviceCounts = $topMetricsPerDevice->groupBy('device_id')->map->count();
        
        // Each device should have exactly 3 top metrics
        foreach ($deviceCounts as $count) {
            $this->assertEquals(3, $count);
        }
    }

    public function test_cte_and_recursive_queries()
    {
        // Create hierarchical data
        DB::statement('CREATE TABLE employees (id INTEGER, name VARCHAR, manager_id INTEGER, department VARCHAR)');
        
        DB::table('employees')->insert([
            ['id' => 1, 'name' => 'CEO', 'manager_id' => null, 'department' => 'Executive'],
            ['id' => 2, 'name' => 'VP Engineering', 'manager_id' => 1, 'department' => 'Engineering'],
            ['id' => 3, 'name' => 'VP Sales', 'manager_id' => 1, 'department' => 'Sales'],
            ['id' => 4, 'name' => 'Senior Engineer', 'manager_id' => 2, 'department' => 'Engineering'],
            ['id' => 5, 'name' => 'Junior Engineer', 'manager_id' => 4, 'department' => 'Engineering'],
            ['id' => 6, 'name' => 'Sales Manager', 'manager_id' => 3, 'department' => 'Sales'],
            ['id' => 7, 'name' => 'Sales Rep', 'manager_id' => 6, 'department' => 'Sales'],
        ]);
        
        // Test CTE for department summary
        $departmentStats = DB::query()
            ->fromSub(function($query) {
                $query->from('employees')
                    ->select('department')
                    ->selectRaw('COUNT(*) as employee_count')
                    ->selectRaw('COUNT(CASE WHEN manager_id IS NULL THEN 1 END) as managers')
                    ->groupBy('department');
            }, 'dept_stats')
            ->get();
        
        $this->assertCount(3, $departmentStats);
        
        // Find engineering department stats
        $engineeringStats = $departmentStats->firstWhere('department', 'Engineering');
        $this->assertNotNull($engineeringStats);
        $this->assertEquals(3, $engineeringStats->employee_count);
        
        // Test hierarchical query using recursive CTE (simulated with joins)
        $hierarchy = DB::query()
            ->from('employees as e1')
            ->leftJoin('employees as e2', 'e1.manager_id', '=', 'e2.id')
            ->select([
                'e1.id',
                'e1.name',
                'e1.department',
                'e2.name as manager_name'
            ])
            ->orderBy('e1.id')
            ->get();
        
        $this->assertCount(7, $hierarchy);
        
        $ceo = $hierarchy->firstWhere('id', 1);
        $this->assertNull($ceo->manager_name);
        
        $vp = $hierarchy->firstWhere('id', 2);
        $this->assertEquals('CEO', $vp->manager_name);
    }

    public function test_sampling_and_statistical_functions()
    {
        // Generate larger dataset for sampling
        $statisticalData = [];
        for ($i = 1; $i <= 10000; $i++) {
            $statisticalData[] = [
                'product_id' => ($i % 50) + 1,
                'customer_id' => $i,
                'amount' => rand(10, 1000),
                'sale_date' => '2024-01-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT),
                'region' => ['North', 'South', 'East', 'West', 'Central'][$i % 5],
            ];
        }
        
        SalesAnalytics::insertBatch($statisticalData, 1000);
        
        // Test sampling
        $sample = SalesAnalytics::query()
            ->sample(0.01) // 1% sample
            ->get();
        
        $this->assertGreaterThan(50, $sample->count());
        $this->assertLessThan(200, $sample->count());
        
        // Test statistical aggregations
        $stats = SalesAnalytics::query()
            ->selectRaw('
                region,
                COUNT(*) as total_sales,
                AVG(amount) as avg_amount,
                STDDEV(amount) as stddev_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY amount) as median_amount
            ')
            ->groupBy('region')
            ->orderBy('region')
            ->get();
        
        $this->assertCount(5, $stats);
        
        foreach ($stats as $stat) {
            $this->assertGreaterThan(0, $stat->total_sales);
            $this->assertGreaterThan(0, $stat->avg_amount);
            $this->assertGreaterThanOrEqual(10, $stat->min_amount);
            $this->assertLessThanOrEqual(1000, $stat->max_amount);
            $this->assertNotNull($stat->median_amount);
        }
    }

    public function test_complex_grouping_and_rollup()
    {
        // Test GROUP BY ALL functionality
        $groupByAllResults = SalesAnalytics::query()
            ->selectRaw('region, product_id, COUNT(*) as sales_count')
            ->groupByAll()
            ->orderBy('region')
            ->orderBy('product_id')
            ->get();
        
        $this->assertGreaterThan(0, $groupByAllResults->count());
        
        // Test complex aggregations with CASE statements
        $complexStats = SalesAnalytics::query()
            ->selectRaw('
                region,
                COUNT(*) as total_sales,
                COUNT(CASE WHEN amount > 250 THEN 1 END) as high_value_sales,
                COUNT(CASE WHEN amount <= 250 THEN 1 END) as low_value_sales,
                SUM(CASE WHEN amount > 250 THEN amount ELSE 0 END) as high_value_revenue,
                AVG(CASE WHEN amount > 250 THEN amount END) as avg_high_value
            ')
            ->groupBy('region')
            ->having('total_sales', '>', 0)
            ->orderBy('total_sales', 'desc')
            ->get();
        
        $this->assertGreaterThan(0, $complexStats->count());
        
        foreach ($complexStats as $stat) {
            $this->assertEquals($stat->total_sales, $stat->high_value_sales + $stat->low_value_sales);
        }
    }

    public function test_temporal_queries_and_date_functions()
    {
        // Test date functions and temporal queries
        $temporalAnalysis = SalesAnalytics::query()
            ->selectRaw('
                DATE_TRUNC(\'month\', sale_date) as month,
                COUNT(*) as sales_count,
                SUM(amount) as monthly_revenue,
                AVG(amount) as avg_sale_amount,
                COUNT(DISTINCT customer_id) as unique_customers
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        $this->assertCount(1, $temporalAnalysis); // All data is from January 2024
        
        $januaryData = $temporalAnalysis->first();
        $this->assertStringContainsString('2024-01', $januaryData->month);
        $this->assertGreaterThan(0, $januaryData->sales_count);
        $this->assertGreaterThan(0, $januaryData->monthly_revenue);
        
        // Test day of week analysis
        $dayOfWeekAnalysis = SalesAnalytics::query()
            ->selectRaw('
                DAYNAME(sale_date) as day_name,
                DAYOFWEEK(sale_date) as day_num,
                COUNT(*) as sales_count,
                AVG(amount) as avg_amount
            ')
            ->groupBy('day_name', 'day_num')
            ->orderBy('day_num')
            ->get();
        
        $this->assertGreaterThan(0, $dayOfWeekAnalysis->count());
        
        foreach ($dayOfWeekAnalysis as $dayStats) {
            $this->assertNotNull($dayStats->day_name);
            $this->assertBetween($dayStats->day_num, 1, 7);
            $this->assertGreaterThan(0, $dayStats->sales_count);
        }
    }

    public function test_data_quality_and_validation_queries()
    {
        // Insert some records with data quality issues
        SalesAnalytics::insertBatch([
            ['product_id' => null, 'customer_id' => 999, 'amount' => 100, 'sale_date' => '2024-01-20', 'region' => 'North'],
            ['product_id' => 1, 'customer_id' => null, 'amount' => 200, 'sale_date' => '2024-01-21', 'region' => 'South'],
            ['product_id' => 2, 'customer_id' => 998, 'amount' => null, 'sale_date' => '2024-01-22', 'region' => 'East'],
            ['product_id' => 3, 'customer_id' => 997, 'amount' => 0, 'sale_date' => '2024-01-23', 'region' => ''],
        ]);
        
        // Data quality assessment
        $qualityReport = SalesAnalytics::query()
            ->selectRaw('
                COUNT(*) as total_records,
                COUNT(product_id) as valid_product_ids,
                COUNT(customer_id) as valid_customer_ids,
                COUNT(amount) as valid_amounts,
                COUNT(CASE WHEN amount > 0 THEN 1 END) as positive_amounts,
                COUNT(CASE WHEN region != \'\' THEN 1 END) as valid_regions,
                COUNT(*) - COUNT(product_id) as missing_product_ids,
                COUNT(*) - COUNT(customer_id) as missing_customer_ids,
                COUNT(*) - COUNT(amount) as missing_amounts
            ')
            ->first();
        
        $this->assertNotNull($qualityReport);
        $this->assertGreaterThan(0, $qualityReport->total_records);
        $this->assertGreaterThan(0, $qualityReport->missing_product_ids);
        $this->assertGreaterThan(0, $qualityReport->missing_customer_ids);
        $this->assertGreaterThan(0, $qualityReport->missing_amounts);
        
        // Find duplicate detection
        $potentialDuplicates = SalesAnalytics::query()
            ->selectRaw('
                product_id,
                customer_id,
                amount,
                sale_date,
                COUNT(*) as occurrence_count
            ')
            ->groupBy('product_id', 'customer_id', 'amount', 'sale_date')
            ->having('occurrence_count', '>', 1)
            ->get();
        
        // Should find duplicates from our initial setup data
        $this->assertGreaterThan(0, $potentialDuplicates->count());
    }

    private function assertBetween($value, $min, $max, $message = '')
    {
        $this->assertGreaterThanOrEqual($min, $value, $message);
        $this->assertLessThanOrEqual($max, $value, $message);
    }
}