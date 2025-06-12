<?php

namespace App\Analytics;

use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class ProductAnalytics
{
    /**
     * Product performance dashboard
     */
    public static function productPerformanceDashboard($startDate = null, $endDate = null)
    {
        $dateFilter = [];
        if ($startDate && $endDate) {
            $dateFilter = [$startDate, $endDate];
        } else {
            $dateFilter = [now()->subDays(30), now()];
        }
        
        // Top products by revenue
        $topProducts = OrderItem::topSellingProducts(10, $dateFilter[0], $dateFilter[1])->get();
        
        // Category performance
        $categoryPerformance = DB::connection('duckdb')->table('products as p')
            ->join('order_items as oi', 'p.product_id', '=', 'oi.product_id')
            ->join('orders as o', 'oi.order_id', '=', 'o.order_id')
            ->where('o.status', 'completed')
            ->whereBetween('o.order_date', $dateFilter)
            ->selectRaw('p.category')
            ->selectRaw('COUNT(DISTINCT p.product_id) as product_count')
            ->selectRaw('COUNT(DISTINCT o.order_id) as order_count')
            ->selectRaw('SUM(oi.quantity) as units_sold')
            ->selectRaw('SUM(oi.total_price) as revenue')
            ->selectRaw('AVG(oi.total_price / oi.quantity) as avg_price')
            ->selectRaw('SUM(oi.discount * oi.quantity) as total_discounts')
            ->groupBy('p.category')
            ->orderBy('revenue', 'desc')
            ->get();
            
        // New vs existing products performance
        $productAge = DB::connection('duckdb')->query()
            ->from('products as p')
            ->join('order_items as oi', 'p.product_id', '=', 'oi.product_id')
            ->join('orders as o', 'oi.order_id', '=', 'o.order_id')
            ->where('o.status', 'completed')
            ->whereBetween('o.order_date', $dateFilter)
            ->selectRaw("CASE 
                WHEN DATEDIFF('day', p.launch_date, CURRENT_DATE) <= 30 THEN 'New (< 30 days)'
                WHEN DATEDIFF('day', p.launch_date, CURRENT_DATE) <= 90 THEN 'Recent (30-90 days)'
                WHEN DATEDIFF('day', p.launch_date, CURRENT_DATE) <= 365 THEN 'Established (90-365 days)'
                ELSE 'Mature (> 365 days)'
            END as product_age_group")
            ->selectRaw('COUNT(DISTINCT p.product_id) as product_count')
            ->selectRaw('SUM(oi.quantity) as units_sold')
            ->selectRaw('SUM(oi.total_price) as revenue')
            ->selectRaw('AVG(oi.total_price / oi.quantity) as avg_selling_price')
            ->groupBy('product_age_group')
            ->orderByRaw("CASE product_age_group
                WHEN 'New (< 30 days)' THEN 1
                WHEN 'Recent (30-90 days)' THEN 2
                WHEN 'Established (90-365 days)' THEN 3
                ELSE 4
            END")
            ->get();
            
        return [
            'date_range' => [
                'start' => $dateFilter[0]->format('Y-m-d'),
                'end' => $dateFilter[1]->format('Y-m-d')
            ],
            'top_products' => $topProducts,
            'category_performance' => $categoryPerformance,
            'product_age_analysis' => $productAge
        ];
    }
    
    /**
     * Market basket analysis - products frequently bought together
     */
    public static function marketBasketAnalysis($minSupport = 0.01, $minConfidence = 0.1)
    {
        // Find product pairs that are frequently bought together
        $basketAnalysis = DB::connection('duckdb')->query()
            ->withCte('order_baskets', function($query) {
                $query->from('order_items as oi1')
                    ->join('order_items as oi2', function($join) {
                        $join->on('oi1.order_id', '=', 'oi2.order_id')
                             ->whereRaw('oi1.product_id < oi2.product_id');
                    })
                    ->selectRaw('oi1.product_id as product_a')
                    ->selectRaw('oi2.product_id as product_b')
                    ->selectRaw('COUNT(DISTINCT oi1.order_id) as co_occurrence_count');
            })
            ->withCte('product_frequencies', function($query) {
                $query->from('order_items')
                    ->selectRaw('product_id')
                    ->selectRaw('COUNT(DISTINCT order_id) as frequency')
                    ->groupBy('product_id');
            })
            ->withCte('total_orders', function($query) {
                $query->from('orders')
                    ->where('status', 'completed')
                    ->selectRaw('COUNT(DISTINCT order_id) as total');
            })
            ->from('order_baskets as ob')
            ->join('product_frequencies as pf1', 'ob.product_a', '=', 'pf1.product_id')
            ->join('product_frequencies as pf2', 'ob.product_b', '=', 'pf2.product_id')
            ->join('products as p1', 'ob.product_a', '=', 'p1.product_id')
            ->join('products as p2', 'ob.product_b', '=', 'p2.product_id')
            ->crossJoin('total_orders as t')
            ->selectRaw('p1.name as product_a_name')
            ->selectRaw('p2.name as product_b_name')
            ->selectRaw('ob.co_occurrence_count')
            ->selectRaw('pf1.frequency as product_a_frequency')
            ->selectRaw('pf2.frequency as product_b_frequency')
            ->selectRaw('ob.co_occurrence_count::FLOAT / t.total as support')
            ->selectRaw('ob.co_occurrence_count::FLOAT / pf1.frequency as confidence_a_to_b')
            ->selectRaw('ob.co_occurrence_count::FLOAT / pf2.frequency as confidence_b_to_a')
            ->selectRaw('(ob.co_occurrence_count::FLOAT / t.total) / ((pf1.frequency::FLOAT / t.total) * (pf2.frequency::FLOAT / t.total)) as lift')
            ->havingRaw('support >= ?', [$minSupport])
            ->havingRaw('confidence_a_to_b >= ? OR confidence_b_to_a >= ?', [$minConfidence, $minConfidence])
            ->orderBy('lift', 'desc')
            ->limit(50)
            ->get();
            
        return $basketAnalysis;
    }
    
    /**
     * Product velocity analysis - how quickly products sell
     */
    public static function productVelocityAnalysis($categoryFilter = null)
    {
        $query = DB::connection('duckdb')->query()
            ->withCte('daily_sales', function($query) use ($categoryFilter) {
                $subQuery = $query->from('products as p')
                    ->join('order_items as oi', 'p.product_id', '=', 'oi.product_id')
                    ->join('orders as o', 'oi.order_id', '=', 'o.order_id')
                    ->where('o.status', 'completed')
                    ->where('p.is_active', true)
                    ->selectRaw('p.product_id')
                    ->selectRaw('p.name')
                    ->selectRaw('p.category')
                    ->selectRaw('p.price')
                    ->selectRaw("DATE_TRUNC('day', o.order_date) as sale_date")
                    ->selectRaw('SUM(oi.quantity) as daily_quantity');
                    
                if ($categoryFilter) {
                    $subQuery->where('p.category', $categoryFilter);
                }
                
                $subQuery->groupBy('p.product_id', 'p.name', 'p.category', 'p.price', 'sale_date');
            })
            ->withCte('velocity_metrics', function($query) {
                $query->from('daily_sales')
                    ->selectRaw('product_id')
                    ->selectRaw('name')
                    ->selectRaw('category')
                    ->selectRaw('price')
                    ->selectRaw('COUNT(DISTINCT sale_date) as days_with_sales')
                    ->selectRaw('SUM(daily_quantity) as total_quantity')
                    ->selectRaw('AVG(daily_quantity) as avg_daily_quantity')
                    ->selectRaw('STDDEV(daily_quantity) as stddev_daily_quantity')
                    ->selectRaw('MAX(daily_quantity) as max_daily_quantity')
                    ->selectRaw("DATEDIFF('day', MIN(sale_date), MAX(sale_date)) + 1 as days_in_period")
                    ->groupBy('product_id', 'name', 'category', 'price');
            })
            ->from('velocity_metrics')
            ->selectRaw('*')
            ->selectRaw('days_with_sales::FLOAT / days_in_period as consistency_score')
            ->selectRaw('total_quantity / days_in_period as velocity_score')
            ->selectRaw('CASE 
                WHEN stddev_daily_quantity = 0 OR avg_daily_quantity = 0 THEN 0 
                ELSE stddev_daily_quantity / avg_daily_quantity 
            END as volatility_score')
            ->orderBy('velocity_score', 'desc')
            ->get();
            
        return $query;
    }
    
    /**
     * Price optimization analysis
     */
    public static function priceOptimizationAnalysis($categoryFilter = null)
    {
        $query = DB::connection('duckdb')->query()
            ->withCte('price_performance', function($query) use ($categoryFilter) {
                $subQuery = $query->from('products as p')
                    ->join('order_items as oi', 'p.product_id', '=', 'oi.product_id')
                    ->join('orders as o', 'oi.order_id', '=', 'o.order_id')
                    ->where('o.status', 'completed')
                    ->selectRaw('p.product_id')
                    ->selectRaw('p.name')
                    ->selectRaw('p.category')
                    ->selectRaw('p.price as list_price')
                    ->selectRaw('p.cost')
                    ->selectRaw('AVG(oi.unit_price) as avg_selling_price')
                    ->selectRaw('AVG(oi.discount) as avg_discount')
                    ->selectRaw('COUNT(DISTINCT o.order_id) as order_count')
                    ->selectRaw('SUM(oi.quantity) as units_sold')
                    ->selectRaw('SUM(oi.total_price) as revenue')
                    ->selectRaw('SUM((oi.unit_price - p.cost) * oi.quantity) as gross_profit');
                    
                if ($categoryFilter) {
                    $subQuery->where('p.category', $categoryFilter);
                }
                
                $subQuery->groupBy('p.product_id', 'p.name', 'p.category', 'p.price', 'p.cost');
            })
            ->withCte('price_segments', function($query) {
                $query->from('price_performance')
                    ->selectRaw('category')
                    ->selectRaw('NTILE(5) OVER (PARTITION BY category ORDER BY list_price) as price_quintile')
                    ->selectRaw('product_id')
                    ->selectRaw('list_price');
            })
            ->from('price_performance as pp')
            ->join('price_segments as ps', 'pp.product_id', '=', 'ps.product_id')
            ->selectRaw('pp.*')
            ->selectRaw('ps.price_quintile')
            ->selectRaw('pp.avg_selling_price / pp.list_price as price_realization')
            ->selectRaw('pp.gross_profit / pp.revenue as gross_margin')
            ->selectRaw('pp.revenue / pp.units_sold as revenue_per_unit')
            ->selectRaw('pp.gross_profit / pp.units_sold as profit_per_unit')
            ->orderBy('pp.category')
            ->orderBy('ps.price_quintile')
            ->orderBy('pp.revenue', 'desc')
            ->get();
            
        // Aggregate by price quintile for insights
        $quintileAnalysis = collect($query)->groupBy(['category', 'price_quintile'])
            ->map(function ($categoryGroup) {
                return $categoryGroup->map(function ($quintileGroup) {
                    return [
                        'product_count' => $quintileGroup->count(),
                        'avg_price' => round($quintileGroup->avg('list_price'), 2),
                        'avg_units_sold' => round($quintileGroup->avg('units_sold'), 0),
                        'total_revenue' => round($quintileGroup->sum('revenue'), 2),
                        'avg_gross_margin' => round($quintileGroup->avg('gross_margin') * 100, 2) . '%',
                        'avg_discount_rate' => round($quintileGroup->avg('avg_discount') / $quintileGroup->avg('list_price') * 100, 2) . '%'
                    ];
                });
            });
            
        return [
            'detailed_analysis' => $query,
            'quintile_summary' => $quintileAnalysis
        ];
    }
    
    /**
     * Product lifecycle analysis
     */
    public static function productLifecycleAnalysis($productId = null)
    {
        $query = Product::performanceTimeSeries($productId, 'week');
        
        if ($productId) {
            // Single product detailed lifecycle
            $lifecycleData = $query->get();
            
            // Calculate lifecycle stages
            $enrichedData = DB::connection('duckdb')->query()
                ->withCte('product_timeline', function($query) use ($lifecycleData) {
                    $query->fromRaw("({$lifecycleData->toQuery()->toSql()}) as pt", $lifecycleData->toQuery()->getBindings());
                })
                ->from('product_timeline')
                ->selectRaw('*')
                ->selectRaw('SUM(revenue) OVER (ORDER BY period) as cumulative_revenue')
                ->selectRaw('AVG(revenue) OVER (ORDER BY period ROWS BETWEEN 3 PRECEDING AND CURRENT ROW) as ma4_revenue')
                ->selectRaw('LAG(revenue, 4) OVER (ORDER BY period) as revenue_4_weeks_ago')
                ->selectRaw('CASE 
                    WHEN revenue_4_weeks_ago IS NULL OR revenue_4_weeks_ago = 0 THEN NULL
                    ELSE (revenue - revenue_4_weeks_ago) / revenue_4_weeks_ago * 100
                END as growth_rate_4w')
                ->orderBy('period')
                ->get();
                
            return $enrichedData;
        } else {
            // Multiple products summary
            return $query->limit(500)->get();
        }
    }
}