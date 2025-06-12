<?php

namespace App\Models;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;

class Order extends AnalyticalModel
{
    protected $connection = 'duckdb';
    protected $table = 'orders';
    protected $allowSingleRowOperations = true;
    public $timestamps = false;
    
    protected $fillable = [
        'order_id',
        'customer_id',
        'order_date',
        'status',
        'total_amount',
        'shipping_address',
        'payment_method',
        'region',
        'channel'
    ];
    
    protected $casts = [
        'order_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'shipping_address' => 'array'
    ];
    
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
    
    // Analytics scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }
    
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }
    
    // Revenue analytics
    public static function dailyRevenue($startDate = null, $endDate = null)
    {
        $query = static::query()
            ->selectRaw("DATE_TRUNC('day', order_date) as date")
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(total_amount) as revenue')
            ->selectRaw('AVG(total_amount) as avg_order_value')
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date');
            
        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }
        
        return $query;
    }
    
    // Cohort analysis
    public static function cohortRetention($cohortMonth)
    {
        return static::query()
            ->withCte('first_orders', function($query) {
                $query->from('orders')
                    ->selectRaw('customer_id')
                    ->selectRaw("MIN(DATE_TRUNC('month', order_date)) as cohort_month")
                    ->groupBy('customer_id');
            })
            ->withCte('customer_orders', function($query) {
                $query->from('orders')
                    ->join('first_orders', 'orders.customer_id', '=', 'first_orders.customer_id')
                    ->selectRaw('first_orders.cohort_month')
                    ->selectRaw("DATE_TRUNC('month', orders.order_date) as order_month")
                    ->selectRaw('COUNT(DISTINCT orders.customer_id) as customers');
            })
            ->from('customer_orders')
            ->where('cohort_month', $cohortMonth)
            ->selectRaw('cohort_month')
            ->selectRaw('order_month')
            ->selectRaw('customers')
            ->selectRaw("DATEDIFF('month', cohort_month, order_month) as months_since_first")
            ->orderBy('order_month');
    }
}