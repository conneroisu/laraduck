<?php

namespace Laraduck\EloquentDuckDB\Tests\Unit;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesDataFiles;
use Laraduck\EloquentDuckDB\Tests\TestCase;
use Mockery as m;

class TestQueriesDataFilesModel extends AnalyticalModel
{
    use QueriesDataFiles;
    
    protected $table = 'test_table';
}

class QueriesDataFilesTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_from_csv_auto()
    {
        $model = TestQueriesDataFilesModel::fromCsv('data/sales.csv');
        
        $this->assertStringContainsString("read_csv_auto('data/sales.csv')", $model->toSql());
    }

    public function test_from_csv_with_options()
    {
        $options = [
            'delimiter' => ';',
            'header' => true,
            'skip' => 1,
            'columns' => ['id', 'name', 'amount']
        ];
        
        $model = TestQueriesDataFilesModel::fromCsv('data/sales.csv', $options);
        
        $sql = $model->toSql();
        $this->assertStringContainsString("read_csv('data/sales.csv'", $sql);
        $this->assertStringContainsString("delimiter = ';'", $sql);
        $this->assertStringContainsString("header = TRUE", $sql);
        $this->assertStringContainsString("skip = 1", $sql);
        $this->assertStringContainsString("columns = ['id', 'name', 'amount']", $sql);
    }

    public function test_from_json_file_auto()
    {
        $model = TestQueriesDataFilesModel::fromJsonFile('data/sales.json');
        
        $this->assertStringContainsString("read_json_auto('data/sales.json')", $model->toSql());
    }

    public function test_from_json_file_with_options()
    {
        $options = [
            'format' => 'newline_delimited',
            'maximum_depth' => 10
        ];
        
        $model = TestQueriesDataFilesModel::fromJsonFile('data/sales.jsonl', $options);
        
        $sql = $model->toSql();
        $this->assertStringContainsString("read_json('data/sales.jsonl'", $sql);
        $this->assertStringContainsString("format = 'newline_delimited'", $sql);
        $this->assertStringContainsString("maximum_depth = '10'", $sql);
    }

    public function test_where_json_path()
    {
        $model = new TestQueriesDataFilesModel();
        $result = $model->whereJsonPath('metadata', 'status', '=', 'active');
        
        $this->assertStringContainsString("metadata->>'$.status' = ?", $result->toSql());
        $this->assertEquals(['active'], $result->getBindings());
    }

    public function test_where_json_contains_key()
    {
        $model = new TestQueriesDataFilesModel();
        $result = $model->whereJsonContainsKey('metadata', 'tags');
        
        $this->assertStringContainsString("json_contains(metadata, '$.\"tags\"')", $result->toSql());
    }

    public function test_select_json_extract_without_alias()
    {
        $model = new TestQueriesDataFilesModel();
        $result = $model->selectJsonExtract('data', 'user.name');
        
        $this->assertStringContainsString("json_extract(data, '$.user.name')", $result->toSql());
    }

    public function test_select_json_extract_with_alias()
    {
        $model = new TestQueriesDataFilesModel();
        $result = $model->selectJsonExtract('data', 'user.name', 'username');
        
        $sql = $result->toSql();
        $this->assertStringContainsString("json_extract(data, '$.user.name') AS username", $sql);
    }

    public function test_to_csv_without_options()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/data.csv' (FORMAT CSV)", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesDataFilesModel();
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $result = $model->toCsv('output/data.csv');
        
        $this->assertTrue($result);
    }

    public function test_to_csv_with_options()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/data.csv' (FORMAT CSV, header TRUE, delimiter ';')", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesDataFilesModel();
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $options = ['header' => true, 'delimiter' => ';'];
        $result = $model->toCsv('output/data.csv', $options);
        
        $this->assertTrue($result);
    }

    public function test_to_json_file_without_options()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/data.json' (FORMAT JSON)", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesDataFilesModel();
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $result = $model->toJsonFile('output/data.json');
        
        $this->assertTrue($result);
    }

    public function test_from_excel_without_options()
    {
        $model = TestQueriesDataFilesModel::fromExcel('data/sales.xlsx');
        
        $this->assertStringContainsString("read_excel('data/sales.xlsx')", $model->toSql());
    }

    public function test_from_excel_with_sheet()
    {
        $model = TestQueriesDataFilesModel::fromExcel('data/sales.xlsx', 'Sales2024');
        
        $sql = $model->toSql();
        $this->assertStringContainsString("read_excel('data/sales.xlsx'", $sql);
        $this->assertStringContainsString("sheet = 'Sales2024'", $sql);
    }

    public function test_from_excel_with_options()
    {
        $options = ['header' => true, 'range' => 'A1:D100'];
        $model = TestQueriesDataFilesModel::fromExcel('data/sales.xlsx', 'Sheet1', $options);
        
        $sql = $model->toSql();
        $this->assertStringContainsString("read_excel('data/sales.xlsx'", $sql);
        $this->assertStringContainsString("sheet = 'Sheet1'", $sql);
        $this->assertStringContainsString("header = TRUE", $sql);
        $this->assertStringContainsString("range = 'A1:D100'", $sql);
    }

    public function test_from_google_sheets_without_options()
    {
        $url = 'https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/export?format=csv';
        $model = TestQueriesDataFilesModel::fromGoogleSheets($url);
        
        $this->assertStringContainsString("read_csv_auto('$url')", $model->toSql());
    }

    public function test_from_google_sheets_with_options()
    {
        $url = 'https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/export?format=csv';
        $options = ['delimiter' => ',', 'header' => 'true'];
        $model = TestQueriesDataFilesModel::fromGoogleSheets($url, $options);
        
        $sql = $model->toSql();
        $this->assertStringContainsString("read_csv_auto('$url'", $sql);
        $this->assertStringContainsString("delimiter = ,", $sql);
        $this->assertStringContainsString("header = true", $sql);
    }

    public function test_from_http_csv_auto_detection()
    {
        $duckdb = m::mock();
        $duckdb->shouldReceive('query')
            ->with('INSTALL httpfs; LOAD httpfs;')
            ->once();

        $connection = m::mock();
        $connection->shouldReceive('getDuckDB')->andReturn($duckdb);

        $model = new TestQueriesDataFilesModel();
        $model->setConnection($connection);

        $url = 'https://example.com/data.csv';
        $result = $model::fromHttp($url);
        
        $this->assertStringContainsString("read_csv_auto('$url')", $result->toSql());
    }

    public function test_from_http_json_explicit()
    {
        $duckdb = m::mock();
        $duckdb->shouldReceive('query')
            ->with('INSTALL httpfs; LOAD httpfs;')
            ->once();

        $connection = m::mock();
        $connection->shouldReceive('getDuckDB')->andReturn($duckdb);

        $model = new TestQueriesDataFilesModel();
        $model->setConnection($connection);

        $url = 'https://example.com/data.json';
        $result = $model::fromHttp($url, 'json');
        
        $this->assertStringContainsString("read_json_auto('$url')", $result->toSql());
    }

    public function test_from_http_parquet_explicit()
    {
        $duckdb = m::mock();
        $duckdb->shouldReceive('query')
            ->with('INSTALL httpfs; LOAD httpfs;')
            ->once();

        $connection = m::mock();
        $connection->shouldReceive('getDuckDB')->andReturn($duckdb);

        $model = new TestQueriesDataFilesModel();
        $model->setConnection($connection);

        $url = 'https://example.com/data.parquet';
        $result = $model::fromHttp($url, 'parquet');
        
        $this->assertStringContainsString("'$url'", $result->toSql());
    }

    public function test_read_csv_metadata()
    {
        $connection = m::mock();
        $connection->shouldReceive('select')
            ->with("SELECT * FROM csv_metadata('data/test.csv')")
            ->once()
            ->andReturn([
                (object) ['filename' => 'test.csv', 'rows' => 1000, 'columns' => 5]
            ]);

        $model = new TestQueriesDataFilesModel();
        $model->setConnection($connection);

        $result = $model::readCsvMetadata('data/test.csv');
        
        $this->assertEquals('test.csv', $result->filename);
        $this->assertEquals(1000, $result->rows);
        $this->assertEquals(5, $result->columns);
    }

    public function test_sniff_csv_schema_default_sample_size()
    {
        $connection = m::mock();
        $connection->shouldReceive('select')
            ->with("SELECT * FROM sniff_csv('data/test.csv', sample_size = 1000)")
            ->once()
            ->andReturn([
                (object) ['column_name' => 'id', 'column_type' => 'BIGINT', 'null_percentage' => 0.0]
            ]);

        $model = new TestQueriesDataFilesModel();
        $model->setConnection($connection);

        $result = $model::sniffCsvSchema('data/test.csv');
        
        $this->assertEquals('id', $result->column_name);
        $this->assertEquals('BIGINT', $result->column_type);
    }

    public function test_sniff_csv_schema_custom_sample_size()
    {
        $connection = m::mock();
        $connection->shouldReceive('select')
            ->with("SELECT * FROM sniff_csv('data/test.csv', sample_size = 5000)")
            ->once()
            ->andReturn([
                (object) ['column_name' => 'id', 'column_type' => 'BIGINT', 'null_percentage' => 0.0]
            ]);

        $model = new TestQueriesDataFilesModel();
        $model->setConnection($connection);

        $result = $model::sniffCsvSchema('data/test.csv', 5000);
        
        $this->assertEquals('id', $result->column_name);
    }

    public function test_export_json_lines()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/data.jsonl' (FORMAT JSON, array TRUE)", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesDataFilesModel();
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $result = $model->exportJsonLines('output/data.jsonl');
        
        $this->assertTrue($result);
    }

    public function test_from_json_lines()
    {
        $model = TestQueriesDataFilesModel::fromJsonLines('data/sales.jsonl');
        
        $sql = $model->toSql();
        $this->assertStringContainsString("read_json('data/sales.jsonl'", $sql);
        $this->assertStringContainsString("format = 'newline_delimited'", $sql);
    }

    public function test_from_tsv()
    {
        $model = TestQueriesDataFilesModel::fromTsv('data/sales.tsv');
        
        $sql = $model->toSql();
        $this->assertStringContainsString("read_csv('data/sales.tsv'", $sql);
        $this->assertStringContainsString("delimiter = '\\t'", $sql);
    }

    public function test_from_tsv_with_additional_options()
    {
        $options = ['header' => true, 'quote' => '"'];
        $model = TestQueriesDataFilesModel::fromTsv('data/sales.tsv', $options);
        
        $sql = $model->toSql();
        $this->assertStringContainsString("read_csv('data/sales.tsv'", $sql);
        $this->assertStringContainsString("delimiter = '\\t'", $sql);
        $this->assertStringContainsString("header = TRUE", $sql);
        $this->assertStringContainsString("quote = '\"'", $sql);
    }

    public function test_to_tsv()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/data.tsv' (FORMAT CSV, delimiter '\\t')", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesDataFilesModel();
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $result = $model->toTsv('output/data.tsv');
        
        $this->assertTrue($result);
    }

    public function test_to_tsv_with_additional_options()
    {
        $connection = m::mock();
        $connection->shouldReceive('statement')
            ->with("COPY (SELECT * FROM test_table) TO 'output/data.tsv' (FORMAT CSV, delimiter '\\t', header TRUE)", [])
            ->once()
            ->andReturn(true);

        $model = new TestQueriesDataFilesModel();
        $model = m::mock($model)->makePartial();
        $model->shouldReceive('toSql')->andReturn('SELECT * FROM test_table');
        $model->shouldReceive('getBindings')->andReturn([]);
        $model->shouldReceive('getConnection')->andReturn($connection);

        $options = ['header' => true];
        $result = $model->toTsv('output/data.tsv', $options);
        
        $this->assertTrue($result);
    }
}