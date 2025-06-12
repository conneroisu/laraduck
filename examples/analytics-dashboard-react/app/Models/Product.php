<?php

namespace App\Models;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends AnalyticalModel
{
    use HasFactory;

    protected $connection = 'duckdb';
    
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'category',
        'subcategory',
        'brand',
        'price',
        'cost',
        'margin',
        'weight',
        'dimensions',
        'stock_quantity',
        'reorder_point',
        'supplier',
        'launch_date',
        'discontinue_date',
        'is_active',
        'rating',
        'review_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'margin' => 'decimal:2',
        'weight' => 'float',
        'launch_date' => 'date',
        'discontinue_date' => 'date',
        'is_active' => 'boolean',
        'rating' => 'float',
    ];

    /**
     * Product performance analysis
     */
    public static function performanceAnalysis($startDate = null, $endDate = null)
    {
        $query = static::query()
            ->join('sales', 'products.product_id', '=', 'sales.product_id')
            ->select([
                'products.product_id',
                'products.name',
                'products.category',
                'products.brand',
                'products.price',
                'products.margin',
                \DB::raw('COUNT(DISTINCT sales.order_id) as orders_count'),
                \DB::raw('SUM(sales.quantity) as units_sold'),
                \DB::raw('SUM(sales.total_amount) as revenue'),
                \DB::raw('SUM(sales.total_amount * products.margin / 100) as profit'),
                \DB::raw('AVG(sales.discount_amount) as avg_discount'),
                \DB::raw('COUNT(DISTINCT sales.customer_id) as unique_customers'),
            ])
            ->groupBy([
                'products.product_id',
                'products.name',
                'products.category',
                'products.brand',
                'products.price',
                'products.margin',
            ]);

        if ($startDate && $endDate) {
            $query->whereBetween('sales.sale_date', [$startDate, $endDate]);
        }

        return $query;
    }

    /**
     * ABC analysis for inventory management
     */
    public static function abcAnalysis()
    {
        return static::query()
            ->withCte('product_revenue', function ($query) {
                return \DB::table('products')
                    ->join('sales', 'products.product_id', '=', 'sales.product_id')
                    ->select([
                        'products.product_id',
                        'products.name',
                        'products.category',
                        'products.stock_quantity',
                        'products.price',
                        \DB::raw('SUM(sales.total_amount) as total_revenue'),
                        \DB::raw('SUM(sales.quantity) as total_units'),
                    ])
                    ->groupBy([
                        'products.product_id',
                        'products.name',
                        'products.category',
                        'products.stock_quantity',
                        'products.price',
                    ]);
            })
            ->withCte('revenue_percentiles', function ($query) {
                return \DB::table('product_revenue')
                    ->select([
                        '*',
                        \DB::raw('SUM(total_revenue) OVER () as grand_total'),
                        \DB::raw('SUM(total_revenue) OVER (ORDER BY total_revenue DESC) as running_total'),
                    ]);
            })
            ->from('revenue_percentiles')
            ->select([
                '*',
                \DB::raw('(total_revenue / grand_total * 100) as revenue_percentage'),
                \DB::raw('(running_total / grand_total * 100) as cumulative_percentage'),
                \DB::raw("CASE 
                    WHEN (running_total / grand_total * 100) <= 80 THEN 'A'
                    WHEN (running_total / grand_total * 100) <= 95 THEN 'B'
                    ELSE 'C'
                END as abc_category"),
            ])
            ->orderBy('total_revenue', 'desc');
    }

    /**
     * Price elasticity analysis
     */
    public static function priceElasticity($priceChanges = [])
    {
        return static::query()
            ->withCte('price_periods', function ($query) use ($priceChanges) {
                // This would typically come from a price history table
                // For demo purposes, we'll use a simple CTE
                $values = [];
                foreach ($priceChanges as $productId => $changes) {
                    foreach ($changes as $change) {
                        $values[] = "({$productId}, '{$change['date']}', {$change['old_price']}, {$change['new_price']})";
                    }
                }
                
                return \DB::raw("(VALUES " . implode(', ', $values) . ") AS t(product_id, change_date, old_price, new_price)");
            })
            ->join('price_periods', 'products.product_id', '=', 'price_periods.product_id')
            ->join('sales as s1', function ($join) {
                $join->on('products.product_id', '=', 's1.product_id')
                     ->on('s1.sale_date', '<', 'price_periods.change_date')
                     ->on('s1.sale_date', '>=', \DB::raw("price_periods.change_date - INTERVAL '30 days'"));
            })
            ->join('sales as s2', function ($join) {
                $join->on('products.product_id', '=', 's2.product_id')
                     ->on('s2.sale_date', '>=', 'price_periods.change_date')
                     ->on('s2.sale_date', '<', \DB::raw("price_periods.change_date + INTERVAL '30 days'"));
            })
            ->select([
                'products.product_id',
                'products.name',
                'price_periods.old_price',
                'price_periods.new_price',
                \DB::raw('(new_price - old_price) / old_price * 100 as price_change_percent'),
                \DB::raw('COUNT(DISTINCT s1.order_id) as orders_before'),
                \DB::raw('COUNT(DISTINCT s2.order_id) as orders_after'),
                \DB::raw('SUM(s1.quantity) as quantity_before'),
                \DB::raw('SUM(s2.quantity) as quantity_after'),
                \DB::raw('((SUM(s2.quantity) - SUM(s1.quantity)) / SUM(s1.quantity) * 100) / ((new_price - old_price) / old_price * 100) as elasticity'),
            ])
            ->groupBy([
                'products.product_id',
                'products.name',
                'price_periods.old_price',
                'price_periods.new_price',
            ]);
    }

    /**
     * Cross-sell analysis
     */
    public static function crossSellAnalysis($productId, $limit = 10)
    {
        return static::query()
            ->withCte('orders_with_product', function ($query) use ($productId) {
                return \DB::table('sales')
                    ->select('order_id')
                    ->where('product_id', $productId)
                    ->distinct();
            })
            ->join('sales', 'products.product_id', '=', 'sales.product_id')
            ->join('orders_with_product', 'sales.order_id', '=', 'orders_with_product.order_id')
            ->where('products.product_id', '!=', $productId)
            ->select([
                'products.product_id',
                'products.name',
                'products.category',
                'products.price',
                \DB::raw('COUNT(DISTINCT sales.order_id) as co_occurrence_count'),
                \DB::raw('SUM(sales.quantity) as total_quantity'),
                \DB::raw('SUM(sales.total_amount) as total_revenue'),
            ])
            ->groupBy([
                'products.product_id',
                'products.name',
                'products.category',
                'products.price',
            ])
            ->orderBy('co_occurrence_count', 'desc')
            ->limit($limit);
    }

    /**
     * Inventory turnover analysis
     */
    public static function inventoryTurnover($days = 30)
    {
        return static::query()
            ->join('sales', 'products.product_id', '=', 'sales.product_id')
            ->where('sales.sale_date', '>=', \DB::raw("CURRENT_DATE - INTERVAL '$days days'"))
            ->select([
                'products.product_id',
                'products.name',
                'products.category',
                'products.stock_quantity',
                'products.cost',
                \DB::raw('SUM(sales.quantity) as units_sold'),
                \DB::raw("SUM(sales.quantity) * 365.0 / $days as annual_units_projection"),
                \DB::raw("CASE 
                    WHEN products.stock_quantity > 0 
                    THEN (SUM(sales.quantity) * 365.0 / $days) / products.stock_quantity 
                    ELSE NULL 
                END as turnover_rate"),
                \DB::raw("CASE 
                    WHEN SUM(sales.quantity) > 0 
                    THEN products.stock_quantity / (SUM(sales.quantity) / $days) 
                    ELSE NULL 
                END as days_of_supply"),
            ])
            ->groupBy([
                'products.product_id',
                'products.name',
                'products.category',
                'products.stock_quantity',
                'products.cost',
            ])
            ->orderBy('turnover_rate', 'desc');
    }
}