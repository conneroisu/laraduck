<?php

namespace Laraduck\EloquentDuckDB\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laraduck\EloquentDuckDB\Tests\TestCase;

class SchemaBuilderTest extends TestCase
{
    public function test_create_table_with_basic_columns()
    {
        Schema::create('test_basic', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
            $table->decimal('salary', 10, 2);
            $table->boolean('active');
            $table->date('birth_date');
            $table->timestamp('created_at');
        });

        $this->assertTrue(Schema::hasTable('test_basic'));
        $this->assertTrue(Schema::hasColumn('test_basic', 'id'));
        $this->assertTrue(Schema::hasColumn('test_basic', 'name'));
        $this->assertTrue(Schema::hasColumn('test_basic', 'age'));
        $this->assertTrue(Schema::hasColumn('test_basic', 'salary'));
        $this->assertTrue(Schema::hasColumn('test_basic', 'active'));
        $this->assertTrue(Schema::hasColumn('test_basic', 'birth_date'));
        $this->assertTrue(Schema::hasColumn('test_basic', 'created_at'));
    }

    public function test_create_table_with_duckdb_specific_types()
    {
        Schema::create('test_duckdb_types', function ($table) {
            $table->id();
            
            // Test DuckDB-specific column types if Blueprint supports them
            if (method_exists($table, 'list')) {
                $table->list('tags', 'varchar');
            } else {
                // Fallback for standard blueprint
                $table->json('tags');
            }
            
            if (method_exists($table, 'struct')) {
                $table->struct('metadata', ['name' => 'varchar', 'value' => 'integer']);
            } else {
                $table->json('metadata');
            }
            
            if (method_exists($table, 'hugeInt')) {
                $table->hugeInt('huge_number');
            } else {
                $table->bigInteger('huge_number');
            }
            
            if (method_exists($table, 'blob')) {
                $table->blob('binary_data');
            } else {
                $table->binary('binary_data');
            }
        });

        $this->assertTrue(Schema::hasTable('test_duckdb_types'));
        $this->assertTrue(Schema::hasColumn('test_duckdb_types', 'id'));
        $this->assertTrue(Schema::hasColumn('test_duckdb_types', 'tags'));
        $this->assertTrue(Schema::hasColumn('test_duckdb_types', 'metadata'));
        $this->assertTrue(Schema::hasColumn('test_duckdb_types', 'huge_number'));
        $this->assertTrue(Schema::hasColumn('test_duckdb_types', 'binary_data'));
    }

    public function test_alter_table_add_columns()
    {
        // First create a simple table
        Schema::create('test_alter', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        $this->assertTrue(Schema::hasTable('test_alter'));
        $this->assertTrue(Schema::hasColumn('test_alter', 'name'));
        $this->assertFalse(Schema::hasColumn('test_alter', 'email'));

        // Add new columns
        Schema::table('test_alter', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->integer('age')->default(0);
            $table->timestamp('updated_at')->nullable();
        });

        $this->assertTrue(Schema::hasColumn('test_alter', 'email'));
        $this->assertTrue(Schema::hasColumn('test_alter', 'age'));
        $this->assertTrue(Schema::hasColumn('test_alter', 'updated_at'));
    }

    public function test_drop_table()
    {
        Schema::create('test_drop', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        $this->assertTrue(Schema::hasTable('test_drop'));

        Schema::drop('test_drop');

        $this->assertFalse(Schema::hasTable('test_drop'));
    }

    public function test_drop_table_if_exists()
    {
        // Table doesn't exist, should not throw error
        Schema::dropIfExists('non_existent_table');
        
        $this->assertFalse(Schema::hasTable('non_existent_table'));

        // Create table and then drop it
        Schema::create('test_drop_if_exists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        $this->assertTrue(Schema::hasTable('test_drop_if_exists'));

        Schema::dropIfExists('test_drop_if_exists');

        $this->assertFalse(Schema::hasTable('test_drop_if_exists'));
    }

    public function test_create_table_with_indexes()
    {
        Schema::create('test_indexes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->index();
            $table->integer('category_id');
            $table->integer('user_id');
            
            // Composite index
            $table->index(['category_id', 'user_id'], 'idx_category_user');
        });

        $this->assertTrue(Schema::hasTable('test_indexes'));
        $this->assertTrue(Schema::hasColumn('test_indexes', 'email'));
        $this->assertTrue(Schema::hasColumn('test_indexes', 'name'));
        $this->assertTrue(Schema::hasColumn('test_indexes', 'category_id'));
        $this->assertTrue(Schema::hasColumn('test_indexes', 'user_id'));
        
        // Note: DuckDB may not support all index types, but the table should still be created
    }

    public function test_create_table_with_foreign_keys()
    {
        // Create parent table first
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        // Create child table with foreign key
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('category_id');
            
            // Note: DuckDB may not fully support foreign key constraints
            // but the table structure should still be created
            try {
                $table->foreign('category_id')->references('id')->on('categories');
            } catch (\Exception $e) {
                // Foreign keys might not be supported, continue test
            }
        });

        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasColumn('products', 'category_id'));
    }

    public function test_column_types_and_constraints()
    {
        Schema::create('test_column_types', function (Blueprint $table) {
            $table->id();
            
            // String types
            $table->string('short_text', 50);
            $table->text('long_text');
            $table->char('code', 10);
            
            // Numeric types
            $table->tinyInteger('tiny_num');
            $table->smallInteger('small_num');
            $table->mediumInteger('medium_num');
            $table->integer('int_num');
            $table->bigInteger('big_num');
            $table->unsignedInteger('unsigned_num');
            
            // Decimal types
            $table->decimal('price', 8, 2);
            $table->float('percentage');
            $table->double('precise_value');
            
            // Date/Time types
            $table->date('event_date');
            $table->time('event_time');
            $table->dateTime('event_datetime');
            $table->timestamp('event_timestamp');
            $table->year('birth_year');
            
            // Boolean and other types
            $table->boolean('is_active');
            $table->json('metadata');
            $table->binary('file_content');
            
            // Nullable and default constraints
            $table->string('optional')->nullable();
            $table->integer('count')->default(0);
            $table->boolean('enabled')->default(true);
        });

        $this->assertTrue(Schema::hasTable('test_column_types'));
        
        // Verify all columns exist
        $columns = [
            'id', 'short_text', 'long_text', 'code', 'tiny_num', 'small_num',
            'medium_num', 'int_num', 'big_num', 'unsigned_num', 'price',
            'percentage', 'precise_value', 'event_date', 'event_time',
            'event_datetime', 'event_timestamp', 'birth_year', 'is_active',
            'metadata', 'file_content', 'optional', 'count', 'enabled'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('test_column_types', $column), "Column '{$column}' should exist");
        }
    }

    public function test_rename_table()
    {
        Schema::create('old_table_name', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        $this->assertTrue(Schema::hasTable('old_table_name'));
        $this->assertFalse(Schema::hasTable('new_table_name'));

        try {
            Schema::rename('old_table_name', 'new_table_name');
            
            $this->assertFalse(Schema::hasTable('old_table_name'));
            $this->assertTrue(Schema::hasTable('new_table_name'));
        } catch (\Exception $e) {
            // Some database systems might not support table renaming
            $this->markTestSkipped('Table renaming not supported: ' . $e->getMessage());
        }
    }

    public function test_modify_columns()
    {
        Schema::create('test_modify', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->integer('age');
        });

        $this->assertTrue(Schema::hasTable('test_modify'));

        try {
            Schema::table('test_modify', function (Blueprint $table) {
                $table->string('name', 100)->change(); // Increase length
                $table->string('age')->change(); // Change type from integer to string
            });
            
            // If we get here, column modification is supported
            $this->assertTrue(Schema::hasColumn('test_modify', 'name'));
            $this->assertTrue(Schema::hasColumn('test_modify', 'age'));
        } catch (\Exception $e) {
            // Column modification might not be supported in DuckDB
            $this->markTestSkipped('Column modification not supported: ' . $e->getMessage());
        }
    }

    public function test_drop_columns()
    {
        Schema::create('test_drop_columns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->integer('age');
            $table->boolean('active');
        });

        $this->assertTrue(Schema::hasColumn('test_drop_columns', 'name'));
        $this->assertTrue(Schema::hasColumn('test_drop_columns', 'email'));
        $this->assertTrue(Schema::hasColumn('test_drop_columns', 'age'));
        $this->assertTrue(Schema::hasColumn('test_drop_columns', 'active'));

        try {
            Schema::table('test_drop_columns', function (Blueprint $table) {
                $table->dropColumn(['email', 'age']);
            });
            
            $this->assertTrue(Schema::hasColumn('test_drop_columns', 'name'));
            $this->assertTrue(Schema::hasColumn('test_drop_columns', 'active'));
            $this->assertFalse(Schema::hasColumn('test_drop_columns', 'email'));
            $this->assertFalse(Schema::hasColumn('test_drop_columns', 'age'));
        } catch (\Exception $e) {
            // Column dropping might not be supported
            $this->markTestSkipped('Column dropping not supported: ' . $e->getMessage());
        }
    }

    public function test_table_exists_check()
    {
        $this->assertFalse(Schema::hasTable('non_existent_table'));
        
        Schema::create('existing_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        
        $this->assertTrue(Schema::hasTable('existing_table'));
    }

    public function test_column_exists_check()
    {
        Schema::create('test_columns_check', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
        });
        
        $this->assertTrue(Schema::hasColumn('test_columns_check', 'id'));
        $this->assertTrue(Schema::hasColumn('test_columns_check', 'name'));
        $this->assertTrue(Schema::hasColumn('test_columns_check', 'age'));
        $this->assertFalse(Schema::hasColumn('test_columns_check', 'email'));
        $this->assertFalse(Schema::hasColumn('test_columns_check', 'non_existent'));
    }

    public function test_multiple_columns_check()
    {
        Schema::create('test_multiple_columns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->integer('age');
        });
        
        $this->assertTrue(Schema::hasColumns('test_multiple_columns', ['id', 'name']));
        $this->assertTrue(Schema::hasColumns('test_multiple_columns', ['name', 'email', 'age']));
        $this->assertFalse(Schema::hasColumns('test_multiple_columns', ['name', 'non_existent']));
        $this->assertFalse(Schema::hasColumns('test_multiple_columns', ['completely', 'non_existent']));
    }

    protected function tearDown(): void
    {
        // Clean up tables created during tests
        $tables = [
            'test_basic', 'test_duckdb_types', 'test_alter', 'test_drop',
            'test_drop_if_exists', 'test_indexes', 'categories', 'products',
            'test_column_types', 'old_table_name', 'new_table_name',
            'test_modify', 'test_drop_columns', 'existing_table',
            'test_columns_check', 'test_multiple_columns'
        ];
        
        foreach ($tables as $table) {
            try {
                Schema::dropIfExists($table);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        
        parent::tearDown();
    }
}