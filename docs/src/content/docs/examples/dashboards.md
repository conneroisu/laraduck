---
title: Real-time Dashboards Example
description: Build high-performance dashboards with LaraDuck
---

This example demonstrates how to build real-time analytical dashboards using LaraDuck's fast query capabilities and caching strategies.

## Dashboard Architecture

### Base Dashboard Model

```php
namespace App\Models\Dashboard;

use Illuminate\Support\Facades\Cache;
use LaraDuck\Traits\HasDuckDB;
use LaraDuck\Traits\HasWindowFunctions;

abstract class DashboardMetric extends Model
{
    use HasDuckDB, HasWindowFunctions;
    
    protected $connection = 'duckdb';
    
    protected function cacheKey(): string
    {
        return sprintf('dashboard:%s:%s', 
            static::class, 
            md5(serialize($this->getAttributes()))
        );
    }
    
    public function cached($ttl = 300)
    {
        return Cache::remember($this->cacheKey(), $ttl, function () {
            return $this->calculate();
        });
    }
    
    abstract public function calculate();
}
```

## Sales Dashboard

### Key Metrics

```php
class SalesDashboard
{
    public function overview($dateRange = 'today')
    {
        $dates = $this->getDateRange($dateRange);
        
        // Current period metrics
        $current = DB::connection('duckdb')->selectOne("
            SELECT
                COUNT(*) as total_orders,
                COUNT(DISTINCT customer_id) as unique_customers,
                SUM(total_amount) as revenue,
                AVG(total_amount) as avg_order_value,
                MEDIAN(total_amount) as median_order_value,
                MAX(total_amount) as largest_order,
                SUM(items_count) as total_items_sold
            FROM orders
            WHERE created_at BETWEEN ? AND ?
        ", [$dates['start'], $dates['end']]);
        
        // Comparison with previous period
        $previous = DB::connection('duckdb')->selectOne("
            SELECT
                COUNT(*) as total_orders,
                SUM(total_amount) as revenue
            FROM orders  
            WHERE created_at BETWEEN ? AND ?
        ", [$dates['prev_start'], $dates['prev_end']]);
        
        // Calculate growth rates
        $current->order_growth = $this->calculateGrowth(
            $current->total_orders, 
            $previous->total_orders
        );
        $current->revenue_growth = $this->calculateGrowth(
            $current->revenue,
            $previous->revenue  
        );
        
        return $current;
    }
    
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) return null;
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
```

### Real-time Order Stream

```php
class OrderStream
{
    public function recentOrders($limit = 50)
    {
        return Order::query()
            ->select([
                'id',
                'customer_name',
                'total_amount',
                'status',
                'created_at'
            ])
            ->selectRaw("
                created_at - LAG(created_at) OVER (ORDER BY created_at) as time_since_last
            ")
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
    
    public function orderVelocity($minutes = 60)
    {
        return DB::connection('duckdb')->select("
            WITH time_buckets AS (
                SELECT 
                    time_bucket(INTERVAL '1 minute', created_at) as minute,
                    COUNT(*) as orders_count,
                    SUM(total_amount) as revenue
                FROM orders
                WHERE created_at >= NOW() - INTERVAL ? minute
                GROUP BY minute
            )
            SELECT 
                minute,
                orders_count,
                revenue,
                AVG(orders_count) OVER (
                    ORDER BY minute 
                    ROWS BETWEEN 4 PRECEDING AND CURRENT ROW
                ) as ma5_orders,
                SUM(orders_count) OVER (
                    ORDER BY minute
                ) as cumulative_orders
            FROM time_buckets
            ORDER BY minute DESC
        ", [$minutes]);
    }
}
```

### Product Performance

