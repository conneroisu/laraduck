# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

Laraduck is a DuckDB driver for Laravel's Eloquent ORM that brings blazing-fast analytical queries to Laravel applications. It enables querying Parquet, CSV, and JSON files directly without importing, while maintaining full Laravel/Eloquent compatibility.

## Project Structure

### Source Code (`src/`)
- `DuckDBServiceProvider.php` - Laravel service provider for DuckDB integration
- `Config/duckdb.php` - Configuration file template
- `Connectors/DuckDBConnector.php` - Database connector implementation
- `Database/` - Core database layer (Connection, PDO wrapper, Statement wrapper)
- `DuckDB/` - DuckDB integration classes (DuckDB, DuckDBCLI)
- `Eloquent/` - Enhanced Eloquent models and builder with analytical features
- `Query/` - Custom query builder with DuckDB-specific SQL generation
- `Schema/` - Schema builder for DuckDB table operations

### Testing (`tests/`)
- `Unit/` - Component-level tests (grammar, query building)
- `Feature/` - Integration tests (model operations, connections)
- `TestCase.php` - Base test case with DuckDB setup

### Examples (`examples/`)
- `analytics-dashboard-react/` - Full React dashboard with Inertia.js
- `ecommerce-analytics/` - E-commerce analytics example with Vue components

### Documentation (`docs/`, `doc/`)
- Astro-based documentation sites with guides, examples, and API reference

## Requirements

### System Requirements
- **PHP**: ^8.3
- **Laravel**: ^10.0 or ^11.0
- **Extensions**: `ext-ffi`, `ext-bcmath`
- **DuckDB**: Installed system-wide or via package manager

### Development Dependencies
- **PHPUnit**: ^10.0 for testing
- **Orchestra Testbench**: ^8.0|^9.0 for Laravel testing
- **Mockery**: ^1.6 for mocking

### Optional Dependencies
- **Nix**: For reproducible development environment
- **Node.js**: For documentation building and example frontend components

## Common Commands

### Development
```bash
# Install dependencies
make install
composer install

# Run tests
make test
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Feature

# Run specific test class
./vendor/bin/phpunit tests/Unit/DuckDBGrammarTest.php
./vendor/bin/phpunit tests/Feature/AnalyticalModelTest.php

# Run linting (PHP syntax check)
make lint

# Verify installation and setup
php verify.php

# Format code (if in Nix environment)
nix fmt
```

### Benchmarking
```bash
# Run all benchmarks
make benchmark
composer run benchmark

# Performance benchmarks only
make benchmark-performance
composer run benchmark:performance

# Database comparison benchmarks
make benchmark-comparison
composer run benchmark:comparison

# Clean benchmark artifacts
make clean
```

### Development Environment (Nix)
```bash
# Enter development shell with DuckDB and development tools
nix develop

# Format all code (formats PHP, Nix, JS, CSS, MD, JSON)
nix fmt

# Development tools available in Nix shell:
# - duckdb: DuckDB CLI for direct database interaction
# - alejandra: Nix formatter
# - prettierd: Multi-language formatter
# - dx: Quick script to edit flake.nix
```

## Architecture

### Core Components

**Service Provider** (`src/DuckDBServiceProvider.php`):
- Registers DuckDB database driver with Laravel
- Extends Laravel's database manager
- Publishes configuration files

**Database Layer**:
- `DuckDBConnector` - Handles PDO connections to DuckDB
- `DuckDBConnection` - Laravel database connection wrapper
- `DuckDBPDOWrapper` - Custom PDO wrapper for DuckDB specifics
- `DuckDBStatementWrapper` - Statement execution wrapper

**Query System**:
- `Query/Grammars/DuckDBGrammar` - SQL generation for DuckDB dialect
- `Query/Builder` - Custom query builder with DuckDB features
- `Query/Processors/DuckDBProcessor` - Result processing

**Eloquent Integration**:
- `AnalyticalModel` - Base model class for analytical workloads
- Traits: `QueriesFiles`, `QueriesDataFiles`, `SupportsAdvancedQueries`
- Custom Eloquent `Builder` with DuckDB-specific methods

**Schema Building**:
- `Schema/Builder` - DuckDB schema operations
- `Schema/Blueprint` - Table definition with DuckDB column types
- `Schema/Grammars/DuckDBGrammar` - DDL SQL generation

### Key Features

