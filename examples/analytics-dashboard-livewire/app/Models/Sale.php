<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesFiles;
use Laraduck\EloquentDuckDB\Eloquent\Traits\SupportsAdvancedQueries;

class Sale extends AnalyticalModel
{
    use HasFactory, QueriesFiles, SupportsAdvancedQueries;

    protected $connection = 'analytics';

    protected $fillable = [
        'product_id',
        'customer_id',
        'quantity',
        'unit_price',
        'total_amount',
        'sale_date',
        'region',
        'channel',
        'discount_amount',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    /**
     * Scope to get sales for a specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sale_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get sales by region
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Get daily sales aggregates
     */
    public function scopeDailySales($query)
    {
        return $query->select([
            'DATE_TRUNC(\'day\', sale_date) as date',
            'SUM(total_amount) as revenue',
            'COUNT(*) as transactions',
            'AVG(total_amount) as avg_transaction',
            'SUM(quantity) as units_sold',
        ])
            ->groupBy('date')
            ->orderBy('date');
    }

    /**
     * Get sales by region with aggregates
     */
    public function scopeRegionSales($query)
    {
        return $query->select([
            'region',
            'SUM(total_amount) as revenue',
            'COUNT(*) as transactions',
            'AVG(total_amount) as avg_transaction',
            'COUNT(DISTINCT customer_id) as unique_customers',
        ])
            ->groupBy('region')
            ->orderBy('revenue', 'desc');
    }

    /**
     * Get top performing products
     */
    public function scopeTopProducts($query, $limit = 10)
    {
        return $query->select([
            'product_id',
            'SUM(total_amount) as revenue',
            'SUM(quantity) as units_sold',
            'COUNT(*) as transactions',
        ])
            ->groupBy('product_id')
            ->orderBy('revenue', 'desc')
            ->limit($limit);
    }

    /**
     * Get sales trends with moving averages
     */
    public function scopeSalesTrends($query, $days = 7)
    {
        return $query->select([
            'sale_date',
            'SUM(total_amount) as daily_revenue',
            'AVG(SUM(total_amount)) OVER (ORDER BY sale_date ROWS BETWEEN ? PRECEDING AND CURRENT ROW) as moving_avg',
        ], [$days - 1])
            ->groupBy('sale_date')
            ->orderBy('sale_date');
    }
}
