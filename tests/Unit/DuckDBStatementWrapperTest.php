<?php

namespace Laraduck\EloquentDuckDB\Tests\Unit;

use Laraduck\EloquentDuckDB\Database\DuckDBStatementWrapper;
use Laraduck\EloquentDuckDB\DuckDB\PreparedStatement;
use Laraduck\EloquentDuckDB\DuckDB\Result;
use Laraduck\EloquentDuckDB\DuckDB\CLIPreparedStatement;
use Laraduck\EloquentDuckDB\DuckDB\CLIResult;
use Laraduck\EloquentDuckDB\Tests\TestCase;
use Mockery as m;
use PDO;
use PDOException;

class DuckDBStatementWrapperTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_constructor_sets_attributes()
    {
        $statement = m::mock(PreparedStatement::class);
        $attributes = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
        
        $wrapper = new DuckDBStatementWrapper($statement, $attributes);
        
        $this->assertEquals(PDO::FETCH_ASSOC, $wrapper->setFetchMode(PDO::FETCH_ASSOC));
    }

    public function test_execute_with_prepared_statement_and_params()
    {
        $result = m::mock(Result::class);
        $statement = m::mock(PreparedStatement::class);
        
        $statement->shouldReceive('bindParam')
            ->with(1, 'test_value')
            ->once();
        $statement->shouldReceive('execute')
            ->once()
            ->andReturn($result);

        $wrapper = new DuckDBStatementWrapper($statement);
        $success = $wrapper->execute(['test_value']);
        
        $this->assertTrue($success);
    }

    public function test_execute_with_cli_prepared_statement()
    {
        $result = m::mock(CLIResult::class);
        $statement = m::mock(CLIPreparedStatement::class);
        
        $statement->shouldReceive('bindParam')
            ->with(1, 'test_value')
            ->once();
        $statement->shouldReceive('execute')
            ->once()
            ->andReturn($result);

        $wrapper = new DuckDBStatementWrapper($statement);
        $success = $wrapper->execute(['test_value']);
        
        $this->assertTrue($success);
    }

    public function test_execute_with_direct_result()
    {
        $result = m::mock(Result::class);
        
        $wrapper = new DuckDBStatementWrapper($result);
        $success = $wrapper->execute();
        
        $this->assertTrue($success);
    }

    public function test_execute_throws_pdo_exception_on_error()
    {
        $statement = m::mock(PreparedStatement::class);
        $statement->shouldReceive('bindParam')->andThrow(new \Exception('Binding failed', 1001));
        
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Binding failed');
        $this->expectExceptionCode(1001);
        
        $wrapper->execute(['value']);
    }

    public function test_bind_value_stores_binding()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $result = $wrapper->bindValue(1, 'test_value', PDO::PARAM_STR);
        
        $this->assertTrue($result);
    }

    public function test_bind_param_stores_reference()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $value = 'test_value';
        $result = $wrapper->bindParam(1, $value, PDO::PARAM_STR);
        
        $this->assertTrue($result);
    }

    public function test_fetch_returns_false_without_result()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $this->assertFalse($wrapper->fetch());
    }

    public function test_fetch_with_result_fetch_obj_mode()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['id' => '1', 'name' => 'John', 'age' => '25']);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ]);
        
        // Simulate execute to set result
        $wrapper = m::mock($wrapper)->makePartial();
        $wrapper->shouldReceive('result')->andReturn($result);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $row = $wrapper->fetch();
        
        $this->assertIsObject($row);
        $this->assertEquals(1, $row->id);
        $this->assertEquals('John', $row->name);
        $this->assertEquals(25, $row->age);
    }

    public function test_fetch_with_result_fetch_assoc_mode()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['id' => '1', 'name' => 'John', 'active' => 'TRUE']);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $row = $wrapper->fetch(PDO::FETCH_ASSOC);
        
        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('John', $row['name']);
    }

    public function test_fetch_with_result_fetch_num_mode()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['id' => '1', 'name' => 'John', 'score' => '95.5']);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $row = $wrapper->fetch(PDO::FETCH_NUM);
        
        $this->assertIsArray($row);
        $this->assertEquals(1, $row[0]);
        $this->assertEquals('John', $row[1]);
        $this->assertEquals(95.5, $row[2]);
    }

    public function test_fetch_handles_null_values()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['id' => '1', 'name' => 'NULL', 'score' => '95.5']);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $row = $wrapper->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals(1, $row['id']);
        $this->assertNull($row['name']); // 'NULL' string should become null
        $this->assertEquals(95.5, $row['score']);
    }

    public function test_fetch_all_returns_empty_array_without_result()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $this->assertEquals([], $wrapper->fetchAll());
    }

    public function test_fetch_all_with_multiple_rows()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->times(3)
            ->andReturn(
                ['id' => '1', 'name' => 'John'],
                ['id' => '2', 'name' => 'Jane'],
                false
            );
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $rows = $wrapper->fetchAll(PDO::FETCH_ASSOC);
        
        $this->assertCount(2, $rows);
        $this->assertEquals(1, $rows[0]['id']);
        $this->assertEquals('John', $rows[0]['name']);
        $this->assertEquals(2, $rows[1]['id']);
        $this->assertEquals('Jane', $rows[1]['name']);
    }

    public function test_fetch_column_returns_false_without_result()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $this->assertFalse($wrapper->fetchColumn());
    }

    public function test_fetch_column_returns_correct_column()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['1', 'John', '25']);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $this->assertEquals(1, $wrapper->fetchColumn(0));
    }

    public function test_fetch_column_with_different_column_index()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['1', 'John', '25']);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $this->assertEquals('John', $wrapper->fetchColumn(1));
        
        // Reset result for next fetch
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['1', 'John', '25']);
        $resultProperty->setValue($wrapper, $result);
        
        $this->assertEquals(25, $wrapper->fetchColumn(2));
    }

    public function test_fetch_column_handles_null_values()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['1', 'NULL', '25']);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $this->assertNull($wrapper->fetchColumn(1));
    }

    public function test_fetch_column_handles_numeric_values()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['-42', '3.14159', 'text']);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $this->assertEquals(-42, $wrapper->fetchColumn(0));
        
        // Reset for next column
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['-42', '3.14159', 'text']);
        $resultProperty->setValue($wrapper, $result);
        
        $this->assertEquals(3.14159, $wrapper->fetchColumn(1));
        
        // Reset for text column
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['-42', '3.14159', 'text']);
        $resultProperty->setValue($wrapper, $result);
        
        $this->assertEquals('text', $wrapper->fetchColumn(2));
    }

    public function test_row_count_without_result()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $this->assertEquals(0, $wrapper->rowCount());
    }

    public function test_row_count_with_result()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('rowCount')
            ->once()
            ->andReturn(5);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $this->assertEquals(5, $wrapper->rowCount());
    }

    public function test_column_count_without_result()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $this->assertEquals(0, $wrapper->columnCount());
    }

    public function test_column_count_with_result()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('columnCount')
            ->once()
            ->andReturn(3);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $this->assertEquals(3, $wrapper->columnCount());
    }

    public function test_set_fetch_mode()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $result = $wrapper->setFetchMode(PDO::FETCH_ASSOC);
        
        $this->assertTrue($result);
    }

    public function test_set_fetch_mode_with_args()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $result = $wrapper->setFetchMode(PDO::FETCH_CLASS, 'stdClass', ['arg1']);
        
        $this->assertTrue($result);
    }

    public function test_close_cursor()
    {
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        $result = $wrapper->closeCursor();
        
        $this->assertTrue($result);
    }

    public function test_fetch_with_both_mode()
    {
        $result = m::mock(Result::class);
        $result->shouldReceive('fetchRow')
            ->once()
            ->andReturn(['id' => '1', 'name' => 'John']);
        
        $statement = m::mock(PreparedStatement::class);
        $wrapper = new DuckDBStatementWrapper($statement);
        
        // Use reflection to set the result property
        $reflection = new \ReflectionClass($wrapper);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($wrapper, $result);
        
        $row = $wrapper->fetch(PDO::FETCH_BOTH);
        
        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('John', $row['name']);
        $this->assertEquals(1, $row[0]);
        $this->assertEquals('John', $row[1]);
    }
}