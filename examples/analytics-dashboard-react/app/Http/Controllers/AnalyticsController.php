<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Sales Analytics Page
     */
    public function sales(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $period = $request->input('period', 'day');

        // Sales trend with different granularities
        $salesTrend = Sale::summarizeByPeriod($period, $startDate, $endDate)
            ->get()
            ->map(fn($item) => [
                'period' => Carbon::parse($item->period)->format($this->getPeriodFormat($period)),
                'revenue' => (float) $item->revenue,
                'orders' => $item->total_orders,
                'items' => $item->total_items,
                'avgOrderValue' => (float) $item->avg_order_value,
                'customers' => $item->unique_customers,
            ]);

        // Moving averages for smoothed trends
        $movingAverages = Sale::movingAverages(7)
            ->dateRange($startDate, $endDate)
            ->get()
            ->map(fn($item) => [
                'date' => $item->sale_date->format('Y-m-d'),
                'dailyRevenue' => (float) $item->daily_revenue,
                'movingAvg7d' => (float) $item->moving_avg_7d,
                'dailyCustomers' => $item->daily_customers,
                'movingAvgCustomers7d' => (float) $item->moving_avg_customers_7d,
            ]);

        // Sales by category and brand
        $categoryAnalysis = Sale::query()
            ->dateRange($startDate, $endDate)
            ->select([
                'category',
                'brand',
                \DB::raw('COUNT(DISTINCT order_id) as orders'),
                \DB::raw('SUM(total_amount) as revenue'),
                \DB::raw('SUM(quantity) as units_sold'),
                \DB::raw('AVG(discount_amount / total_amount * 100) as avg_discount_percent'),
            ])
            ->groupBy(['category', 'brand'])
            ->orderBy('revenue', 'desc')
            ->limit(20)
            ->get();

        // Hourly patterns
        $hourlyPatterns = Sale::query()
            ->dateRange($startDate, $endDate)
            ->select([
                \DB::raw("EXTRACT(HOUR FROM sale_time) as hour"),
                \DB::raw('COUNT(DISTINCT order_id) as orders'),
                \DB::raw('SUM(total_amount) as revenue'),
                \DB::raw('AVG(total_amount) as avg_order_value'),
            ])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn($item) => [
                'hour' => $item->hour,
                'orders' => $item->orders,
                'revenue' => (float) $item->revenue,
                'avgOrderValue' => (float) $item->avg_order_value,
            ]);

        // Payment method distribution
        $paymentMethods = Sale::query()
            ->dateRange($startDate, $endDate)
            ->select([
                'payment_method',
                \DB::raw('COUNT(*) as transactions'),
                \DB::raw('SUM(total_amount) as revenue'),
                \DB::raw('AVG(total_amount) as avg_transaction'),
            ])
            ->groupBy('payment_method')
            ->orderBy('revenue', 'desc')
            ->get();

        return Inertia::render('Analytics/Sales', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'period' => $period,
            ],
            'salesTrend' => $salesTrend,
            'movingAverages' => $movingAverages,
            'categoryAnalysis' => $categoryAnalysis,
            'hourlyPatterns' => $hourlyPatterns,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Customer Analytics Page
     */
    public function customers(Request $request)
    {
        // RFM Analysis
        $rfmAnalysis = Customer::rfmAnalysis()
            ->get()
            ->groupBy('rfm_segment')
            ->map(fn($group) => [
                'segment' => $group[0]->rfm_segment,
                'count' => $group->count(),
                'avgRecency' => round($group->avg('recency'), 1),
                'avgFrequency' => round($group->avg('frequency'), 1),
                'avgMonetary' => round($group->avg('monetary'), 2),
                'customers' => $group->take(5)->map(fn($c) => [
                    'id' => $c->customer_id,
                    'name' => $c->name,
                    'email' => $c->email,
                    'rfmScore' => $c->rfm_score,
                ]),
            ])
            ->values();

        // CLV Prediction
        $clvPrediction = Customer::clvPrediction(12)
            ->orderBy('predicted_value_next_12m', 'desc')
            ->limit(100)
            ->get()
            ->map(fn($c) => [
                'customerId' => $c->customer_id,
                'name' => $c->name,
                'segment' => $c->segment,
                'currentLtv' => (float) $c->lifetime_value,
                'predictedValue' => (float) $c->predicted_value_next_12m,
                'ordersPerMonth' => round($c->orders_per_month, 2),
            ]);

        // Churn Analysis
        $churnAnalysis = Customer::churnAnalysis(90)->get();

        // Geographic Distribution
        $geoDistribution = Customer::geographicDistribution('country')
            ->limit(20)
            ->get()
            ->map(fn($item) => [
                'country' => $item->country,
                'customers' => $item->customer_count,
                'totalLtv' => (float) $item->total_ltv,
                'avgLtv' => (float) $item->avg_ltv,
                'avgOrders' => round($item->avg_orders, 1),
            ]);

        // Acquisition Channels
        $acquisitionChannels = Customer::acquisitionChannelPerformance()
            ->get()
            ->map(fn($item) => [
                'channel' => $item->acquisition_channel,
                'customers' => $item->customers_acquired,
                'avgLtv' => (float) $item->avg_ltv,
                'totalLtv' => (float) $item->total_ltv,
                'avgLifespan' => round($item->avg_customer_lifespan_days, 0),
            ]);

        // Cohort Analysis
        $cohortAnalysis = Sale::cohortAnalysis('month')
            ->whereBetween('customer_cohorts.cohort', [
                now()->subMonths(12)->startOfMonth(),
                now()->endOfMonth(),
            ])
            ->get()
            ->groupBy('cohort')
            ->map(fn($cohort) => [
                'cohort' => Carbon::parse($cohort[0]->cohort)->format('Y-m'),
                'retention' => $cohort->mapWithKeys(fn($item) => [
                    $item->months_since_first => [
                        'customers' => $item->customers,
                        'revenue' => (float) $item->revenue,
                        'orders' => $item->orders,
                    ]
                ]),
            ])
            ->values();

        return Inertia::render('Analytics/Customers', [
            'rfmAnalysis' => $rfmAnalysis,
            'clvPrediction' => $clvPrediction,
            'churnAnalysis' => $churnAnalysis,
            'geoDistribution' => $geoDistribution,
            'acquisitionChannels' => $acquisitionChannels,
            'cohortAnalysis' => $cohortAnalysis,
        ]);
    }

    /**
     * Product Analytics Page
     */
    public function products(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonths(1)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Product Performance
        $productPerformance = Product::performanceAnalysis($startDate, $endDate)
            ->orderBy('revenue', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($p) => [
                'productId' => $p->product_id,
                'name' => $p->name,
                'category' => $p->category,
                'brand' => $p->brand,
                'price' => (float) $p->price,
                'margin' => (float) $p->margin,
                'orders' => $p->orders_count,
                'unitsSold' => $p->units_sold,
                'revenue' => (float) $p->revenue,
                'profit' => (float) $p->profit,
                'avgDiscount' => (float) $p->avg_discount,
                'uniqueCustomers' => $p->unique_customers,
            ]);

        // ABC Analysis
        $abcAnalysis = Product::abcAnalysis()
            ->get()
            ->groupBy('abc_category')
            ->map(fn($group, $category) => [
                'category' => $category,
                'productCount' => $group->count(),
                'totalRevenue' => $group->sum('total_revenue'),
                'revenuePercentage' => round($group->first()->cumulative_percentage - 
                    ($category === 'A' ? 0 : ($category === 'B' ? 80 : 95)), 2),
                'products' => $group->take(10)->map(fn($p) => [
                    'productId' => $p->product_id,
                    'name' => $p->name,
                    'revenue' => (float) $p->total_revenue,
                    'units' => $p->total_units,
                ]),
            ]);

        // Inventory Turnover
        $inventoryTurnover = Product::inventoryTurnover(30)
            ->whereNotNull('turnover_rate')
            ->orderBy('turnover_rate', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'productId' => $p->product_id,
                'name' => $p->name,
                'category' => $p->category,
                'stockQuantity' => $p->stock_quantity,
                'unitsSold' => $p->units_sold,
                'turnoverRate' => round($p->turnover_rate, 2),
                'daysOfSupply' => round($p->days_of_supply ?? 0, 0),
                'stockValue' => (float) ($p->stock_quantity * $p->cost),
            ]);

        // Category Performance Pivot
        $categoryPivot = Sale::query()
            ->dateRange($startDate, $endDate)
            ->pivot(['region'], 'total_amount', 'sum')
            ->select([
                'category',
                \DB::raw("SUM(CASE WHEN region = 'North' THEN total_amount ELSE 0 END) as north"),
                \DB::raw("SUM(CASE WHEN region = 'South' THEN total_amount ELSE 0 END) as south"),
                \DB::raw("SUM(CASE WHEN region = 'East' THEN total_amount ELSE 0 END) as east"),
                \DB::raw("SUM(CASE WHEN region = 'West' THEN total_amount ELSE 0 END) as west"),
                \DB::raw('SUM(total_amount) as total'),
            ])
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get();

        return Inertia::render('Analytics/Products', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'productPerformance' => $productPerformance,
            'abcAnalysis' => $abcAnalysis,
            'inventoryTurnover' => $inventoryTurnover,
            'categoryPivot' => $categoryPivot,
        ]);
    }

    /**
     * Advanced Analytics Page
     */
    public function advanced(Request $request)
    {
        // Window Functions Example: Sales Ranking
        $salesRanking = Sale::salesByRegionWithRank()
            ->where('sale_date', '>=', now()->subDays(7))
            ->orderBy('region')
            ->orderBy('rank_in_region')
            ->limit(50)
            ->get()
            ->groupBy('region')
            ->map(fn($group) => $group->take(10)->map(fn($s) => [
                'orderId' => $s->order_id,
                'amount' => (float) $s->total_amount,
                'rank' => $s->rank_in_region,
                'percentile' => round($s->percentile_in_region * 100, 1),
                'regionTotal' => (float) $s->region_total,
                'regionAvg' => (float) $s->region_avg,
            ]));

        // Cross-sell Analysis for top products
        $topProductIds = Sale::query()
            ->select('product_id')
            ->groupBy('product_id')
            ->orderByRaw('SUM(total_amount) DESC')
            ->limit(5)
            ->pluck('product_id');

        $crossSellAnalysis = collect($topProductIds)->mapWithKeys(function ($productId) {
            $product = Product::find($productId);
            $crossSells = Product::crossSellAnalysis($productId, 5)->get();
            
            return [$productId => [
                'product' => $product ? $product->name : "Product $productId",
                'recommendations' => $crossSells->map(fn($p) => [
                    'productId' => $p->product_id,
                    'name' => $p->name,
                    'coOccurrence' => $p->co_occurrence_count,
                    'revenue' => (float) $p->total_revenue,
                ]),
            ]];
        });

        return Inertia::render('Analytics/Advanced', [
            'salesRanking' => $salesRanking,
            'crossSellAnalysis' => $crossSellAnalysis,
        ]);
    }

    /**
     * Export functionality
     */
    public function export(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sales,customers,products',
            'format' => 'required|in:parquet,csv,json',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $filename = $request->type . '_export_' . now()->format('Y-m-d_His') . '.' . $request->format;
        $path = storage_path('app/exports/' . $filename);

        switch ($request->type) {
            case 'sales':
                if ($request->format === 'parquet') {
                    Sale::exportToParquet($path, $request->start_date, $request->end_date);
                } else {
                    $data = Sale::query()
                        ->when($request->start_date && $request->end_date, fn($q) => 
                            $q->dateRange($request->start_date, $request->end_date)
                        )
                        ->get();
                    
                    if ($request->format === 'csv') {
                        $this->exportToCsv($data, $path);
                    } else {
                        file_put_contents($path, $data->toJson());
                    }
                }
                break;

            case 'customers':
                $data = Customer::query()->get();
                if ($request->format === 'csv') {
                    $this->exportToCsv($data, $path);
                } else {
                    file_put_contents($path, $data->toJson());
                }
                break;

            case 'products':
                $data = Product::query()->get();
                if ($request->format === 'csv') {
                    $this->exportToCsv($data, $path);
                } else {
                    file_put_contents($path, $data->toJson());
                }
                break;
        }

        return response()->download($path)->deleteFileAfterSend();
    }

    private function exportToCsv($data, $path)
    {
        $handle = fopen($path, 'w');
        
        // Write headers
        if ($data->count() > 0) {
            fputcsv($handle, array_keys($data->first()->toArray()));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($handle, $row->toArray());
        }
        
        fclose($handle);
    }

    private function getPeriodFormat($period)
    {
        return match($period) {
            'hour' => 'Y-m-d H:00',
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'Y-m',
            'quarter' => 'Y-Q',
            'year' => 'Y',
            default => 'Y-m-d'
        };
    }
}