```php
class ProductMetrics
{
    public function topProducts($period = '7 days', $limit = 10)
    {
        return Cache::remember("top_products:{$period}", 300, function () use ($period, $limit) {
            return DB::connection('duckdb')->select("
                WITH product_sales AS (
                    SELECT 
                        p.id,
                        p.name,
                        p.category,
                        COUNT(DISTINCT o.id) as orders_count,
                        SUM(oi.quantity) as units_sold,
                        SUM(oi.subtotal) as revenue,
                        AVG(oi.price) as avg_selling_price,
                        STDDEV(oi.price) as price_variance
                    FROM products p
                    JOIN order_items oi ON p.id = oi.product_id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at >= CURRENT_TIMESTAMP - INTERVAL ?
                    GROUP BY p.id, p.name, p.category
                ),
                ranked AS (
                    SELECT 
                        *,
                        ROW_NUMBER() OVER (ORDER BY revenue DESC) as revenue_rank,
                        ROW_NUMBER() OVER (ORDER BY units_sold DESC) as volume_rank,
                        PERCENT_RANK() OVER (ORDER BY revenue) as revenue_percentile
                    FROM product_sales
                )
                SELECT 
                    *,
                    ROUND(revenue * 100.0 / SUM(revenue) OVER (), 2) as revenue_share
                FROM ranked
                WHERE revenue_rank <= ?
                ORDER BY revenue DESC
            ", [$period, $limit]);
        });
    }
    
    public function categoryPerformance()
    {
        return DB::connection('duckdb')->select("
            SELECT 
                category,
                COUNT(DISTINCT product_id) as product_count,
                SUM(quantity) as units_sold,
                SUM(subtotal) as revenue,
                AVG(subtotal / NULLIF(quantity, 0)) as avg_unit_price,
                SUM(subtotal) * 100.0 / SUM(SUM(subtotal)) OVER () as revenue_percentage
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE order_created_at >= CURRENT_DATE - INTERVAL '30 days'
            GROUP BY category
            ORDER BY revenue DESC
        ");
    }
}
```

## Customer Analytics Dashboard

### Customer Segments

```php
class CustomerAnalytics
{
    public function segmentation()
    {
        return Cache::remember('customer_segments', 600, function () {
            return DB::connection('duckdb')->select("
                WITH customer_metrics AS (
                    SELECT 
                        customer_id,
                        COUNT(*) as order_count,
                        SUM(total_amount) as lifetime_value,
                        AVG(total_amount) as avg_order_value,
                        MAX(created_at) as last_order_date,
                        MIN(created_at) as first_order_date,
                        MAX(created_at) - MIN(created_at) as customer_age,
                        COUNT(*) FILTER (WHERE created_at >= CURRENT_DATE - INTERVAL '30 days') as recent_orders
                    FROM orders
                    GROUP BY customer_id
                ),
                segments AS (
                    SELECT 
                        *,
                        CASE 
                            WHEN lifetime_value > PERCENTILE_CONT(0.9) WITHIN GROUP (ORDER BY lifetime_value) OVER () 
                                THEN 'VIP'
                            WHEN recent_orders = 0 AND last_order_date < CURRENT_DATE - INTERVAL '90 days'
                                THEN 'At Risk'
                            WHEN order_count = 1 
                                THEN 'New'
                            WHEN recent_orders >= 3
                                THEN 'Active'
                            ELSE 'Regular'
                        END as segment
                    FROM customer_metrics
                )
                SELECT 
                    segment,
                    COUNT(*) as customer_count,
                    AVG(lifetime_value) as avg_ltv,
                    SUM(lifetime_value) as total_revenue,
                    AVG(order_count) as avg_orders,
                    PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY lifetime_value) as median_ltv
                FROM segments
                GROUP BY segment
                ORDER BY total_revenue DESC
            ");
        });
    }
    
    public function cohortAnalysis()
    {
        return DB::connection('duckdb')->select("
            WITH cohorts AS (
                SELECT 
                    customer_id,
                    DATE_TRUNC('month', MIN(created_at)) as cohort_month,
                    MIN(created_at) as first_order_date
                FROM orders
                GROUP BY customer_id
            ),
            cohort_data AS (
                SELECT 
                    c.cohort_month,
                    DATE_DIFF('month', c.cohort_month, DATE_TRUNC('month', o.created_at)) as months_since_first,
                    COUNT(DISTINCT c.customer_id) as customers,
                    SUM(o.total_amount) as revenue
                FROM cohorts c
                JOIN orders o ON c.customer_id = o.customer_id
                WHERE c.cohort_month >= CURRENT_DATE - INTERVAL '12 months'
                GROUP BY c.cohort_month, months_since_first
            ),
            cohort_sizes AS (
                SELECT 
                    cohort_month,
                    COUNT(DISTINCT customer_id) as cohort_size
                FROM cohorts
                GROUP BY cohort_month
            )
            SELECT 
                cd.cohort_month,
                cd.months_since_first,
                cd.customers,
                cs.cohort_size,
                ROUND(cd.customers * 100.0 / cs.cohort_size, 2) as retention_rate,
                cd.revenue,
                ROUND(cd.revenue / cd.customers, 2) as revenue_per_customer
            FROM cohort_data cd
            JOIN cohort_sizes cs ON cd.cohort_month = cs.cohort_month
            ORDER BY cd.cohort_month, cd.months_since_first
        ");
    }
}
```

