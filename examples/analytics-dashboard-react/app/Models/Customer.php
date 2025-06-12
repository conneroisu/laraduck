<?php

namespace App\Models;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends AnalyticalModel
{
    use HasFactory;

    protected $connection = 'duckdb';
    
    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'phone',
        'segment',
        'lifetime_value',
        'first_purchase_date',
        'last_purchase_date',
        'total_orders',
        'total_spent',
        'avg_order_value',
        'preferred_category',
        'preferred_brand',
        'city',
        'state',
        'country',
        'acquisition_channel',
        'churn_risk_score',
    ];

    protected $casts = [
        'first_purchase_date' => 'date',
        'last_purchase_date' => 'date',
        'lifetime_value' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'churn_risk_score' => 'float',
    ];

    /**
     * RFM (Recency, Frequency, Monetary) Analysis
     */
    public static function rfmAnalysis()
    {
        return static::query()
            ->withCte('rfm_data', function ($query) {
                return \DB::table('customers')
                    ->select([
                        'customer_id',
                        'name',
                        'email',
                        'segment',
                        \DB::raw("DATE_DIFF('day', last_purchase_date, CURRENT_DATE) as recency"),
                        'total_orders as frequency',
                        'total_spent as monetary',
                    ]);
            })
            ->withCte('rfm_scores', function ($query) {
                return \DB::table('rfm_data')
                    ->select([
                        '*',
                        \DB::raw('NTILE(5) OVER (ORDER BY recency DESC) as r_score'),
                        \DB::raw('NTILE(5) OVER (ORDER BY frequency ASC) as f_score'),
                        \DB::raw('NTILE(5) OVER (ORDER BY monetary ASC) as m_score'),
                    ]);
            })
            ->from('rfm_scores')
            ->select([
                '*',
                \DB::raw('CONCAT(r_score::VARCHAR, f_score::VARCHAR, m_score::VARCHAR) as rfm_score'),
                \DB::raw('(r_score + f_score + m_score) / 3.0 as rfm_avg'),
                \DB::raw("CASE 
                    WHEN r_score >= 4 AND f_score >= 4 AND m_score >= 4 THEN 'Champions'
                    WHEN r_score >= 3 AND f_score >= 3 AND m_score >= 4 THEN 'Loyal Customers'
                    WHEN r_score >= 3 AND f_score <= 2 AND m_score >= 3 THEN 'Potential Loyalists'
                    WHEN r_score >= 4 AND f_score <= 2 THEN 'New Customers'
                    WHEN r_score <= 2 AND f_score >= 3 THEN 'At Risk'
                    WHEN r_score <= 2 AND f_score <= 2 AND m_score >= 3 THEN 'Cant Lose Them'
                    ELSE 'Lost'
                END as rfm_segment"),
            ]);
    }

    /**
     * Customer lifetime value prediction
     */
    public static function clvPrediction($months = 12)
    {
        return static::query()
            ->select([
                'customer_id',
                'name',
                'segment',
                'lifetime_value',
                'avg_order_value',
                'total_orders',
                \DB::raw("DATE_DIFF('month', first_purchase_date, last_purchase_date) as customer_age_months"),
                \DB::raw("total_orders::FLOAT / GREATEST(DATE_DIFF('month', first_purchase_date, last_purchase_date), 1) as orders_per_month"),
                \DB::raw("(total_orders::FLOAT / GREATEST(DATE_DIFF('month', first_purchase_date, last_purchase_date), 1)) * avg_order_value * $months as predicted_value_next_{$months}m"),
            ])
            ->where('total_orders', '>', 0);
    }

    /**
     * Churn analysis
     */
    public static function churnAnalysis($daysThreshold = 90)
    {
        return static::query()
            ->select([
                \DB::raw("DATE_DIFF('day', last_purchase_date, CURRENT_DATE) > $daysThreshold as is_churned"),
                'segment',
                \DB::raw('COUNT(*) as customer_count'),
                \DB::raw('AVG(lifetime_value) as avg_ltv'),
                \DB::raw('AVG(total_orders) as avg_orders'),
                \DB::raw('AVG(churn_risk_score) as avg_risk_score'),
            ])
            ->groupBy(['is_churned', 'segment'])
            ->orderBy('is_churned')
            ->orderBy('customer_count', 'desc');
    }

    /**
     * Customer segmentation with statistics
     */
    public static function segmentationStats()
    {
        return static::query()
            ->select([
                'segment',
                \DB::raw('COUNT(*) as customer_count'),
                \DB::raw('SUM(lifetime_value) as total_ltv'),
                \DB::raw('AVG(lifetime_value) as avg_ltv'),
                \DB::raw('MEDIAN(lifetime_value) as median_ltv'),
                \DB::raw('STDDEV(lifetime_value) as stddev_ltv'),
                \DB::raw('MIN(lifetime_value) as min_ltv'),
                \DB::raw('MAX(lifetime_value) as max_ltv'),
                \DB::raw('AVG(total_orders) as avg_orders'),
                \DB::raw('AVG(avg_order_value) as avg_order_value'),
            ])
            ->groupBy('segment')
            ->orderBy('total_ltv', 'desc');
    }

    /**
     * Geographic distribution
     */
    public static function geographicDistribution($level = 'country')
    {
        $groupBy = match($level) {
            'city' => ['country', 'state', 'city'],
            'state' => ['country', 'state'],
            default => ['country']
        };

        return static::query()
            ->select(array_merge(
                $groupBy,
                [
                    \DB::raw('COUNT(*) as customer_count'),
                    \DB::raw('SUM(lifetime_value) as total_ltv'),
                    \DB::raw('AVG(lifetime_value) as avg_ltv'),
                    \DB::raw('AVG(total_orders) as avg_orders'),
                ]
            ))
            ->groupBy($groupBy)
            ->orderBy('customer_count', 'desc');
    }

    /**
     * Acquisition channel performance
     */
    public static function acquisitionChannelPerformance()
    {
        return static::query()
            ->select([
                'acquisition_channel',
                \DB::raw('COUNT(*) as customers_acquired'),
                \DB::raw('AVG(lifetime_value) as avg_ltv'),
                \DB::raw('SUM(lifetime_value) as total_ltv'),
                \DB::raw('AVG(total_orders) as avg_orders'),
                \DB::raw('AVG(avg_order_value) as avg_order_value'),
                \DB::raw("AVG(DATE_DIFF('day', first_purchase_date, last_purchase_date)) as avg_customer_lifespan_days"),
            ])
            ->groupBy('acquisition_channel')
            ->orderBy('total_ltv', 'desc');
    }
}