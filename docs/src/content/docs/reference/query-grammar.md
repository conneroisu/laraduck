---
title: Query Grammar
description: DuckDB SQL grammar extensions in LaraDuck
---

## Query Grammar Overview

LaraDuck extends Laravel's query builder to support DuckDB-specific SQL features while maintaining familiar Laravel syntax.

## Select Extensions

### Window Functions

```php
// Basic window function
Model::query()
    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY category ORDER BY price DESC) as rank')
    ->get();

// Named windows
Model::query()
    ->selectRaw('SUM(amount) OVER w as running_total')
    ->selectRaw('AVG(amount) OVER w as running_avg')
    ->fromRaw('sales, WINDOW w AS (ORDER BY date)')
    ->get();
```

### DISTINCT ON

```php
// Get latest record per group
Model::query()
    ->selectRaw('DISTINCT ON (user_id) *')
    ->orderBy('user_id')
    ->orderBy('created_at', 'desc')
    ->get();
```

### Array Functions

```php
// Array aggregation
Model::query()
    ->selectRaw('LIST(name) as names')
    ->selectRaw('LIST(DISTINCT category) as categories')
    ->groupBy('department')
    ->get();

// Array operations
Model::query()
    ->selectRaw("array_contains(tags, 'urgent') as is_urgent")
    ->selectRaw("array_length(tags) as tag_count")
    ->get();
```

## From Extensions

### Table Functions

```php
// Read from CSV
Model::query()
    ->fromRaw("read_csv('/data/sales.csv', header=true)")
    ->where('amount', '>', 100)
    ->get();

// Generate series
Model::query()
    ->fromRaw("generate_series(1, 100) as t(n)")
    ->get();

// Glob patterns
Model::query()
    ->fromRaw("read_parquet('/data/sales_*.parquet')")
    ->get();
```

### VALUES Clause

```php
Model::query()
    ->fromRaw("(VALUES (1, 'A'), (2, 'B'), (3, 'C')) as t(id, letter)")
    ->get();
```

## Join Extensions

### ASOF Joins

```php
// Time-series join
Model::query()
    ->from('orders')
    ->joinRaw('ASOF JOIN prices ON orders.timestamp >= prices.timestamp')
    ->get();
```

### Lateral Joins

```php
Model::query()
    ->from('users')
    ->joinRaw('LATERAL (SELECT * FROM orders WHERE orders.user_id = users.id LIMIT 5) as recent_orders ON true')
    ->get();
```

## Where Extensions

### Array Predicates

```php
// Array contains
Model::query()
    ->whereRaw("array_contains(tags, ?)", ['urgent'])
    ->get();

// Array overlap
Model::query()
    ->whereRaw("list_has_any(categories, ?)", [['electronics', 'computers']])
    ->get();
```

### Pattern Matching

```php
// SIMILAR TO
Model::query()
    ->whereRaw("email SIMILAR TO ?", ['%@(gmail|yahoo)\.com'])
    ->get();

// Glob pattern
Model::query()
    ->whereRaw("filename GLOB ?", ['*.csv'])
    ->get();
```

## Aggregation Extensions

### Statistical Functions

```php
Model::query()
    ->selectRaw('MEDIAN(price) as median_price')
    ->selectRaw('MODE(category) as most_common')
    ->selectRaw('QUANTILE_CONT(price, 0.95) as p95')
    ->get();
```

### Approximate Functions

```php
Model::query()
    ->selectRaw('APPROX_COUNT_DISTINCT(user_id) as unique_users')
    ->selectRaw('APPROX_QUANTILE(response_time, 0.99) as p99')
    ->get();
```

## Data Types

### Struct Types

```php
// Create struct
Model::query()
    ->selectRaw("{'name': name, 'email': email} as user_info")
    ->get();

// Access struct fields
Model::query()
    ->selectRaw("user_info.name as name")
    ->get();
```

### List/Array Types

```php
// Create lists
Model::query()
    ->selectRaw("LIST(order_id) as order_ids")
    ->groupBy('user_id')
    ->get();

// Unnest arrays
Model::query()
    ->fromRaw("orders, UNNEST(order_items) as t(item)")
    ->get();
```

## Special Clauses

### QUALIFY

```php
// Filter window function results
Model::query()
    ->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY category ORDER BY price DESC) as rn')
    ->qualifyRaw('rn <= 3')
    ->get();
```

### SAMPLE

```php
// Random sampling
Model::query()
    ->sampleRaw('10 PERCENT')
    ->get();

// Fixed number sample
Model::query()
    ->sampleRaw('1000 ROWS')
    ->get();
```