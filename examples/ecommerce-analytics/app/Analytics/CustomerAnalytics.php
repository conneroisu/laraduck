<?php

namespace App\Analytics;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CustomerAnalytics
{
    /**
     * Customer segmentation analysis
     */
    public static function customerSegmentation()
    {
        // RFM-based segmentation
        $rfmData = Customer::rfmAnalysis()->get();
        
        // Define segment rules
        $segments = $rfmData->map(function ($customer) {
            $rfm = $customer->rfm_segment;
            
            // Champions: Bought recently, buy often, spend the most
            if (in_array($rfm, ['555', '554', '544', '545', '454', '455', '445'])) {
                return array_merge($customer->toArray(), ['segment' => 'Champions']);
            }
            
            // Loyal Customers: Spend good money, responsive to promotions
            if (in_array($rfm, ['543', '444', '435', '355', '354', '345', '344', '335'])) {
                return array_merge($customer->toArray(), ['segment' => 'Loyal Customers']);
            }
            
            // Potential Loyalists: Recent customers, spent good amount, bought more than once
            if (in_array($rfm, ['553', '551', '552', '541', '542', '533', '532', '531', '451', '452'])) {
                return array_merge($customer->toArray(), ['segment' => 'Potential Loyalists']);
            }
            
            // New Customers: Bought recently, but not often
            if (in_array($rfm, ['512', '511', '422', '421', '412', '411', '311'])) {
                return array_merge($customer->toArray(), ['segment' => 'New Customers']);
            }
            
            // At Risk: Spent big money, purchased often but long time ago
            if (in_array($rfm, ['155', '154', '144', '145', '254', '255', '245'])) {
                return array_merge($customer->toArray(), ['segment' => 'At Risk']);
            }
            
            // Can't Lose Them: Made big purchases and often, but haven't returned
            if (in_array($rfm, ['155', '154', '144', '145', '255', '254', '245', '244'])) {
                return array_merge($customer->toArray(), ['segment' => "Can't Lose Them"]);
            }
            
            // Hibernating: Low spenders, low frequency, purchased long time ago
            if (in_array($rfm, ['332', '322', '231', '241', '221', '213', '231', '211', '222'])) {
                return array_merge($customer->toArray(), ['segment' => 'Hibernating']);
            }
            
            // Lost: Lowest recency, frequency and monetary scores
            if (in_array($rfm, ['111', '112', '121', '122', '123', '132', '133'])) {
                return array_merge($customer->toArray(), ['segment' => 'Lost']);
            }
            
            return array_merge($customer->toArray(), ['segment' => 'Other']);
        });
        
        // Aggregate by segment
        $segmentSummary = collect($segments)->groupBy('segment')->map(function ($group) {
            return [
                'count' => $group->count(),
                'avg_recency' => round($group->avg('recency'), 1),
                'avg_frequency' => round($group->avg('frequency'), 1),
                'avg_monetary' => round($group->avg('monetary'), 2),
                'total_value' => round($group->sum('monetary'), 2),
            ];
        });
        
        return [
            'segments' => $segmentSummary,
            'customers' => $segments
        ];
    }
    
    /**
     * Customer lifetime value analysis
     */
    public static function lifetimeValueAnalysis($cohortSize = 'month')
    {
        return DB::connection('duckdb')->query()
            ->withCte('customer_cohorts', function($query) use ($cohortSize) {
                $query->from('customers as c')
                    ->join('orders as o', 'c.customer_id', '=', 'o.customer_id')
                    ->where('o.status', 'completed')
                    ->selectRaw('c.customer_id')
                    ->selectRaw("DATE_TRUNC(?, c.registration_date) as cohort", [$cohortSize])
                    ->selectRaw("MIN(o.order_date) as first_order_date")
                    ->groupBy('c.customer_id', 'cohort');
            })
            ->withCte('cohort_revenue', function($query) use ($cohortSize) {
                $query->from('customer_cohorts as cc')
                    ->join('orders as o', 'cc.customer_id', '=', 'o.customer_id')
                    ->where('o.status', 'completed')
                    ->selectRaw('cc.cohort')
                    ->selectRaw("DATEDIFF(?, o.order_date, cc.first_order_date) + 1 as period_number", [$cohortSize])
                    ->selectRaw('COUNT(DISTINCT cc.customer_id) as active_customers')
                    ->selectRaw('SUM(o.total_amount) as period_revenue')
                    ->groupBy('cc.cohort', 'period_number');
            })
            ->withCte('cohort_sizes', function($query) {
                $query->from('customer_cohorts')
                    ->selectRaw('cohort')
                    ->selectRaw('COUNT(DISTINCT customer_id) as cohort_size')
                    ->groupBy('cohort');
            })
            ->from('cohort_revenue as cr')
            ->join('cohort_sizes as cs', 'cr.cohort', '=', 'cs.cohort')
            ->selectRaw('cr.cohort')
            ->selectRaw('cr.period_number')
            ->selectRaw('cs.cohort_size')
            ->selectRaw('cr.active_customers')
            ->selectRaw('cr.period_revenue')
            ->selectRaw('cr.period_revenue / cs.cohort_size as revenue_per_customer')
            ->selectRaw('SUM(cr.period_revenue) OVER (PARTITION BY cr.cohort ORDER BY cr.period_number) as cumulative_revenue')
            ->selectRaw('SUM(cr.period_revenue) OVER (PARTITION BY cr.cohort ORDER BY cr.period_number) / cs.cohort_size as cumulative_ltv')
            ->orderBy('cr.cohort')
            ->orderBy('cr.period_number')
            ->get();
    }
    
