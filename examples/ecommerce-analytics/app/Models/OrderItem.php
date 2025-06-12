<?php

namespace App\Models;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;

class OrderItem extends AnalyticalModel
{
    protected $connection = 'duckdb';
    protected $table = 'order_items';
    protected $allowSingleRowOperations = true;
    public $timestamps = false;
    
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'tax',
        'total_price'
    ];
    
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
    
    // Product performance analytics
    public static function topSellingProducts($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::query()
            ->join('products', 'order_items.product_id', '=', 'products.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->where('orders.status', 'completed')
            ->selectRaw('products.product_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('products.category')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.total_price) as revenue')
            ->selectRaw('AVG(order_items.discount) as avg_discount')
            ->groupBy('products.product_id', 'products.name', 'products.category')
            ->orderBy('revenue', 'desc')
            ->limit($limit);
            
        if ($startDate && $endDate) {
            $query->whereBetween('orders.order_date', [$startDate, $endDate]);
        }
        
        return $query;
    }
    
    // Market basket analysis
    public static function frequentlyBoughtTogether($productId, $limit = 5)
    {
        return static::query()
            ->withCte('target_orders', function($query) use ($productId) {
                $query->from('order_items')
                    ->where('product_id', $productId)
                    ->select('order_id');
            })
            ->from('order_items as oi')
            ->join('target_orders as t', 'oi.order_id', '=', 't.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.product_id')
            ->where('oi.product_id', '!=', $productId)
            ->selectRaw('oi.product_id')
            ->selectRaw('p.name as product_name')
            ->selectRaw('COUNT(*) as frequency')
            ->selectRaw('AVG(oi.quantity) as avg_quantity')
            ->groupBy('oi.product_id', 'p.name')
            ->orderBy('frequency', 'desc')
            ->limit($limit);
    }
}