**File Querying**: Direct querying of Parquet/CSV/JSON files without import using `QueriesFiles` and `QueriesDataFiles` traits.

**Advanced SQL Support**: Window functions, CTEs, QUALIFY clause, and other analytical SQL features through `SupportsAdvancedQueries` trait.

**Batch Operations**: Optimized bulk insert operations via `AnalyticalModel::insertBatch()` with configurable chunk sizes.

**Laravel Integration**: Full compatibility with Laravel's database layer, migrations, and Eloquent ORM.

### Configuration

DuckDB connections are configured in Laravel's `config/database.php`:
```php
'connections' => [
    'analytics' => [
        'driver' => 'duckdb',
        'database' => storage_path('analytics.duckdb'),
        'read_only' => false,
        'memory_limit' => '4GB',
    ],
],
```

### Testing Strategy

- **Unit Tests** (`tests/Unit/`): Test individual components like grammar generation, PDO wrappers, traits
- **Feature Tests** (`tests/Feature/`): Test integration scenarios like model operations, connections, file queries
- **Benchmarks** (`benchmarks/`): Performance testing and database comparisons with other databases
- **Test Environment**: Uses in-memory DuckDB (`:memory:`) for speed, configured in `phpunit.xml`
- **Test Base Class**: `TestCase` extends Orchestra Testbench with DuckDB service provider registration

### Package Information

- **Package Name**: `laraduck/eloquent-duckdb`
- **Root Namespace**: `Laraduck\EloquentDuckDB\`
- **Service Provider**: `Laraduck\EloquentDuckDB\DuckDBServiceProvider`
- **Auto-Discovery**: Service provider is automatically registered via Laravel package discovery

### Example Usage Patterns

**Basic Analytical Model**:
```php
use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;

class Sale extends AnalyticalModel
{
    protected $connection = 'analytics';
    use QueriesFiles, SupportsAdvancedQueries;
}
```

**File Querying**:
```php
$data = Sale::fromParquetFile('data/*.parquet')
    ->where('region', 'North America')
    ->sum('revenue');
```

**Batch Operations**:
```php
Sale::insertBatch($largeDataset, 10000); // Chunk size of 10k
```

### Development Workflow

#### Code Quality Assurance
Always run these commands before committing changes:
```bash
# Run tests to ensure functionality
make test

# Run linting to check syntax
make lint

# Format code if using Nix
nix fmt
```

#### Working with Examples
Each example in `examples/` has its own setup:
```bash
# For ecommerce-analytics example
cd examples/ecommerce-analytics
composer install
php setup.php
php verify-example.php

# For React dashboard example  
cd examples/analytics-dashboard-react
composer install && npm install
php verify-setup.php
```

#### Performance Testing
Use benchmarks to validate performance improvements:
```bash
# Run before/after performance comparison
make benchmark-performance

# Compare against other databases
make benchmark-comparison
```

## Debugging and Troubleshooting

### Common Issues

**DuckDB Connection Errors**:
- Verify DuckDB is installed: `duckdb --version`
- Check file permissions for database files
- Ensure `ext-ffi` extension is enabled: `php -m | grep ffi`

**Memory Issues with Large Datasets**:
- Increase DuckDB memory limit in config: `'memory_limit' => '8GB'`
- Use streaming queries for very large files
- Consider partitioning large Parquet files

**FFI Extension Missing**:
```bash
# Install FFI extension (Ubuntu/Debian)
sudo apt-get install php-ffi

# Enable in php.ini
extension=ffi
```

### Debugging SQL Generation
Enable query logging to debug generated SQL:
```php
// In your Laravel application
DB::connection('analytics')->enableQueryLog();
// Run your queries
dd(DB::connection('analytics')->getQueryLog());
```

### Testing in Isolation
Use in-memory databases for testing:
```php
// In tests, use :memory: database
'analytics' => [
    'driver' => 'duckdb',
    'database' => ':memory:',
]
```

### Performance Profiling
Monitor query performance:
```php
// Enable EXPLAIN for query analysis
$query = Sale::where('amount', '>', 1000);
$explained = DB::connection('analytics')
    ->select('EXPLAIN ANALYZE ' . $query->toSql(), $query->getBindings());