    /**
     * Customer retention analysis
     */
    public static function retentionAnalysis($cohortSize = 'month', $periods = 12)
    {
        return DB::connection('duckdb')->query()
            ->withCte('user_cohorts', function($query) use ($cohortSize) {
                $query->from('customers as c')
                    ->join(DB::raw('(SELECT customer_id, MIN(order_date) as first_order_date FROM orders GROUP BY customer_id) as fo'), 
                           'c.customer_id', '=', 'fo.customer_id')
                    ->selectRaw('c.customer_id')
                    ->selectRaw("DATE_TRUNC(?, fo.first_order_date) as cohort_month", [$cohortSize]);
            })
            ->withCte('user_activities', function($query) use ($cohortSize) {
                $query->from('user_cohorts as uc')
                    ->join('orders as o', 'uc.customer_id', '=', 'o.customer_id')
                    ->where('o.status', 'completed')
                    ->selectRaw('uc.cohort_month')
                    ->selectRaw("DATE_TRUNC(?, o.order_date) as activity_month", [$cohortSize])
                    ->selectRaw('uc.customer_id')
                    ->distinct();
            })
            ->withCte('cohort_sizes', function($query) {
                $query->from('user_cohorts')
                    ->selectRaw('cohort_month')
                    ->selectRaw('COUNT(DISTINCT customer_id) as cohort_size')
                    ->groupBy('cohort_month');
            })
            ->withCte('retention_data', function($query) use ($cohortSize) {
                $query->from('user_activities as ua')
                    ->join('cohort_sizes as cs', 'ua.cohort_month', '=', 'cs.cohort_month')
                    ->selectRaw('ua.cohort_month')
                    ->selectRaw("DATEDIFF(?, ua.activity_month, ua.cohort_month) as months_since_first", [$cohortSize])
                    ->selectRaw('cs.cohort_size')
                    ->selectRaw('COUNT(DISTINCT ua.customer_id) as retained_customers')
                    ->groupBy('ua.cohort_month', 'months_since_first', 'cs.cohort_size');
            })
            ->from('retention_data')
            ->where('months_since_first', '<=', $periods)
            ->selectRaw('cohort_month')
            ->selectRaw('months_since_first')
            ->selectRaw('cohort_size')
            ->selectRaw('retained_customers')
            ->selectRaw('ROUND(retained_customers * 100.0 / cohort_size, 2) as retention_rate')
            ->orderBy('cohort_month')
            ->orderBy('months_since_first')
            ->get();
    }
    
    /**
     * Churn prediction analysis
     */
    public static function churnPrediction($daysThreshold = 90)
    {
        $churnData = Customer::churnIndicators($daysThreshold)->get();
        
        // Calculate churn risk scores
        $riskScores = DB::connection('duckdb')->query()
            ->withCte('customer_metrics', function($query) use ($daysThreshold) {
                $query->fromRaw("({$churnData->toQuery()->toSql()}) as cm", $churnData->toQuery()->getBindings())
                    ->selectRaw('*')
                    ->selectRaw('NTILE(5) OVER (ORDER BY days_since_last_order DESC) as recency_score')
                    ->selectRaw('NTILE(5) OVER (ORDER BY total_orders) as frequency_score')
                    ->selectRaw('NTILE(5) OVER (ORDER BY avg_order_value) as monetary_score');
            })
            ->from('customer_metrics')
            ->selectRaw('customer_id')
            ->selectRaw('customer_segment')
            ->selectRaw('days_since_last_order')
            ->selectRaw('total_orders')
            ->selectRaw('avg_order_value')
            ->selectRaw('is_churned')
            ->selectRaw('recency_score')
            ->selectRaw('frequency_score')
            ->selectRaw('monetary_score')
            ->selectRaw('(recency_score * 0.5 + (6 - frequency_score) * 0.3 + (6 - monetary_score) * 0.2) as churn_risk_score')
            ->orderBy('churn_risk_score', 'desc')
            ->get();
            
        // Group by risk levels
        $riskLevels = $riskScores->map(function ($customer) {
            $riskLevel = match(true) {
                $customer->churn_risk_score >= 4 => 'High Risk',
                $customer->churn_risk_score >= 3 => 'Medium Risk',
                $customer->churn_risk_score >= 2 => 'Low Risk',
                default => 'No Risk'
            };
            
            return array_merge($customer->toArray(), ['risk_level' => $riskLevel]);
        });
        
        $summary = collect($riskLevels)->groupBy('risk_level')->map(function ($group) {
            return [
                'count' => $group->count(),
                'avg_days_since_order' => round($group->avg('days_since_last_order'), 1),
                'avg_lifetime_orders' => round($group->avg('total_orders'), 1),
                'avg_order_value' => round($group->avg('avg_order_value'), 2),
                'churned_percentage' => round($group->where('is_churned', 1)->count() / $group->count() * 100, 2)
            ];
        });
        
        return [
            'summary' => $summary,
            'customers' => $riskLevels
        ];
    }
    
