<?php

namespace Laraduck\EloquentDuckDB\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Laraduck\EloquentDuckDB\Tests\TestCase;

class TestAnalyticalModel extends AnalyticalModel
{
    protected $table = 'analytics_data';
    protected $fillable = ['metric', 'value', 'timestamp'];
    public $timestamps = false;
}

class AnalyticalModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        DB::statement('CREATE TABLE analytics_data (id INTEGER, metric VARCHAR, value DECIMAL, timestamp TIMESTAMP)');
    }
    
    public function test_batch_insert()
    {
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data[] = [
                'id' => $i,
                'metric' => 'metric_' . ($i % 10),
                'value' => rand(100, 1000),
                'timestamp' => now()->subDays($i),
            ];
        }
        
        $rowsInserted = TestAnalyticalModel::insertBatch($data);
        
        $this->assertEquals(100, $rowsInserted);
        $this->assertEquals(100, TestAnalyticalModel::count());
    }
    
    public function test_single_row_operations_throw_exception()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Single-row operations are not recommended');
        
        $model = new TestAnalyticalModel();
        $model->metric = 'test';
        $model->value = 100;
        $model->save();
    }
    
    public function test_window_functions_on_model()
    {
        TestAnalyticalModel::insertBatch([
            ['id' => 1, 'metric' => 'cpu', 'value' => 50, 'timestamp' => now()],
            ['id' => 2, 'metric' => 'cpu', 'value' => 60, 'timestamp' => now()->addMinute()],
            ['id' => 3, 'metric' => 'cpu', 'value' => 55, 'timestamp' => now()->addMinutes(2)],
            ['id' => 4, 'metric' => 'memory', 'value' => 80, 'timestamp' => now()],
            ['id' => 5, 'metric' => 'memory', 'value' => 85, 'timestamp' => now()->addMinute()],
        ]);
        
        $results = TestAnalyticalModel::query()
            ->selectRaw('*, AVG(value) OVER (PARTITION BY metric) as avg_value')
            ->get();
        
        $cpuAvg = $results->where('metric', 'cpu')->first()->avg_value;
        $this->assertEquals(55, $cpuAvg);
    }
    
    public function test_running_total()
    {
        TestAnalyticalModel::insertBatch([
            ['id' => 1, 'metric' => 'sales', 'value' => 100, 'timestamp' => now()],
            ['id' => 2, 'metric' => 'sales', 'value' => 200, 'timestamp' => now()->addDay()],
            ['id' => 3, 'metric' => 'sales', 'value' => 150, 'timestamp' => now()->addDays(2)],
        ]);
        
        $results = TestAnalyticalModel::query()
            ->runningTotal('value', 'timestamp')
            ->orderBy('timestamp')
            ->get();
        
        $this->assertEquals(100, $results[0]->value);
        $this->assertEquals(300, $results[1]->sum);
        $this->assertEquals(450, $results[2]->sum);
    }
    
    public function test_top_n_per_group()
    {
        $data = [];
        foreach (['A', 'B', 'C'] as $group) {
            for ($i = 1; $i <= 5; $i++) {
                $data[] = [
                    'id' => count($data) + 1,
                    'metric' => $group,
                    'value' => rand(1, 100),
                    'timestamp' => now(),
                ];
            }
        }
        
        TestAnalyticalModel::insertBatch($data);
        
        $topTwo = TestAnalyticalModel::query()
            ->topNPerGroup(2, 'metric', 'value DESC')
            ->orderBy('metric')
            ->orderBy('value', 'desc')
            ->get();
        
        $this->assertEquals(6, $topTwo->count());
        
        $groupCounts = $topTwo->groupBy('metric')->map->count();
        $this->assertEquals(2, $groupCounts['A']);
        $this->assertEquals(2, $groupCounts['B']);
        $this->assertEquals(2, $groupCounts['C']);
    }
    
    public function test_sample_percent()
    {
        $data = [];
        for ($i = 1; $i <= 10000; $i++) {
            $data[] = [
                'id' => $i,
                'metric' => 'test',
                'value' => $i,
                'timestamp' => now(),
            ];
        }
        
        TestAnalyticalModel::insertBatch($data, 1000);
        
        $sample = TestAnalyticalModel::samplePercent(1)->get();
        
        $this->assertGreaterThan(50, $sample->count());
        $this->assertLessThan(150, $sample->count());
    }
}