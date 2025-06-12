<?php

namespace Laraduck\EloquentDuckDB\Tests\Unit;

use Laraduck\EloquentDuckDB\Database\DuckDBPDOWrapper;
use Laraduck\EloquentDuckDB\Database\DuckDBStatementWrapper;
use Laraduck\EloquentDuckDB\DuckDB\DuckDB;
use Laraduck\EloquentDuckDB\DuckDB\DuckDBCLI;
use Laraduck\EloquentDuckDB\Tests\TestCase;
use Mockery as m;
use PDO;
use PDOException;

class DuckDBPDOWrapperTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_constructor_sets_default_attributes()
    {
        $duckdb = m::mock(DuckDB::class);
        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $wrapper->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertEquals(PDO::FETCH_OBJ, $wrapper->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
        $this->assertEquals(PDO::CASE_NATURAL, $wrapper->getAttribute(PDO::ATTR_CASE));
        $this->assertEquals(PDO::NULL_NATURAL, $wrapper->getAttribute(PDO::ATTR_ORACLE_NULLS));
        $this->assertFalse($wrapper->getAttribute(PDO::ATTR_STRINGIFY_FETCHES));
    }

    public function test_prepare_success()
    {
        $preparedStatement = m::mock();
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('preparedStatement')
            ->with('SELECT * FROM users WHERE id = ?')
            ->once()
            ->andReturn($preparedStatement);

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $result = $wrapper->prepare('SELECT * FROM users WHERE id = ?');
        
        $this->assertInstanceOf(DuckDBStatementWrapper::class, $result);
    }

    public function test_prepare_throws_pdo_exception_on_error()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('preparedStatement')
            ->with('INVALID SQL')
            ->once()
            ->andThrow(new \Exception('SQL syntax error', 42000));

        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('SQL syntax error');
        $this->expectExceptionCode(42000);
        
        $wrapper->prepare('INVALID SQL');
    }

    public function test_query_success()
    {
        $result = m::mock();
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('SELECT * FROM users')
            ->once()
            ->andReturn($result);

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $statement = $wrapper->query('SELECT * FROM users');
        
        $this->assertInstanceOf(DuckDBStatementWrapper::class, $statement);
    }

    public function test_query_throws_pdo_exception_on_error()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('INVALID SQL')
            ->once()
            ->andThrow(new \Exception('Table does not exist', 25));

        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Table does not exist');
        $this->expectExceptionCode(25);
        
        $wrapper->query('INVALID SQL');
    }

    public function test_exec_success()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('CREATE TABLE test (id INTEGER)')
            ->once()
            ->andReturn(null);

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $result = $wrapper->exec('CREATE TABLE test (id INTEGER)');
        
        $this->assertEquals(0, $result);
    }

    public function test_exec_throws_pdo_exception_on_error()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('INVALID DDL')
            ->once()
            ->andThrow(new \Exception('Cannot create table', 23000));

        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Cannot create table');
        $this->expectExceptionCode(23000);
        
        $wrapper->exec('INVALID DDL');
    }

    public function test_last_insert_id_returns_zero()
    {
        $duckdb = m::mock(DuckDB::class);
        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->assertEquals('0', $wrapper->lastInsertId());
        $this->assertEquals('0', $wrapper->lastInsertId('sequence_name'));
    }

    public function test_begin_transaction_success()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('BEGIN TRANSACTION')
            ->once()
            ->andReturn(null);

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $result = $wrapper->beginTransaction();
        
        $this->assertTrue($result);
    }

    public function test_begin_transaction_failure()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('BEGIN TRANSACTION')
            ->once()
            ->andThrow(new \Exception('Transaction already started'));

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $result = $wrapper->beginTransaction();
        
        $this->assertFalse($result);
    }

    public function test_commit_success()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('COMMIT')
            ->once()
            ->andReturn(null);

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $result = $wrapper->commit();
        
        $this->assertTrue($result);
    }

    public function test_commit_failure()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('COMMIT')
            ->once()
            ->andThrow(new \Exception('No transaction to commit'));

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $result = $wrapper->commit();
        
        $this->assertFalse($result);
    }

    public function test_rollback_success()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('ROLLBACK')
            ->once()
            ->andReturn(null);

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $result = $wrapper->rollBack();
        
        $this->assertTrue($result);
    }

    public function test_rollback_failure()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('ROLLBACK')
            ->once()
            ->andThrow(new \Exception('No transaction to rollback'));

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $result = $wrapper->rollBack();
        
        $this->assertFalse($result);
    }

    public function test_in_transaction_returns_false()
    {
        $duckdb = m::mock(DuckDB::class);
        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->assertFalse($wrapper->inTransaction());
    }

    public function test_get_attribute_returns_correct_value()
    {
        $duckdb = m::mock(DuckDB::class);
        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $wrapper->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertNull($wrapper->getAttribute(999)); // Non-existent attribute
    }

    public function test_set_attribute_updates_value()
    {
        $duckdb = m::mock(DuckDB::class);
        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $result = $wrapper->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        $this->assertTrue($result);
        $this->assertEquals(PDO::FETCH_ASSOC, $wrapper->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    public function test_quote_string_value()
    {
        $duckdb = m::mock(DuckDB::class);
        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->assertEquals("'hello'", $wrapper->quote('hello'));
        $this->assertEquals("'O''Reilly'", $wrapper->quote("O'Reilly"));
        $this->assertEquals("''", $wrapper->quote(''));
    }

    public function test_quote_integer_value()
    {
        $duckdb = m::mock(DuckDB::class);
        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->assertEquals('42', $wrapper->quote('42', PDO::PARAM_INT));
        $this->assertEquals('42', $wrapper->quote(42, PDO::PARAM_INT));
        $this->assertEquals('-123', $wrapper->quote(-123, PDO::PARAM_INT));
    }

    public function test_get_duckdb_returns_instance()
    {
        $duckdb = m::mock(DuckDB::class);
        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->assertSame($duckdb, $wrapper->getDuckDB());
    }

    public function test_works_with_duckdb_cli()
    {
        $duckdbCli = m::mock(DuckDBCLI::class);
        $preparedStatement = m::mock();
        
        $duckdbCli->shouldReceive('preparedStatement')
            ->with('SELECT 1')
            ->once()
            ->andReturn($preparedStatement);

        $wrapper = new DuckDBPDOWrapper($duckdbCli);
        $result = $wrapper->prepare('SELECT 1');
        
        $this->assertInstanceOf(DuckDBStatementWrapper::class, $result);
        $this->assertSame($duckdbCli, $wrapper->getDuckDB());
    }

    public function test_prepare_with_options_parameter()
    {
        $preparedStatement = m::mock();
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('preparedStatement')
            ->with('SELECT * FROM users WHERE id = ?')
            ->once()
            ->andReturn($preparedStatement);

        $wrapper = new DuckDBPDOWrapper($duckdb);
        $options = [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL];
        $result = $wrapper->prepare('SELECT * FROM users WHERE id = ?', $options);
        
        $this->assertInstanceOf(DuckDBStatementWrapper::class, $result);
    }

    public function test_quote_special_characters()
    {
        $duckdb = m::mock(DuckDB::class);
        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        $this->assertEquals("'test''quote'", $wrapper->quote("test'quote"));
        $this->assertEquals("'multiple''single''quotes'", $wrapper->quote("multiple'single'quotes"));
        $this->assertEquals("'newline\ncharacter'", $wrapper->quote("newline\ncharacter"));
        $this->assertEquals("'tab\tcharacter'", $wrapper->quote("tab\tcharacter"));
    }

    public function test_exception_code_conversion()
    {
        $duckdb = m::mock(DuckDB::class);
        $duckdb->shouldReceive('query')
            ->with('INVALID')
            ->once()
            ->andThrow(new \Exception('Error message', 99999));

        $wrapper = new DuckDBPDOWrapper($duckdb);
        
        try {
            $wrapper->query('INVALID');
            $this->fail('Expected PDOException was not thrown');
        } catch (PDOException $e) {
            $this->assertEquals('Error message', $e->getMessage());
            $this->assertEquals(99999, $e->getCode());
        }
    }
}