# Laraduck Analytics Dashboard with React

A comprehensive e-commerce analytics dashboard built with Laravel, Inertia.js, React, and Laraduck (DuckDB integration for Laravel). This example showcases advanced analytical features including window functions, CTEs, pivot tables, and real-time analytics.

## Features Demonstrated

### Laraduck Integration

- **Analytical Models**: Extended Eloquent models with analytical capabilities
- **Window Functions**: RANK(), DENSE_RANK(), PERCENT_RANK(), ROW_NUMBER()
- **Common Table Expressions (CTEs)**: Complex multi-step queries
- **Pivot Operations**: Dynamic cross-tabulation of data
- **File Operations**: Parquet export/import, CSV handling
- **Advanced Aggregations**: Moving averages, cumulative sums, statistical functions

### Analytics Dashboards

1. **Main Dashboard**: Key metrics, trends, top products, regional analysis
2. **Sales Analytics**: Time series analysis, moving averages, payment methods
3. **Customer Analytics**: RFM segmentation, CLV prediction, cohort retention
4. **Product Analytics**: ABC analysis, inventory turnover, cross-regional performance
5. **Advanced Analytics**: Window functions demo, cross-sell recommendations

### Technical Stack

- **Backend**: Laravel 10 with Laraduck
- **Frontend**: React with Inertia.js
- **UI Components**: Tremor (React components for dashboards)
- **Charts**: Tremor's built-in charts (Area, Bar, Donut, Scatter, Line)
- **Database**: DuckDB (via Laraduck)

## Installation

### Prerequisites

- PHP 8.1+
- Composer
- Node.js 16+
- DuckDB CLI (optional, for direct queries)

### Setup Steps

1. **Navigate to the example directory**:

```bash
cd examples/analytics-dashboard-react
```

2. **Install dependencies**:

```bash
composer install
npm install
```

3. **Configure environment**:

```bash
cp .env.example .env
php artisan key:generate
```

4. **Add DuckDB configuration to `.env`**:

```env
DUCKDB_PATH=database/analytics.duckdb
DUCKDB_MEMORY_LIMIT=4GB
DUCKDB_THREADS=4
```

5. **Run migrations**:

```bash
php artisan migrate
```

6. **Seed the database with sample data**:

```bash
php artisan db:seed
```

This will create:

- 10,000 customers
- 500 products
- 50,000 orders
- ~150,000 sales records

7. **Build frontend assets**:

```bash
npm run build
# or for development
npm run dev
```

8. **Start the server**:

```bash
php artisan serve
```

9. **Access the dashboard**:
   Open http://localhost:8000 in your browser

## Usage Examples

### Analytical Model Examples

**RFM Analysis**:

```php
$rfmSegments = Customer::rfmAnalysis()
    ->get()
    ->groupBy('rfm_segment');
```

**Sales with Window Functions**:

```php
$rankedSales = Sale::salesByRegionWithRank()
    ->where('sale_date', '>=', now()->subDays(7))
    ->get();
```

**Product Cross-Sell Analysis**:

```php
$recommendations = Product::crossSellAnalysis($productId, 5)
    ->get();
```

**Moving Averages**:

```php
$movingAvg = Sale::movingAverages(7)
    ->dateRange($startDate, $endDate)
    ->get();
```

### Export Operations

**Export to Parquet**:

```php
Sale::exportToParquet('exports/sales.parquet', $startDate, $endDate);
```

**Query Parquet Files**:

```php
$results = Sale::queryParquet('exports/sales.parquet')
    ->where('region', 'North')
    ->sum('total_amount');
```

## Key Features Showcase

### 1. Window Functions

The Advanced Analytics page demonstrates window functions with regional sales ranking:

- RANK() for position within region
- PERCENT_RANK() for percentile calculations
- Partition-based aggregations

### 2. CTEs (Common Table Expressions)

Used throughout for complex multi-step queries:

- RFM scoring with multiple CTEs
- ABC analysis with running totals
- Cross-sell analysis with order filtering

### 3. Time Series Analysis

- Daily/weekly/monthly aggregations
- Moving averages (7-day, 30-day)
- Year-over-year comparisons
- Seasonal trend detection

### 4. Customer Segmentation

- RFM (Recency, Frequency, Monetary) analysis
- Customer lifetime value prediction
- Churn risk scoring
- Cohort retention analysis

### 5. Inventory Analytics

- ABC classification
- Inventory turnover rates
- Days of supply calculations
- Dead stock identification

## Performance Optimization

The example includes several performance optimizations:

1. **Indexed columns** for common queries
2. **Materialized CTEs** for complex calculations
3. **Batch processing** for large datasets
4. **Query result caching** for dashboard metrics
5. **Parquet file format** for efficient storage

## Extending the Example

### Adding New Analytics

1. Create new methods in the analytical models
2. Add corresponding controller methods
3. Create React components for visualization
4. Update routes as needed

### Custom Metrics

Extend the models with your own analytical methods:

```php
public static function customMetric()
{
    return static::query()
        ->withCte('intermediate_calc', function ($query) {
            // Your CTE logic
        })
        ->select([
            // Your selections with window functions
        ]);
}
```

## Troubleshooting

### Memory Issues

If you encounter memory issues with large datasets:

1. Increase PHP memory limit in `php.ini`
2. Adjust DuckDB memory settings in `.env`
3. Use pagination for large result sets

### Performance Tips

1. Use `explain()` to analyze query plans
2. Create appropriate indexes
3. Leverage DuckDB's columnar storage
4. Use Parquet files for historical data

## License

This example is part of the Laraduck package and follows the same license terms.