```

## Performance Considerations

### Optimizing File Queries
- **Use Parquet**: Generally 10-100x faster than CSV for analytical queries
- **Partition Files**: Split large datasets by date/category for better performance
- **Column Selection**: Use `select()` to limit columns read from files
- **Predicate Pushdown**: Apply `where()` clauses early to reduce data scanning

### Memory Management
- **Batch Size**: Tune `insertBatch()` chunk size based on available memory
- **Memory Limit**: Configure DuckDB memory limit appropriately
- **Connection Pooling**: Reuse connections for multiple operations

### Query Optimization
- **Window Functions**: Leverage DuckDB's optimized window function implementations
- **CTEs**: Use Common Table Expressions for complex analytical queries
- **Indexes**: Create indexes on frequently queried columns

### Example Performance Patterns
```php
// Efficient file querying with column selection and filtering
$revenue = Sale::fromParquetFile('sales/*.parquet')
    ->select('product_id', 'amount', 'sale_date')
    ->where('sale_date', '>=', '2024-01-01')
    ->where('amount', '>', 100)
    ->groupBy('product_id')
    ->sum('amount');

// Optimized batch insertions
$chunkSize = min(10000, (int)(memory_get_limit() / 1024 / 100));
Sale::insertBatch($largeDataset, $chunkSize);

// Using QUALIFY for efficient analytical queries
$topSellers = Sale::select('product_id', 'amount')
    ->qualify('ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY amount DESC) <= 5')
    ->get();
```

## Extended Usage Examples

### Time Series Analysis
```php
class MetricData extends AnalyticalModel
{
    use QueriesFiles, SupportsAdvancedQueries;
    
    public function dailyAggregates()
    {
        return $this->select([
            'DATE_TRUNC(\'day\', timestamp) as date',
            'AVG(value) as avg_value',
            'MAX(value) as max_value',
            'COUNT(*) as count'
        ])
        ->groupBy('date')
        ->orderBy('date');
    }
    
    public function movingAverage($window = 7)
    {
        return $this->select([
            'timestamp',
            'value',
            'AVG(value) OVER (ORDER BY timestamp ROWS BETWEEN ? PRECEDING AND CURRENT ROW) as moving_avg'
        ], [$window - 1]);
    }
}
```

### Complex Analytical Queries
```php
// Multi-table joins with window functions
$customerMetrics = Customer::select([
    'customers.id',
    'customers.name',
    'SUM(orders.total) as total_spent',
    'COUNT(orders.id) as order_count',
    'RANK() OVER (ORDER BY SUM(orders.total) DESC) as spending_rank'
])
->join('orders', 'customers.id', '=', 'orders.customer_id')
->groupBy('customers.id', 'customers.name')
->qualify('spending_rank <= 100')
->get();

// File-based reporting with exports
$monthlyReport = Sale::fromParquetFile('sales/2024-*.parquet')
    ->select([
        'DATE_TRUNC(\'month\', sale_date) as month',
        'SUM(amount) as revenue',
        'COUNT(DISTINCT customer_id) as unique_customers'
    ])
    ->groupBy('month')
    ->orderBy('month');

// Export results to Parquet for further analysis
$monthlyReport->toParquetFile('reports/monthly_summary.parquet');
```

### Data Pipeline Integration
```php
// ETL pipeline example
class DataPipeline
{
    public function processRawData($inputPath, $outputPath)
    {
        // Extract and transform in one query
        $processed = RawData::fromParquetFile($inputPath)
            ->select([
                'id',
                'UPPER(TRIM(name)) as clean_name', 
                'CAST(amount as DECIMAL(10,2)) as amount',
                'DATE_TRUNC(\'day\', created_at) as date'
            ])
            ->where('amount', '>', 0)
            ->whereNotNull('name');
            
        // Load to destination
        $processed->toParquetFile($outputPath);
        
        return $processed->count();
    }
}
```

## Development Guidelines

### Code Standards
- Follow PSR-12 coding standards for PHP
- Use descriptive method and variable names
- Add type hints for all method parameters and return types
- Write comprehensive tests for new features

### Testing Requirements
- All new features must include unit tests
- Integration tests required for database operations
- Performance tests for analytical operations
- Use in-memory database (`:memory:`) for unit tests

### Documentation Requirements  
- Update relevant documentation for new features
- Include code examples in docblocks
- Add usage examples to appropriate example projects

### Performance Guidelines
- Always consider memory usage for large datasets
- Profile queries using `EXPLAIN ANALYZE` before optimizing
- Use appropriate batch sizes for bulk operations
- Leverage DuckDB's analytical SQL features

**Always verify that changes work by running the test suite and examples before submitting code.**