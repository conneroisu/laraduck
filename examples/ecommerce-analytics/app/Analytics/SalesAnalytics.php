<?php

namespace App\Analytics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Capsule\Manager as Capsule;

class SalesAnalytics
{
    /**
     * Executive Dashboard Metrics
     */
    public static function executiveDashboard($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();
        
        // Current period metrics
        $currentMetrics = Order::query()
            ->where('status', 'completed')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->selectRaw('COUNT(DISTINCT order_id) as total_orders')
            ->selectRaw('COUNT(DISTINCT customer_id) as unique_customers')
            ->selectRaw('SUM(total_amount) as total_revenue')
            ->selectRaw('AVG(total_amount) as avg_order_value')
            ->first();
            
        // Previous period for comparison
        $daysDiff = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($daysDiff);
        $prevEndDate = $startDate->copy()->subDay();
        
        $previousMetrics = Order::query()
            ->where('status', 'completed')
            ->whereBetween('order_date', [$prevStartDate, $prevEndDate])
            ->selectRaw('COUNT(DISTINCT order_id) as total_orders')
            ->selectRaw('COUNT(DISTINCT customer_id) as unique_customers')
            ->selectRaw('SUM(total_amount) as total_revenue')
            ->selectRaw('AVG(total_amount) as avg_order_value')
            ->first();
            
        // Calculate growth rates
        $metrics = [
            'current_period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_orders' => $currentMetrics->total_orders,
                'unique_customers' => $currentMetrics->unique_customers,
                'total_revenue' => round($currentMetrics->total_revenue, 2),
                'avg_order_value' => round($currentMetrics->avg_order_value, 2),
            ],
            'previous_period' => [
                'start_date' => $prevStartDate->format('Y-m-d'),
                'end_date' => $prevEndDate->format('Y-m-d'),
                'total_orders' => $previousMetrics->total_orders,
                'unique_customers' => $previousMetrics->unique_customers,
                'total_revenue' => round($previousMetrics->total_revenue, 2),
                'avg_order_value' => round($previousMetrics->avg_order_value, 2),
            ],
            'growth' => [
                'orders_growth' => self::calculateGrowth($previousMetrics->total_orders, $currentMetrics->total_orders),
                'customers_growth' => self::calculateGrowth($previousMetrics->unique_customers, $currentMetrics->unique_customers),
                'revenue_growth' => self::calculateGrowth($previousMetrics->total_revenue, $currentMetrics->total_revenue),
                'aov_growth' => self::calculateGrowth($previousMetrics->avg_order_value, $currentMetrics->avg_order_value),
            ]
        ];
        
