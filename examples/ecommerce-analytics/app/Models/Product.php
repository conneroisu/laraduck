<?php

namespace App\Models;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;

class Product extends AnalyticalModel
{
    protected $connection = 'duckdb';
    protected $table = 'products';
    protected $allowSingleRowOperations = true;
    public $timestamps = false;
    
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'category',
        'subcategory',
        'brand',
        'price',
        'cost',
        'weight',
        'launch_date',
        'is_active'
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:2',
        'launch_date' => 'date',
        'is_active' => 'boolean'
    ];
    
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id', 'product_id');
    }
    
    // Product performance over time
    public static function performanceTimeSeries($productId = null, $granularity = 'week')
    {
        $query = static::query()
            ->join('order_items', 'products.product_id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->where('orders.status', 'completed')
            ->selectRaw("DATE_TRUNC(?, orders.order_date) as period", [$granularity])
            ->selectRaw('products.product_id')
            ->selectRaw('products.name')
            ->selectRaw('products.category')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.total_price) as revenue')
            ->selectRaw('COUNT(DISTINCT orders.order_id) as order_count')
            ->groupBy('period', 'products.product_id', 'products.name', 'products.category')
            ->orderBy('period');
            
        if ($productId) {
            $query->where('products.product_id', $productId);
        }
        
        return $query;
    }
    
    // ABC Analysis (Pareto principle)
    public static function abcAnalysis($startDate = null, $endDate = null)
    {
        return static::query()
            ->withCte('product_revenue', function($query) use ($startDate, $endDate) {
                $subQuery = $query->from('products')
                    ->join('order_items', 'products.product_id', '=', 'order_items.product_id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
                    ->where('orders.status', 'completed')
                    ->selectRaw('products.product_id')
                    ->selectRaw('products.name')
                    ->selectRaw('products.category')
                    ->selectRaw('SUM(order_items.total_price) as revenue')
                    ->groupBy('products.product_id', 'products.name', 'products.category');
                    
                if ($startDate && $endDate) {
                    $subQuery->whereBetween('orders.order_date', [$startDate, $endDate]);
                }
            })
            ->withCte('revenue_analysis', function($query) {
                $query->from('product_revenue')
                    ->selectRaw('*')
                    ->selectRaw('SUM(revenue) OVER () as total_revenue')
                    ->selectRaw('revenue / SUM(revenue) OVER () as revenue_percentage')
                    ->selectRaw('SUM(revenue / SUM(revenue) OVER ()) OVER (ORDER BY revenue DESC) as cumulative_percentage');
            })
            ->from('revenue_analysis')
            ->selectRaw('*')
            ->selectRaw("CASE 
                WHEN cumulative_percentage <= 0.8 THEN 'A'
                WHEN cumulative_percentage <= 0.95 THEN 'B'
                ELSE 'C'
            END as abc_category")
            ->orderBy('revenue', 'desc');
    }
    
    // Price elasticity analysis
    public static function priceElasticity($productId, $periods = 12)
    {
        return static::query()
            ->withCte('price_changes', function($query) use ($productId) {
                $query->fromRaw("(
                    SELECT 
                        DATE_TRUNC('month', order_date) as month,
                        AVG(unit_price) as avg_price,
                        SUM(quantity) as total_quantity
                    FROM order_items
                    JOIN orders ON order_items.order_id = orders.order_id
                    WHERE product_id = ? AND orders.status = 'completed'
                    GROUP BY month
                    ORDER BY month
                ) as price_data", [$productId]);
            })
            ->from('price_changes')
            ->selectRaw('month')
            ->selectRaw('avg_price')
            ->selectRaw('total_quantity')
            ->selectRaw('LAG(avg_price) OVER (ORDER BY month) as prev_price')
            ->selectRaw('LAG(total_quantity) OVER (ORDER BY month) as prev_quantity')
            ->selectRaw('(avg_price - LAG(avg_price) OVER (ORDER BY month)) / LAG(avg_price) OVER (ORDER BY month) as price_change_pct')
            ->selectRaw('(total_quantity - LAG(total_quantity) OVER (ORDER BY month)) / LAG(total_quantity) OVER (ORDER BY month) as quantity_change_pct')
            ->selectRaw('CASE 
                WHEN LAG(avg_price) OVER (ORDER BY month) IS NOT NULL 
                THEN ((total_quantity - LAG(total_quantity) OVER (ORDER BY month)) / LAG(total_quantity) OVER (ORDER BY month)) / 
                     ((avg_price - LAG(avg_price) OVER (ORDER BY month)) / LAG(avg_price) OVER (ORDER BY month))
                ELSE NULL 
            END as elasticity')
            ->limit($periods);
    }
}