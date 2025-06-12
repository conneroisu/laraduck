<?php

namespace Laraduck\EloquentDuckDB\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Laraduck\EloquentDuckDB\Database\DuckDBConnection;
use Laraduck\EloquentDuckDB\Tests\TestCase;

class DuckDBConnectionTest extends TestCase
{
    public function test_connection_can_be_established()
    {
        $connection = DB::connection('duckdb');
        
        $this->assertInstanceOf(DuckDBConnection::class, $connection);
    }
    
    public function test_can_create_and_query_table()
    {
        DB::statement('CREATE TABLE users (id INTEGER, name VARCHAR, email VARCHAR)');
        
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ]);
        
        $users = DB::table('users')->get();
        
        $this->assertCount(2, $users);
        $this->assertEquals('John Doe', $users[0]->name);
    }
    
    public function test_can_use_window_functions()
    {
        DB::statement('CREATE TABLE sales (id INTEGER, department VARCHAR, amount DECIMAL)');
        
        DB::table('sales')->insert([
            ['id' => 1, 'department' => 'IT', 'amount' => 1000],
            ['id' => 2, 'department' => 'IT', 'amount' => 1500],
            ['id' => 3, 'department' => 'HR', 'amount' => 800],
            ['id' => 4, 'department' => 'HR', 'amount' => 1200],
        ]);
        
        $results = DB::table('sales')
            ->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY department ORDER BY amount DESC) as rank')
            ->get();
        
        $this->assertCount(4, $results);
        $this->assertEquals(1, $results->firstWhere('id', 2)->rank);
    }
    
    public function test_can_use_cte()
    {
        DB::statement('CREATE TABLE employees (id INTEGER, name VARCHAR, manager_id INTEGER)');
        
        DB::table('employees')->insert([
            ['id' => 1, 'name' => 'CEO', 'manager_id' => null],
            ['id' => 2, 'name' => 'VP', 'manager_id' => 1],
            ['id' => 3, 'name' => 'Manager', 'manager_id' => 2],
            ['id' => 4, 'name' => 'Employee', 'manager_id' => 3],
        ]);
        
        $query = DB::table('employees')
            ->where('manager_id', 1)
            ->select('id', 'name');
        
        $results = DB::query()
            ->withCte('managers', $query)
            ->from('managers')
            ->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('VP', $results[0]->name);
    }
    
    public function test_can_use_sample()
    {
        DB::statement('CREATE TABLE large_table (id INTEGER)');
        
        $data = [];
        for ($i = 1; $i <= 1000; $i++) {
            $data[] = ['id' => $i];
        }
        
        DB::table('large_table')->insert($data);
        
        $sample = DB::query()
            ->from('large_table')
            ->sample(0.1)
            ->get();
        
        $this->assertGreaterThan(50, $sample->count());
        $this->assertLessThan(150, $sample->count());
    }
    
    public function test_can_group_by_all()
    {
        DB::statement('CREATE TABLE orders (product VARCHAR, category VARCHAR, amount DECIMAL)');
        
        DB::table('orders')->insert([
            ['product' => 'A', 'category' => 'Electronics', 'amount' => 100],
            ['product' => 'B', 'category' => 'Electronics', 'amount' => 200],
            ['product' => 'C', 'category' => 'Books', 'amount' => 50],
        ]);
        
        $results = DB::table('orders')
            ->selectRaw('category, SUM(amount) as total')
            ->groupByAll()
            ->get();
        
        $this->assertCount(2, $results);
        $this->assertEquals(300, $results->firstWhere('category', 'Electronics')->total);
    }
}