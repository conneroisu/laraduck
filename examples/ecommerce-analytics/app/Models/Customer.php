<?php

namespace App\Models;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;

class Customer extends AnalyticalModel
{
    protected $connection = 'duckdb';
    protected $table = 'customers';
    protected $allowSingleRowOperations = true;
    public $timestamps = false;
    
    protected $fillable = [
        'customer_id',
        'email',
        'first_name',
        'last_name',
        'registration_date',
        'country',
        'city',
        'age_group',
        'gender',
        'customer_segment'
    ];
    
    protected $casts = [
        'registration_date' => 'date'
    ];
    
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'customer_id');
    }
    
    // Customer lifetime value
    public static function calculateCLTV($lookbackDays = 365)
    {
        return static::query()
            ->join('orders', 'customers.customer_id', '=', 'orders.customer_id')
            ->where('orders.status', 'completed')
            ->where('orders.order_date', '>=', now()->subDays($lookbackDays))
            ->selectRaw('customers.customer_id')
            ->selectRaw('customers.email')
            ->selectRaw('customers.customer_segment')
            ->selectRaw('MIN(orders.order_date) as first_order_date')
            ->selectRaw('MAX(orders.order_date) as last_order_date')
            ->selectRaw('COUNT(DISTINCT orders.order_id) as order_count')
            ->selectRaw('SUM(orders.total_amount) as lifetime_value')
            ->selectRaw('AVG(orders.total_amount) as avg_order_value')
            ->selectRaw("DATEDIFF('day', MIN(orders.order_date), MAX(orders.order_date)) as customer_lifespan_days")
            ->groupBy('customers.customer_id', 'customers.email', 'customers.customer_segment')
            ->orderBy('lifetime_value', 'desc');
    }
    
    // RFM Analysis (Recency, Frequency, Monetary)
    public static function rfmAnalysis($referenceDate = null)
    {
        $referenceDate = $referenceDate ?? now();
        
        return static::query()
            ->withCte('customer_metrics', function($query) use ($referenceDate) {
                $query->from('customers')
                    ->leftJoin('orders', 'customers.customer_id', '=', 'orders.customer_id')
                    ->where('orders.status', 'completed')
                    ->selectRaw('customers.customer_id')
                    ->selectRaw("DATEDIFF('day', MAX(orders.order_date), ?::DATE) as recency", [$referenceDate])
                    ->selectRaw('COUNT(DISTINCT orders.order_id) as frequency')
                    ->selectRaw('SUM(orders.total_amount) as monetary')
                    ->groupBy('customers.customer_id');
            })
            ->withCte('rfm_scores', function($query) {
                $query->from('customer_metrics')
                    ->selectRaw('customer_id')
                    ->selectRaw('recency')
                    ->selectRaw('frequency') 
                    ->selectRaw('monetary')
                    ->selectRaw('NTILE(5) OVER (ORDER BY recency DESC) as r_score')
                    ->selectRaw('NTILE(5) OVER (ORDER BY frequency) as f_score')
                    ->selectRaw('NTILE(5) OVER (ORDER BY monetary) as m_score');
            })
            ->from('rfm_scores')
            ->join('customers', 'rfm_scores.customer_id', '=', 'customers.customer_id')
            ->selectRaw('customers.*')
            ->selectRaw('rfm_scores.recency')
            ->selectRaw('rfm_scores.frequency')
            ->selectRaw('rfm_scores.monetary')
            ->selectRaw('rfm_scores.r_score')
            ->selectRaw('rfm_scores.f_score')
            ->selectRaw('rfm_scores.m_score')
            ->selectRaw("CONCAT(r_score, f_score, m_score) as rfm_segment");
    }
    
    // Churn prediction data
    public static function churnIndicators($daysSinceLastOrder = 90)
    {
        return static::query()
            ->withCte('last_orders', function($query) {
                $query->from('orders')
                    ->where('status', 'completed')
                    ->selectRaw('customer_id')
                    ->selectRaw('MAX(order_date) as last_order_date')
                    ->groupBy('customer_id');
            })
            ->from('customers')
            ->leftJoin('last_orders', 'customers.customer_id', '=', 'last_orders.customer_id')
            ->leftJoin('orders', 'customers.customer_id', '=', 'orders.customer_id')
            ->where('orders.status', 'completed')
            ->selectRaw('customers.customer_id')
            ->selectRaw('customers.customer_segment')
            ->selectRaw('COUNT(DISTINCT orders.order_id) as total_orders')
            ->selectRaw('AVG(orders.total_amount) as avg_order_value')
            ->selectRaw("DATEDIFF('day', last_orders.last_order_date, CURRENT_DATE) as days_since_last_order")
            ->selectRaw("CASE WHEN DATEDIFF('day', last_orders.last_order_date, CURRENT_DATE) > ? THEN 1 ELSE 0 END as is_churned", [$daysSinceLastOrder])
            ->groupBy('customers.customer_id', 'customers.customer_segment', 'last_orders.last_order_date');
    }
}