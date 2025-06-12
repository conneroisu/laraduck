# E-Commerce Analytics Example

A comprehensive example demonstrating the Laravel DuckDB extension capabilities through an e-commerce analytics platform.

## Features Demonstrated

- **Analytical Models**: Using DuckDB-optimized models for OLAP workloads
- **Complex Analytics**: Window functions, CTEs, QUALIFY clauses
- **File Operations**: Import/export with Parquet, CSV, JSON
- **Real-time Analytics**: Executive dashboards and KPIs
- **Advanced Queries**: RFM analysis, cohort retention, market basket analysis
- **Performance**: Batch operations and columnar storage benefits

## Quick Start

1. **Install dependencies** (from the example directory):

```bash
composer install
```

2. **Run setup to create database and generate sample data**:

```bash
php setup.php
```

This creates:

- 5,000 customers
- 200 products
- 25,000 orders
- ~100,000 order items

3. **Run analytics examples**:

```bash
php analytics.php
```

4. **Run file operations examples**:

```bash
php file_operations.php
```

5. **Generate comprehensive reports**:

```bash
php reports.php
```

6. **Launch web dashboard**:

```bash
php -S localhost:8000 dashboard.php
```

Then open http://localhost:8000 in your browser.

## Project Structure

```
ecommerce-analytics/
├── app/
│   ├── Models/           # DuckDB analytical models
│   │   ├── Customer.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   └── Product.php
│   ├── Analytics/        # Analytics query classes
│   │   ├── SalesAnalytics.php
│   │   ├── CustomerAnalytics.php
│   │   └── ProductAnalytics.php
│   └── Commands/         # CLI operations
│       └── FileOperations.php
├── database/
│   ├── migrations/       # Table schemas
│   └── seeders/          # Data generators
├── data/                 # DuckDB database files
├── exports/              # Export files (Parquet, CSV, JSON)
│   └── reports/          # Generated reports
└── resources/
    └── views/            # Dashboard views
```

## Analytics Examples

### 1. Executive Dashboard

- Current period metrics vs previous period
- Growth rates and KPIs
- Real-time revenue tracking

### 2. Sales Analytics

- Time-series analysis with various granularities
- Revenue breakdown by multiple dimensions
- Year-over-Year comparisons
- Sales forecasting

### 3. Customer Analytics

- RFM (Recency, Frequency, Monetary) segmentation
- Customer Lifetime Value (CLV) analysis
- Retention cohort analysis
- Churn prediction

### 4. Product Analytics

- Top selling products
- Market basket analysis (frequently bought together)
- Product velocity and performance
- Price optimization insights
- ABC analysis (Pareto principle)

### 5. Advanced Features

- Window functions for running totals and rankings
- CTEs for complex hierarchical queries
- QUALIFY clause for window function filtering
- Batch operations for performance

## File Operations

### Export Examples

- Orders to Parquet with compression
- Partitioned exports by date/region
- Analytics results to CSV
- Product catalog to JSON
- Customer segments with RFM scores

### Import Examples

- Query Parquet files directly without loading
- Import CSV with automatic schema detection
- Query multiple files with glob patterns
- HTTP/HTTPS remote file access
- Join file data with database tables

## Performance Benefits

This example demonstrates DuckDB's advantages for analytics:

1. **Columnar Storage**: Efficient compression and fast aggregations
2. **Vectorized Execution**: Process data in batches
3. **File Querying**: Analyze files without ETL
4. **OLAP Optimization**: Built for analytical queries
5. **Memory Efficiency**: Process larger-than-memory datasets

## Extending the Example

You can extend this example by:

1. Adding more analytical queries
2. Implementing real-time streaming analytics
3. Creating data pipelines with file imports
4. Building ML features with DuckDB's functions
5. Adding more visualization options

## Notes

- The dashboard requires an internet connection for Chart.js and Tailwind CSS
- Large datasets may take time to generate during setup
- File exports are saved in the `exports/` directory
- The DuckDB database is stored in `data/analytics.duckdb`
