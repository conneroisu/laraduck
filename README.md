# Laravel DuckDB

A DuckDB driver for Laravel's Eloquent ORM, bringing blazing-fast analytical queries to your Laravel applications.

## Features

- 🚀 **Blazing Fast Analytics** - Process millions of rows in seconds with DuckDB's columnar engine
- 📁 **Query Files Directly** - Query Parquet, CSV, and JSON files without importing
- 🎯 **Laravel Native** - Seamlessly integrates with Eloquent ORM
- 📊 **Advanced SQL Features** - Window functions, CTEs, QUALIFY clause, and more
- 🔄 **Batch Operations** - Optimized for bulk data operations
- 📈 **Time Series Analysis** - Built-in support for temporal data

## Performance

DuckDB excels at analytical workloads with impressive benchmarks:

- **100k bulk insert**: 1.2 seconds (83,100 rows/sec)
- **Aggregations**: 3-7x faster than traditional databases
- **Parquet queries**: 469,000 rows/sec write, 62,900 rows/sec read
- **Window functions**: Native support with 45ms for complex queries

See [BENCHMARKS.md](BENCHMARKS.md) for detailed performance metrics.

## Documentation

Full documentation is available at [https://laraduck.dev](https://laraduck.dev)

For local documentation development:
```bash
cd docs
npm install
npm run dev
```

## Quick Start

### Installation

```bash
composer require laraduck/laraduck
```

### Configuration

Add a DuckDB connection to your `config/database.php`:

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

### Basic Usage

```php
use ConnerOiSu\LaravelDuckDB\Eloquent\AnalyticalModel;

class Sale extends AnalyticalModel
{
    protected $connection = 'analytics';
}

// Analytical queries
$revenue = Sale::query()
    ->whereYear('created_at', 2024)
    ->sum('revenue');

// Query files directly
$historical = Sale::fromParquetFile('s3://data-lake/sales/*.parquet')
    ->where('region', 'North America')
    ->sum('revenue');

// Window functions
$ranked = Sale::query()
    ->selectRaw('*, RANK() OVER (ORDER BY revenue DESC) as rank')
    ->qualify('rank <= 10')
    ->get();
```

## Examples

See the `examples/` directory for complete examples:
- **E-commerce Analytics** - Full analytics platform with dashboards
- **Log Analysis** - Query log files without ETL
- **Financial Reports** - Complex financial calculations

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
