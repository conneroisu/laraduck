<?php

namespace Laraduck\EloquentDuckDB\Tests\Unit;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesFiles;
use Laraduck\EloquentDuckDB\Tests\TestCase;
use Mockery as m;

class TestQueriesFilesModel extends AnalyticalModel
{
    use QueriesFiles;
    
    protected $table = 'test_table';
}

class QueriesFilesTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_from_parquet_creates_correct_query()
    {
        $model = TestQueriesFilesModel::fromParquet('data/sales.parquet');
        
        $this->assertStringContainsString("'data/sales.parquet'", $model->toSql());
    }

    public function test_from_parquet_with_alias()
    {
        $model = TestQueriesFilesModel::fromParquet('data/sales.parquet', 'sales_data');
        
        $sql = $model->toSql();
        $this->assertStringContainsString("'data/sales.parquet' AS sales_data", $sql);
    }

    public function test_from_parquet_glob_pattern()
    {
        $model = TestQueriesFilesModel::fromParquetGlob('data/sales_*.parquet');
        
        $this->assertStringContainsString("'data/sales_*.parquet'", $model->toSql());
    }

    public function test_scan_parquet_metadata_builds_correct_query()
    {
        $connection = m::mock();
        $connection->shouldReceive('select')
            ->with("SELECT * FROM parquet_metadata('data/test.parquet')")
            ->once()
            ->andReturn([
                (object) ['file_name' => 'test.parquet', 'row_groups' => 1, 'total_rows' => 100]
            ]);

        $model = new TestQueriesFilesModel();
        $model->setConnection($connection);

        $result = $model::scanParquetMetadata('data/test.parquet');
        
        $this->assertEquals('test.parquet', $result->file_name);
        $this->assertEquals(1, $result->row_groups);
        $this->assertEquals(100, $result->total_rows);
    }

    public function test_scan_parquet_schema_builds_correct_query()
    {
        $connection = m::mock();
        $connection->shouldReceive('select')
            ->with("SELECT * FROM parquet_schema('data/test.parquet')")
            ->once()
            ->andReturn([
                (object) ['name' => 'id', 'type' => 'BIGINT'],
                (object) ['name' => 'name', 'type' => 'VARCHAR'],
            ]);

        $model = new TestQueriesFilesModel();
        $model->setConnection($connection);

        $result = $model::scanParquetSchema('data/test.parquet');
        
        $this->assertCount(2, $result);
        $this->assertEquals('id', $result[0]->name);
        $this->assertEquals('BIGINT', $result[0]->type);
    }

    public function test_create_table_from_parquet_without_options()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("CREATE TABLE sales AS SELECT * FROM read_parquet('data/sales.parquet')")
            ->once()
            ->andReturn(true);

        $model = new TestQueriesFilesModel();
        $model->setConnection($connection);

        $result = $model::createTableFromParquet('sales', 'data/sales.parquet');
        
        $this->assertTrue($result);
    }

    public function test_create_table_from_parquet_with_options()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("CREATE TABLE sales AS SELECT * FROM read_parquet('data/sales.parquet', compression = snappy, hive_partitioning = true)")
            ->once()
            ->andReturn(true);

        $model = new TestQueriesFilesModel();
        $model->setConnection($connection);

        $options = ['compression' => 'snappy', 'hive_partitioning' => 'true'];
        $result = $model::createTableFromParquet('sales', 'data/sales.parquet', $options);
        
        $this->assertTrue($result);
    }

    public function test_to_parquet_without_options()
    {
        $queryBuilder = m::mock(QueryBuilder::class);
        $queryBuilder->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $queryBuilder->shouldReceive('getBindings')->andReturn([]);

        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/data.parquet' (FORMAT PARQUET)", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesFilesModel();
        $model->setConnection($connection);
        
        // Mock the query builder methods
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $result = $model->toParquet('output/data.parquet');
        
        $this->assertTrue($result);
    }

    public function test_to_parquet_with_options()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/data.parquet' (FORMAT PARQUET, compression 'snappy', row_group_size 100000)", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesFilesModel();
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $options = ['compression' => 'snappy', 'row_group_size' => 100000];
        $result = $model->toParquet('output/data.parquet', $options);
        
        $this->assertTrue($result);
    }

    public function test_export_to_parquet_partitioned_single_partition()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/partitioned' (FORMAT PARQUET, PARTITION_BY (region))", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesFilesModel();
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $result = $model->exportToParquetPartitioned('output/partitioned', 'region');
        
        $this->assertTrue($result);
    }

    public function test_export_to_parquet_partitioned_multiple_partitions()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/partitioned' (FORMAT PARQUET, PARTITION_BY (region, year))", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesFilesModel();
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $result = $model->exportToParquetPartitioned('output/partitioned', ['region', 'year']);
        
        $this->assertTrue($result);
    }

    public function test_from_s3_parquet_without_credentials()
    {
        $model = TestQueriesFilesModel::fromS3Parquet('my-bucket', 'data/sales.parquet');
        
        $this->assertStringContainsString("'s3://my-bucket/data/sales.parquet'", $model->toSql());
    }

    public function test_from_s3_parquet_with_credentials()
    {
        $connection = m::mock();
        $connection->shouldReceive('setSetting')
            ->with('s3_access_key_id', 'test-key')
            ->once();
        $connection->shouldReceive('setSetting')
            ->with('s3_secret_access_key', 'test-secret')
            ->once();

        $model = new TestQueriesFilesModel();
        $model->setConnection($connection);

        $credentials = [
            's3_access_key_id' => 'test-key',
            's3_secret_access_key' => 'test-secret'
        ];

        $result = $model::fromS3Parquet('my-bucket', 'data/sales.parquet', $credentials);
        
        $this->assertStringContainsString("'s3://my-bucket/data/sales.parquet'", $result->toSql());
    }

    public function test_read_parquet_arrow()
    {
        $model = TestQueriesFilesModel::readParquetArrow('data/sales.parquet');
        
        $this->assertStringContainsString("arrow_scan('data/sales.parquet')", $model->toSql());
    }

    public function test_from_iceberg_without_options()
    {
        $model = TestQueriesFilesModel::fromIceberg('path/to/iceberg/table');
        
        $this->assertStringContainsString("iceberg_scan('path/to/iceberg/table')", $model->toSql());
    }

    public function test_from_iceberg_with_options()
    {
        $model = TestQueriesFilesModel::fromIceberg('path/to/iceberg/table', [
            'allow_moved_paths' => true,
            'metadata_compression_codec' => 'gzip'
        ]);
        
        $sql = $model->toSql();
        $this->assertStringContainsString("iceberg_scan('path/to/iceberg/table'", $sql);
        $this->assertStringContainsString("allow_moved_paths = TRUE", $sql);
        $this->assertStringContainsString("metadata_compression_codec = 'gzip'", $sql);
    }

    public function test_from_delta_lake_without_options()
    {
        $model = TestQueriesFilesModel::fromDeltaLake('path/to/delta/table');
        
        $this->assertStringContainsString("delta_scan('path/to/delta/table')", $model->toSql());
    }

    public function test_from_delta_lake_with_options()
    {
        $model = TestQueriesFilesModel::fromDeltaLake('path/to/delta/table', [
            'version' => 42,
            'timestamp' => '2023-01-01'
        ]);
        
        $sql = $model->toSql();
        $this->assertStringContainsString("delta_scan('path/to/delta/table'", $sql);
        $this->assertStringContainsString("version = 42", $sql);
        $this->assertStringContainsString("timestamp = 2023-01-01", $sql);
    }
}