    /**
     * Customer acquisition cost and payback period
     */
    public static function customerAcquisitionMetrics($startDate = null, $endDate = null)
    {
        $dateFilter = [];
        if ($startDate && $endDate) {
            $dateFilter = [$startDate, $endDate];
        } else {
            $dateFilter = [now()->subYear(), now()];
        }
        
        // Get new customers and their revenue over time
        $acquisitionData = DB::connection('duckdb')->query()
            ->withCte('new_customers', function($query) use ($dateFilter) {
                $query->from('customers')
                    ->whereBetween('registration_date', $dateFilter)
                    ->selectRaw('customer_id')
                    ->selectRaw('registration_date')
                    ->selectRaw("DATE_TRUNC('month', registration_date) as acquisition_month");
            })
            ->withCte('customer_revenue', function($query) {
                $query->from('new_customers as nc')
                    ->leftJoin('orders as o', function($join) {
                        $join->on('nc.customer_id', '=', 'o.customer_id')
                             ->where('o.status', '=', 'completed');
                    })
                    ->selectRaw('nc.customer_id')
                    ->selectRaw('nc.acquisition_month')
                    ->selectRaw("DATEDIFF('month', nc.registration_date, o.order_date) as months_since_acquisition")
                    ->selectRaw('o.total_amount')
                    ->whereNotNull('o.order_id');
            })
            ->withCte('monthly_cohort_revenue', function($query) {
                $query->from('customer_revenue')
                    ->selectRaw('acquisition_month')
                    ->selectRaw('months_since_acquisition')
                    ->selectRaw('COUNT(DISTINCT customer_id) as active_customers')
                    ->selectRaw('SUM(total_amount) as revenue')
                    ->groupBy('acquisition_month', 'months_since_acquisition');
            })
            ->withCte('acquisition_costs', function($query) use ($dateFilter) {
                $query->from('marketing_campaigns')
                    ->whereBetween('start_date', $dateFilter)
                    ->selectRaw("DATE_TRUNC('month', start_date) as month")
                    ->selectRaw('SUM(actual_spend) as marketing_spend')
                    ->groupBy('month');
            })
            ->from('monthly_cohort_revenue as mcr')
            ->leftJoin('acquisition_costs as ac', 'mcr.acquisition_month', '=', 'ac.month')
            ->leftJoin(DB::raw("(SELECT acquisition_month, COUNT(*) as new_customers FROM new_customers GROUP BY acquisition_month) as nc_count"), 
                      'mcr.acquisition_month', '=', 'nc_count.acquisition_month')
            ->selectRaw('mcr.acquisition_month')
            ->selectRaw('mcr.months_since_acquisition')
            ->selectRaw('nc_count.new_customers')
            ->selectRaw('ac.marketing_spend')
            ->selectRaw('ac.marketing_spend / NULLIF(nc_count.new_customers, 0) as cac')
            ->selectRaw('mcr.revenue')
            ->selectRaw('mcr.revenue / NULLIF(nc_count.new_customers, 0) as revenue_per_customer')
            ->selectRaw('SUM(mcr.revenue) OVER (PARTITION BY mcr.acquisition_month ORDER BY mcr.months_since_acquisition) as cumulative_revenue')
            ->selectRaw('SUM(mcr.revenue) OVER (PARTITION BY mcr.acquisition_month ORDER BY mcr.months_since_acquisition) / NULLIF(nc_count.new_customers, 0) as cumulative_revenue_per_customer')
            ->orderBy('mcr.acquisition_month')
            ->orderBy('mcr.months_since_acquisition')
            ->get();
            
        return $acquisitionData;
    }
}