# Laraduck Analytics Dashboard - Livewire Example

A comprehensive Laravel Livewire analytics dashboard showcasing the power of **Laraduck** (DuckDB for Laravel) for high-performance analytical queries and real-time data visualization.

## 🦆 What is Laraduck?

Laraduck is a DuckDB driver for Laravel's Eloquent ORM that brings blazing-fast analytical queries to Laravel applications. It enables querying Parquet, CSV, and JSON files directly without importing, while maintaining full Laravel/Eloquent compatibility.

## ✨ Features Demonstrated

This example showcases:

- **Real-time Analytics**: Interactive Livewire components with live data updates
- **Advanced SQL Analytics**: Window functions, CTEs, and complex aggregations
- **Performance Optimization**: Fast analytical queries on large datasets
- **Modern UI**: Built with Livewire Flux components for a polished interface
- **DuckDB Integration**: Seamless integration with Laravel's Eloquent ORM

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- DuckDB installed on your system
- Laravel 11.0+

### Installation

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Set up the database and sample data:**
   ```bash
   php setup.php
   ```

3. **Verify the installation:**
   ```bash
   php verify-setup.php
   ```

4. **Start the development server:**
   ```bash
   composer run dev
   ```

5. **Visit the dashboard:**
   Open [http://localhost:8000](http://localhost:8000) in your browser

## 📊 Dashboard Components

### Sales Overview
- **Key Metrics**: Total revenue, transactions, and average transaction value
- **Daily Sales Chart**: Visual representation of sales trends over the last 30 days
- **Regional Performance**: Sales breakdown by geographic region
- **Top Products**: Best-performing products by revenue

### Analytics Chart
- **Interactive Charts**: Switch between revenue, transactions, and trend analysis
- **Timeframe Selection**: View data for 7, 30, or 90-day periods
- **Moving Averages**: Advanced analytics using DuckDB's window functions

### Product Analytics
- **Product Performance**: Detailed analysis of product sales and profitability
- **Category Filtering**: Filter products by category
- **Profit Margins**: Visual representation of product profitability
- **Sales Metrics**: Units sold, revenue, and transaction counts

## 🏗️ Architecture

### Models

- **`Sale`**: AnalyticalModel with advanced query scopes for sales analytics
- **`Product`**: Product catalog with pricing and category information
- **`Customer`**: Customer data with regional and type classifications

### Livewire Components

- **`SalesOverview`**: Main dashboard metrics and overview charts
- **`AnalyticsChart`**: Interactive charts with multiple data views
- **`ProductAnalytics`**: Detailed product performance analysis

### Key Features

#### Advanced DuckDB Queries
```php
// Daily sales with aggregations
Sale::dailySales()->get();

// Regional performance analysis
Sale::regionSales()->get();

// Moving averages with window functions
Sale::salesTrends(7)->get();

// Top products by revenue
Sale::topProducts(10)->get();
```

#### Real-time Updates
```php
// Livewire component with live data binding
public function loadOverviewData()
{
    $this->totalRevenue = Sale::sum('total_amount');
    // ... other metrics
}
```

#### File Querying (Trait Available)
```php
// Query Parquet files directly (when files are available)
Sale::fromParquetFile('sales/*.parquet')
    ->where('region', 'North America')
    ->sum('revenue');
```

## 🔧 Configuration

### Database Configuration

The DuckDB connection is configured in `config/database.php`:

```php
'analytics' => [
    'driver' => 'duckdb',
    'database' => database_path('analytics.duckdb'),
    'read_only' => false,
    'memory_limit' => '4GB',
    'max_memory' => '8GB',
    'threads' => 4,
],
```

### Environment Variables

You can customize DuckDB settings via `.env`:

```env
DUCKDB_DATABASE=database/analytics.duckdb
DUCKDB_READ_ONLY=false
DUCKDB_MEMORY_LIMIT=4GB
DUCKDB_MAX_MEMORY=8GB
DUCKDB_THREADS=4
```

## 📈 Sample Data

The example includes:
- **10 products** across Electronics and Accessories categories
- **50 customers** distributed across 4 regions
- **500 sales transactions** spanning 90 days
- Realistic pricing, discounts, and transaction patterns

## 🎯 Performance Benefits

DuckDB provides significant performance advantages for analytical workloads:

- **Columnar Storage**: Optimized for analytical queries
- **Vectorized Execution**: Fast processing of large datasets
- **Advanced Analytics**: Built-in support for window functions and complex aggregations
- **Memory Efficiency**: Intelligent memory management for large datasets

## 🛠️ Development

### Code Quality

Run these commands to maintain code quality:

```bash
# Check code style with Laravel Pint
composer run lint

# Fix code style issues automatically
composer run lint:fix

# Run tests
composer run test
```

### Adding New Analytics

1. **Create a new scope in the Sale model:**
   ```php
   public function scopeCustomAnalytics($query)
   {
       return $query->select([
           // Your custom analytics
       ]);
   }
   ```

2. **Create a new Livewire component:**
   ```bash
   php artisan make:livewire CustomAnalytics
   ```

3. **Add to the dashboard:**
   ```blade
   <livewire:custom-analytics />
   ```

### File-Based Analytics

To add file querying capabilities:

1. **Add sample data files** (Parquet, CSV, JSON) to a `data/` directory
2. **Use the QueriesFiles trait** in your models
3. **Query files directly:**
   ```php
   Sale::fromParquetFile('data/sales.parquet')
       ->where('amount', '>', 100)
       ->get();
   ```

## 🔍 Troubleshooting

### Common Issues

1. **DuckDB not found:**
   - Install DuckDB: `brew install duckdb` (macOS) or visit [duckdb.org](https://duckdb.org/docs/installation/)

2. **Memory issues with large datasets:**
   - Increase `DUCKDB_MEMORY_LIMIT` in your `.env` file
   - Use streaming queries for very large files

3. **Performance optimization:**
   - Create indexes on frequently queried columns
   - Use DuckDB's `EXPLAIN ANALYZE` to optimize queries

### Verification

Run the verification script to check your setup:
```bash
php verify-setup.php
```

## 📚 Learn More

- [Laraduck Documentation](https://github.com/laraduck/eloquent-duckdb)
- [DuckDB Documentation](https://duckdb.org/docs/)
- [Laravel Livewire](https://livewire.laravel.com/)
- [Livewire Flux](https://flux.laravel.com/)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request to the main Laraduck repository.

## 📄 License

This example is open-sourced software licensed under the [MIT license](LICENSE).