## Inventory Dashboard

### Stock Levels and Movements

```php
class InventoryDashboard
{
    public function stockOverview()
    {
        return DB::connection('duckdb')->select("
            WITH current_stock AS (
                SELECT 
                    p.id,
                    p.name,
                    p.sku,
                    p.category,
                    i.quantity_on_hand,
                    i.quantity_reserved,
                    i.quantity_on_hand - i.quantity_reserved as available,
                    i.reorder_point,
                    i.reorder_quantity,
                    p.unit_cost,
                    i.quantity_on_hand * p.unit_cost as inventory_value
                FROM products p
                JOIN inventory i ON p.id = i.product_id
            ),
            movement_stats AS (
                SELECT 
                    product_id,
                    AVG(daily_sold) as avg_daily_demand,
                    STDDEV(daily_sold) as demand_stddev,
                    MAX(daily_sold) as max_daily_demand
                FROM (
                    SELECT 
                        product_id,
                        DATE(created_at) as sale_date,
                        SUM(quantity) as daily_sold
                    FROM order_items
                    WHERE created_at >= CURRENT_DATE - INTERVAL '30 days'
                    GROUP BY product_id, sale_date
                ) daily_sales
                GROUP BY product_id
            )
            SELECT 
                cs.*,
                ms.avg_daily_demand,
                CASE 
                    WHEN ms.avg_daily_demand > 0 
                    THEN cs.available / ms.avg_daily_demand 
                    ELSE 999 
                END as days_of_stock,
                CASE
                    WHEN cs.available <= cs.reorder_point THEN 'Reorder Now'
                    WHEN cs.available <= cs.reorder_point * 1.5 THEN 'Low Stock'
                    WHEN cs.available > cs.reorder_point * 5 THEN 'Overstock'
                    ELSE 'Normal'
                END as stock_status
            FROM current_stock cs
            LEFT JOIN movement_stats ms ON cs.id = ms.product_id
            ORDER BY days_of_stock ASC
        ");
    }
    
    public function demandForecast($productId = null, $days = 30)
    {
        $productFilter = $productId ? "AND product_id = ?" : "";
        $params = $productId ? [$days, $productId] : [$days];
        
        return DB::connection('duckdb')->select("
            WITH daily_demand AS (
                SELECT 
                    product_id,
                    DATE(created_at) as date,
                    SUM(quantity) as quantity
                FROM order_items
                WHERE created_at >= CURRENT_DATE - INTERVAL ? days
                {$productFilter}
                GROUP BY product_id, date
            ),
            time_series AS (
                SELECT 
                    product_id,
                    date,
                    quantity,
                    -- Moving averages
                    AVG(quantity) OVER (
                        PARTITION BY product_id 
                        ORDER BY date 
                        ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
                    ) as ma7,
                    AVG(quantity) OVER (
                        PARTITION BY product_id 
                        ORDER BY date 
                        ROWS BETWEEN 13 PRECEDING AND CURRENT ROW
                    ) as ma14,
                    -- Trend
                    REGR_SLOPE(quantity, EXTRACT(EPOCH FROM date)) OVER (
                        PARTITION BY product_id 
                        ORDER BY date 
                        ROWS BETWEEN 6 PRECEDING AND CURRENT ROW
                    ) as trend,
                    -- Day of week seasonality
                    EXTRACT(DOW FROM date) as day_of_week,
                    AVG(quantity) OVER (
                        PARTITION BY product_id, EXTRACT(DOW FROM date)
                    ) as dow_avg
                FROM daily_demand
            )
            SELECT 
                product_id,
                date,
                quantity as actual,
                ROUND(ma7, 2) as forecast_ma7,
                ROUND(ma14, 2) as forecast_ma14,
                ROUND(dow_avg, 2) as seasonal_avg,
                ROUND(
                    ma7 + (trend * 86400 * 7), -- Project 7 days ahead
                    2
                ) as trend_forecast
            FROM time_series
            ORDER BY product_id, date DESC
        ", $params);
    }
}
```