        return $metrics;
    }
    
    /**
     * Sales trend analysis with multiple time granularities
     */
    public static function salesTrend($granularity = 'day', $periods = 30)
    {
        $startDate = match($granularity) {
            'hour' => now()->subHours($periods),
            'day' => now()->subDays($periods),
            'week' => now()->subWeeks($periods),
            'month' => now()->subMonths($periods),
            'quarter' => now()->subQuarters($periods),
            'year' => now()->subYears($periods),
            default => now()->subDays($periods)
        };
        
        return Order::query()
            ->where('status', 'completed')
            ->where('order_date', '>=', $startDate)
            ->selectRaw("DATE_TRUNC(?, order_date) as period", [$granularity])
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COUNT(DISTINCT customer_id) as unique_customers')
            ->selectRaw('SUM(total_amount) as revenue')
            ->selectRaw('AVG(total_amount) as avg_order_value')
            ->selectRaw('MIN(total_amount) as min_order_value')
            ->selectRaw('MAX(total_amount) as max_order_value')
            ->selectRaw('STDDEV(total_amount) as order_value_stddev')
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
    
    /**
     * Revenue breakdown by dimensions
     */
    public static function revenueBreakdown($dimension = 'category', $startDate = null, $endDate = null)
    {
        // Simple revenue breakdown using raw SQL to avoid memory issues
        $sql = "
            SELECT 
                p.category,
                COUNT(DISTINCT o.order_id) as order_count,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                SUM(oi.quantity) as units_sold,
                SUM(oi.total_price) as revenue,
                SUM(oi.discount * oi.quantity) as total_discount,
                AVG(oi.total_price / oi.quantity) as avg_unit_price
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE o.status = 'completed'
            GROUP BY p.category
            ORDER BY revenue DESC
        ";
        
        return Capsule::connection('duckdb')->select($sql);
    }
    
    /**
     * Year-over-Year comparison
     */
    public static function yearOverYearComparison($metric = 'revenue', $granularity = 'month')
    {
        $currentYear = now()->year;
        $lastYear = $currentYear - 1;
        
        $metricCalculation = match($metric) {
            'revenue' => 'SUM(total_amount)',
            'orders' => 'COUNT(*)',
            'customers' => 'COUNT(DISTINCT customer_id)',
            'aov' => 'AVG(total_amount)',
            default => 'SUM(total_amount)'
        };
        
        return Capsule::connection('duckdb')->query()
            ->fromRaw("(
                SELECT 
                    DATE_TRUNC('{$granularity}', order_date) as period,
                    YEAR(order_date) as year,
                    {$metricCalculation} as metric_value
                FROM orders
                WHERE status = 'completed'
                    AND YEAR(order_date) IN ({$currentYear}, {$lastYear})
                GROUP BY period, year
            ) as yoy")
            ->selectRaw("DATE_PART('{$granularity}', period) as period_number")
            ->selectRaw("MAX(CASE WHEN year = {$lastYear} THEN metric_value END) as last_year")
            ->selectRaw("MAX(CASE WHEN year = {$currentYear} THEN metric_value END) as current_year")
            ->selectRaw("(MAX(CASE WHEN year = {$currentYear} THEN metric_value END) - 
                         MAX(CASE WHEN year = {$lastYear} THEN metric_value END)) / 
                         MAX(CASE WHEN year = {$lastYear} THEN metric_value END) * 100 as yoy_growth")
            ->groupBy('period_number')
            ->orderBy('period_number')
            ->get();
    }
    
    /**
     * Sales forecasting using simple moving average
     */
    public static function salesForecast($forecastDays = 30, $lookbackDays = 90)
    {
        // Historical data with moving averages
        $historicalData = Order::query()
            ->where('status', 'completed')
            ->where('order_date', '>=', now()->subDays($lookbackDays + 30))
            ->selectRaw("DATE_TRUNC('day', order_date) as date")
            ->selectRaw('SUM(total_amount) as daily_revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Calculate moving averages
        $maData = Capsule::connection('duckdb')->query()
            ->withCte('daily_revenue', function($query) use ($lookbackDays) {
                $query->from('orders')
                    ->where('status', 'completed')
                    ->where('order_date', '>=', now()->subDays($lookbackDays + 30))
                    ->selectRaw("DATE_TRUNC('day', order_date) as date")
                    ->selectRaw('SUM(total_amount) as revenue')
                    ->groupBy('date');
            })
            ->from('daily_revenue')
            ->selectRaw('date')
            ->selectRaw('revenue')
            ->selectRaw('AVG(revenue) OVER (ORDER BY date ROWS BETWEEN 6 PRECEDING AND CURRENT ROW) as ma7')
            ->selectRaw('AVG(revenue) OVER (ORDER BY date ROWS BETWEEN 29 PRECEDING AND CURRENT ROW) as ma30')
            ->selectRaw('STDDEV(revenue) OVER (ORDER BY date ROWS BETWEEN 29 PRECEDING AND CURRENT ROW) as stddev30')
            ->orderBy('date', 'desc')
            ->limit($lookbackDays)
            ->get();
            
        // Simple forecast based on recent trend
        $recentAvg = $maData->take(7)->avg('revenue');
        $recentTrend = ($maData->first()->ma7 - $maData->get(6)->ma7) / 7;
        
        $forecast = collect();
        for ($i = 1; $i <= $forecastDays; $i++) {
            $forecastDate = now()->addDays($i);
            $forecastValue = $recentAvg + ($recentTrend * $i);
            
            $forecast->push([
                'date' => $forecastDate->format('Y-m-d'),
                'forecast_revenue' => round($forecastValue, 2),
                'confidence_lower' => round($forecastValue * 0.8, 2),
                'confidence_upper' => round($forecastValue * 1.2, 2),
            ]);
        }
        
        return [
            'historical' => $maData,
            'forecast' => $forecast
        ];
    }
    
    /**
     * Conversion funnel analysis
     */
    public static function conversionFunnel($startDate = null, $endDate = null)
    {
        $dateFilter = '';
        if ($startDate && $endDate) {
            $dateFilter = "AND view_timestamp BETWEEN '{$startDate}' AND '{$endDate}'";
        }
        
        return Capsule::connection('duckdb')->select("
            WITH funnel_stages AS (
                SELECT 
                    COUNT(DISTINCT session_id) as total_sessions,
                    COUNT(DISTINCT CASE WHEN customer_id IS NOT NULL THEN session_id END) as logged_in_sessions,
                    COUNT(DISTINCT pv.session_id) as viewed_product,
                    COUNT(DISTINCT o.customer_id) as made_purchase
                FROM product_views pv
                LEFT JOIN orders o ON pv.customer_id = o.customer_id 
                    AND o.order_date >= pv.view_timestamp
                    AND o.order_date <= pv.view_timestamp + INTERVAL '1 day'
                WHERE 1=1 {$dateFilter}
            )
            SELECT 
                'Site Visit' as stage,
                1 as stage_order,
                total_sessions as users,
                100.0 as percentage
            FROM funnel_stages
            UNION ALL
            SELECT 
                'Logged In' as stage,
                2 as stage_order,
                logged_in_sessions as users,
                ROUND(logged_in_sessions * 100.0 / total_sessions, 2) as percentage
            FROM funnel_stages
            UNION ALL
            SELECT 
                'Viewed Product' as stage,
                3 as stage_order,
                viewed_product as users,
                ROUND(viewed_product * 100.0 / total_sessions, 2) as percentage
            FROM funnel_stages
            UNION ALL
            SELECT 
                'Made Purchase' as stage,
                4 as stage_order,
                made_purchase as users,
                ROUND(made_purchase * 100.0 / total_sessions, 2) as percentage
            FROM funnel_stages
            ORDER BY stage_order
        ");
    }
    
    private static function calculateGrowth($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
}