<?php

namespace App\Models;

use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesFiles;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesDataFiles;
use Laraduck\EloquentDuckDB\Eloquent\Traits\SupportsAdvancedQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends AnalyticalModel
{
    use HasFactory;

    protected $connection = 'duckdb';
    
    protected $fillable = [
        'order_id',
        'product_id',
        'customer_id',
        'quantity',
        'unit_price',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'category',
        'brand',
        'region',
        'country',
        'city',
        'sale_date',
        'sale_time',
        'payment_method',
        'shipping_method',
        'customer_segment',
        'is_returned',
        'return_reason',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'sale_time' => 'datetime',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'is_returned' => 'boolean',
    ];

    /**
     * Scope for date range filtering
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sale_date', [$startDate, $endDate]);
    }

    /**
     * Scope for region filtering
     */
    public function scopeRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Get sales summary by period
     */
    public static function summarizeByPeriod($period = 'day', $startDate = null, $endDate = null)
    {
        $dateFormat = match($period) {
            'hour' => "DATE_TRUNC('hour', sale_time)",
            'day' => "DATE_TRUNC('day', sale_date)",
            'week' => "DATE_TRUNC('week', sale_date)",
            'month' => "DATE_TRUNC('month', sale_date)",
            'quarter' => "DATE_TRUNC('quarter', sale_date)",
            'year' => "DATE_TRUNC('year', sale_date)",
            default => "DATE_TRUNC('day', sale_date)"
        };

        $query = static::query()
            ->select([
                \DB::raw("$dateFormat as period"),
                \DB::raw('COUNT(DISTINCT id) as total_orders'),
                \DB::raw('COUNT(*) as total_items'),
                \DB::raw('SUM(quantity) as total_quantity'),
                \DB::raw('SUM(revenue) as revenue'),
                \DB::raw('SUM(0) as total_discount'), // No discount column in our schema
                \DB::raw('SUM(0) as total_tax'), // No tax column in our schema
                \DB::raw('AVG(revenue) as avg_order_value'),
                \DB::raw('COUNT(DISTINCT customer_id) as unique_customers'),
            ])
            ->groupBy('period')
            ->orderBy('period');

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query;
    }

    /**
     * Get top products by revenue
     */
    public static function topProducts($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::query()
            ->select([
                'product_id',
                'category',
                'brand',
                \DB::raw('COUNT(*) as units_sold'),
                \DB::raw('SUM(revenue) as revenue'),
                \DB::raw('AVG(price) as avg_price'),
                \DB::raw('SUM(0) as total_discount'), // No discount column in our schema
            ])
            ->groupBy(['product_id', 'category', 'brand'])
            ->orderBy('revenue', 'desc')
            ->limit($limit);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query;
    }

    /**
     * Get sales by region with window functions
     */
    public static function salesByRegionWithRank()
    {
        return static::query()
            ->window('ranked', function ($window) {
                $window->partitionBy('region')
                       ->orderBy('revenue', 'desc');
            })
            ->select([
                '*',
                \DB::raw('ROW_NUMBER() OVER ranked as rank_in_region'),
                \DB::raw('PERCENT_RANK() OVER ranked as percentile_in_region'),
                \DB::raw('SUM(revenue) OVER (PARTITION BY region) as region_total'),
                \DB::raw('AVG(revenue) OVER (PARTITION BY region) as region_avg'),
            ]);
    }

    /**
     * Customer cohort analysis
     */
    public static function cohortAnalysis($cohortPeriod = 'month')
    {
        $cohortFormat = match($cohortPeriod) {
            'week' => "DATE_TRUNC('week', first_purchase)",
            'month' => "DATE_TRUNC('month', first_purchase)",
            'quarter' => "DATE_TRUNC('quarter', first_purchase)",
            default => "DATE_TRUNC('month', first_purchase)"
        };

        return static::query()
            ->withCte('customer_cohorts', function ($query) use ($cohortFormat) {
                return \DB::table('sales')
                    ->select([
                        'customer_id',
                        \DB::raw("MIN(sale_date) as first_purchase"),
                        \DB::raw("$cohortFormat as cohort"),
                    ])
                    ->groupBy('customer_id');
            })
            ->from('sales')
            ->join('customer_cohorts', 'sales.customer_id', '=', 'customer_cohorts.customer_id')
            ->select([
                'customer_cohorts.cohort',
                \DB::raw("DATE_DIFF('month', customer_cohorts.first_purchase, sales.sale_date) as months_since_first"),
                \DB::raw('COUNT(DISTINCT sales.customer_id) as customers'),
                \DB::raw('SUM(sales.revenue) as revenue'),
                \DB::raw('COUNT(sales.id) as orders'),
            ])
            ->groupBy(['customer_cohorts.cohort', 'months_since_first'])
            ->orderBy('customer_cohorts.cohort')
            ->orderBy('months_since_first');
    }

    /**
     * Moving averages for trend analysis
     */
    public static function movingAverages($days = 7)
    {
        return static::query()
            ->window('moving', function ($window) use ($days) {
                $window->orderBy('sale_date')
                       ->rows()
                       ->preceding($days - 1)
                       ->following(0);
            })
            ->select([
                'sale_date',
                \DB::raw('SUM(revenue) as daily_revenue'),
                \DB::raw("AVG(SUM(revenue)) OVER moving as moving_avg_{$days}d"),
                \DB::raw('COUNT(DISTINCT customer_id) as daily_customers'),
                \DB::raw("AVG(COUNT(DISTINCT customer_id)) OVER moving as moving_avg_customers_{$days}d"),
            ])
            ->groupBy('sale_date')
            ->orderBy('sale_date');
    }

    /**
     * Export sales data to Parquet
     */
    public static function exportToParquet($filename, $startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->toParquet($filename);
    }

    /**
     * Import sales from CSV
     */
    public static function importFromCsv($filename, $options = [])
    {
        return static::fromCsv($filename, array_merge([
            'header' => true,
            'delimiter' => ',',
            'quote' => '"',
        ], $options));
    }

    /**
     * Pivot analysis by dimensions
     */
    public static function pivotAnalysis($rows = ['category'], $columns = ['region'], $values = 'total_amount', $aggregation = 'sum')
    {
        return static::query()
            ->pivot($columns, $values, $aggregation)
            ->groupBy($rows)
            ->orderBy($rows[0]);
    }
}