## Performance Monitoring Dashboard

### Query Performance

```php
class PerformanceDashboard
{
    public function queryMetrics()
    {
        return DB::connection('duckdb')->select("
            SELECT 
                query_type,
                COUNT(*) as execution_count,
                AVG(execution_time_ms) as avg_time,
                MEDIAN(execution_time_ms) as median_time,
                PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY execution_time_ms) as p95_time,
                MAX(execution_time_ms) as max_time,
                SUM(execution_time_ms) as total_time
            FROM query_log
            WHERE executed_at >= CURRENT_TIMESTAMP - INTERVAL '1 hour'
            GROUP BY query_type
            ORDER BY total_time DESC
        ");
    }
    
    public function cachePerformance()
    {
        $stats = Cache::get('dashboard_cache_stats', []);
        
        return [
            'hit_rate' => round(($stats['hits'] ?? 0) / max(($stats['total'] ?? 1), 1) * 100, 2),
            'miss_rate' => round(($stats['misses'] ?? 0) / max(($stats['total'] ?? 1), 1) * 100, 2),
            'avg_response_time' => $stats['avg_response_time'] ?? 0,
            'cached_queries' => $stats['cached_queries'] ?? 0,
            'memory_usage' => $stats['memory_usage'] ?? 0,
        ];
    }
}
```

## Dashboard API Endpoints

### Real-time Updates via WebSocket

```php
class DashboardController extends Controller
{
    public function metrics(Request $request)
    {
        $metrics = Cache::remember('dashboard:metrics:' . $request->get('period', 'today'), 60, function () use ($request) {
            return [
                'sales' => (new SalesDashboard)->overview($request->get('period', 'today')),
                'products' => (new ProductMetrics)->topProducts($request->get('period', '7 days')),
                'customers' => (new CustomerAnalytics)->segmentation(),
                'inventory' => (new InventoryDashboard)->stockOverview(),
            ];
        });
        
        return response()->json($metrics);
    }
    
    public function stream()
    {
        return response()->stream(function () {
            while (true) {
                $data = [
                    'orders' => (new OrderStream)->recentOrders(10),
                    'velocity' => (new OrderStream)->orderVelocity(5),
                    'timestamp' => now()->toIso8601String(),
                ];
                
                echo "data: " . json_encode($data) . "\n\n";
                ob_flush();
                flush();
                
                sleep(5); // Update every 5 seconds
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
```

### Optimized Dashboard Queries

```php
class DashboardOptimizer
{
    public function precomputeMetrics()
    {
        // Create materialized views for complex aggregations
        DB::connection('duckdb')->statement("
            CREATE OR REPLACE VIEW dashboard_daily_summary AS
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                COUNT(DISTINCT customer_id) as unique_customers,
                SUM(total_amount) as revenue,
                AVG(total_amount) as avg_order_value,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY total_amount) as median_order_value
            FROM orders
            WHERE created_at >= CURRENT_DATE - INTERVAL '90 days'
            GROUP BY date
        ");
        
        // Precompute product rankings
        DB::connection('duckdb')->statement("
            CREATE OR REPLACE VIEW product_performance_summary AS
            SELECT 
                p.id,
                p.name,
                p.category,
                SUM(oi.quantity) as total_sold_30d,
                SUM(oi.subtotal) as revenue_30d,
                RANK() OVER (ORDER BY SUM(oi.subtotal) DESC) as revenue_rank,
                RANK() OVER (PARTITION BY p.category ORDER BY SUM(oi.subtotal) DESC) as category_rank
            FROM products p
            JOIN order_items oi ON p.id = oi.product_id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at >= CURRENT_DATE - INTERVAL '30 days'
            GROUP BY p.id, p.name, p.category
        ");
    }
}
```