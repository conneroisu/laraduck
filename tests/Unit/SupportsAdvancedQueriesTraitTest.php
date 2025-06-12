<?php

namespace Laraduck\EloquentDuckDB\Tests\Unit;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Laraduck\EloquentDuckDB\Eloquent\Traits\SupportsAdvancedQueries;
use Laraduck\EloquentDuckDB\Tests\TestCase;
use Mockery as m;

class TestSupportsAdvancedQueriesModel extends AnalyticalModel
{
    use SupportsAdvancedQueries;
    
    protected $table = 'test_table';
}

class SupportsAdvancedQueriesTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_lead_lag_with_defaults()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->leadLag('sales');
        
        $this->assertStringContainsString('LAG(sales, 1)', $result->toSql());
    }

    public function test_lead_lag_with_offset_and_default()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->leadLag('sales', 3, 0, 'date', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('LAG(sales, 3, 0)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY date', $sql);
    }

    public function test_lead_function()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->lead('sales', 2, null, 'date');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('LEAD(sales, 2)', $sql);
        $this->assertStringContainsString('ORDER BY date', $sql);
    }

    public function test_lead_with_default_value()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->lead('sales', 1, 0, 'date', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('LEAD(sales, 1, 0)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY date', $sql);
    }

    public function test_row_number_with_order_and_partition()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->rowNumber('sales DESC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('ROW_NUMBER()', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY sales DESC', $sql);
    }

    public function test_rank_function()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->rank('sales DESC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('RANK()', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY sales DESC', $sql);
    }

    public function test_dense_rank_function()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->denseRank('sales DESC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('DENSE_RANK()', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY sales DESC', $sql);
    }

    public function test_percent_rank_function()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->percentRank('sales DESC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('PERCENT_RANK()', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY sales DESC', $sql);
    }

    public function test_ntile_function()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->ntile(4, 'sales DESC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('NTILE(4)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY sales DESC', $sql);
    }

    public function test_cumulative_distribution()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->cumulativeDistribution('sales DESC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('CUME_DIST()', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY sales DESC', $sql);
    }

    public function test_first_value_function()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->firstValue('sales', 'date ASC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('FIRST_VALUE(sales)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY date ASC', $sql);
    }

    public function test_first_value_with_frame()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->firstValue('sales', 'date ASC', 'region', 'ROWS UNBOUNDED PRECEDING');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('FIRST_VALUE(sales)', $sql);
        $this->assertStringContainsString('ROWS UNBOUNDED PRECEDING', $sql);
    }

    public function test_last_value_function()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->lastValue('sales', 'date ASC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('LAST_VALUE(sales)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY date ASC', $sql);
        $this->assertStringContainsString('RANGE BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING', $sql);
    }

    public function test_last_value_with_custom_frame()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->lastValue('sales', 'date ASC', 'region', 'ROWS BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('LAST_VALUE(sales)', $sql);
        $this->assertStringContainsString('ROWS BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING', $sql);
    }

    public function test_nth_value_function()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->nthValue('sales', 3, 'date ASC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('NTH_VALUE(sales, 3)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY date ASC', $sql);
    }

    public function test_window_aggregate_sum()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->windowAggregate('sum', 'sales', 'date', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('SUM(sales)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY date', $sql);
    }

    public function test_window_aggregate_with_frame()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $frame = 'ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING';
        $result = $model->windowAggregate('avg', 'sales', 'date', 'region', $frame);
        
        $sql = $result->toSql();
        $this->assertStringContainsString('AVG(sales)', $sql);
        $this->assertStringContainsString($frame, $sql);
    }

    public function test_running_total()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->runningTotal('sales', 'date', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('SUM(sales)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY date', $sql);
        $this->assertStringContainsString('ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW', $sql);
    }

    public function test_running_average()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->runningAverage('sales', 'date', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('AVG(sales)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY date', $sql);
        $this->assertStringContainsString('ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW', $sql);
    }

    public function test_moving_average()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->movingAverage('sales', 3, 1, 'date', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('AVG(sales)', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY date', $sql);
        $this->assertStringContainsString('ROWS BETWEEN 3 PRECEDING AND 1 FOLLOWING', $sql);
    }

    public function test_with_row_numbers_default_alias()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->withRowNumbers();
        
        $sql = $result->toSql();
        $this->assertStringContainsString('ROW_NUMBER() OVER ()', $sql);
        $this->assertStringContainsString('AS row_num', $sql);
    }

    public function test_with_row_numbers_custom_alias()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->withRowNumbers('rn', 'sales DESC', 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('ROW_NUMBER() OVER (', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY sales DESC', $sql);
        $this->assertStringContainsString('AS rn', $sql);
    }

    public function test_top_n_per_group()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->topNPerGroup(3, 'region', 'sales DESC');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('ROW_NUMBER() OVER (', $sql);
        $this->assertStringContainsString('PARTITION BY region', $sql);
        $this->assertStringContainsString('ORDER BY sales DESC', $sql);
        $this->assertStringContainsString('AS rn', $sql);
        $this->assertStringContainsString('qualify', $sql);
        $this->assertStringContainsString('rn', $sql);
    }

    public function test_with_percentiles_default()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->withPercentiles('sales');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('PERCENTILE_CONT(0.25)', $sql);
        $this->assertStringContainsString('PERCENTILE_CONT(0.5)', $sql);
        $this->assertStringContainsString('PERCENTILE_CONT(0.75)', $sql);
        $this->assertStringContainsString('ORDER BY sales', $sql);
        $this->assertStringContainsString('AS p25_sales', $sql);
        $this->assertStringContainsString('AS p50_sales', $sql);
        $this->assertStringContainsString('AS p75_sales', $sql);
    }

    public function test_with_percentiles_custom()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $result = $model->withPercentiles('sales', [0.1, 0.9], 'region');
        
        $sql = $result->toSql();
        $this->assertStringContainsString('PERCENTILE_CONT(0.1)', $sql);
        $this->assertStringContainsString('PERCENTILE_CONT(0.9)', $sql);
        $this->assertStringContainsString('ORDER BY sales', $sql);
        $this->assertStringContainsString('OVER (PARTITION BY region)', $sql);
        $this->assertStringContainsString('AS p10_sales', $sql);
        $this->assertStringContainsString('AS p90_sales', $sql);
    }

    public function test_sample_bernoulli_default()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $queryBuilder = m::mock();
        $queryBuilder->shouldReceive('sample')->with(0.1, 'BERNOULLI')->andReturnSelf();
        
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        $result = $model->sample(0.1);
        
        $this->assertSame($queryBuilder, $result);
    }

    public function test_sample_reservoir_method()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $queryBuilder = m::mock();
        $queryBuilder->shouldReceive('sample')->with(0.05, 'RESERVOIR')->andReturnSelf();
        
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        $result = $model->sample(0.05, 'RESERVOIR');
        
        $this->assertSame($queryBuilder, $result);
    }

    public function test_group_by_all()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $queryBuilder = m::mock();
        $queryBuilder->shouldReceive('groupByAll')->andReturnSelf();
        
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        $result = $model->groupByAll();
        
        $this->assertSame($queryBuilder, $result);
    }

    public function test_qualify_basic()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $queryBuilder = m::mock();
        $queryBuilder->shouldReceive('qualify')->with('rn', '<=', 5, 'and')->andReturnSelf();
        
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        $result = $model->qualify('rn', '<=', 5);
        
        $this->assertSame($queryBuilder, $result);
    }

    public function test_or_qualify()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $queryBuilder = m::mock();
        $queryBuilder->shouldReceive('orQualify')->with('rn', '>', 10)->andReturnSelf();
        
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        $result = $model->orQualify('rn', '>', 10);
        
        $this->assertSame($queryBuilder, $result);
    }

    public function test_with_cte()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $queryBuilder = m::mock();
        $subQuery = m::mock();
        
        $queryBuilder->shouldReceive('withCte')
            ->with('high_sales', $subQuery, null, true)
            ->andReturnSelf();
        
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        $result = $model->withCte('high_sales', $subQuery);
        
        $this->assertSame($queryBuilder, $result);
    }

    public function test_with_recursive_cte()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $queryBuilder = m::mock();
        $baseQuery = m::mock();
        $recursiveQuery = m::mock();
        
        $queryBuilder->shouldReceive('withRecursiveCte')
            ->with('hierarchy', $baseQuery, $recursiveQuery, null)
            ->andReturnSelf();
        
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        $result = $model->withRecursiveCte('hierarchy', $baseQuery, $recursiveQuery);
        
        $this->assertSame($queryBuilder, $result);
    }

    public function test_insert_using()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $queryBuilder = m::mock();
        $sourceQuery = m::mock();
        
        $columns = ['id', 'name', 'amount'];
        $queryBuilder->shouldReceive('insertUsing')
            ->with($columns, $sourceQuery)
            ->andReturnSelf();
        
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        $result = $model->insertUsing($columns, $sourceQuery);
        
        $this->assertSame($queryBuilder, $result);
    }

    public function test_upsert()
    {
        $model = new TestSupportsAdvancedQueriesModel();
        $queryBuilder = m::mock();
        
        $values = [['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']];
        $uniqueBy = 'id';
        $update = ['name'];
        
        $queryBuilder->shouldReceive('upsert')
            ->with($values, $uniqueBy, $update)
            ->andReturnSelf();
        
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        $result = $model->upsert($values, $uniqueBy, $update);
        
        $this->assertSame($queryBuilder, $result);
    }
}