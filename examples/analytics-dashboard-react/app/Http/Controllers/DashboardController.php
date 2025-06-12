<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Key metrics
        $metrics = $this->getKeyMetrics($startDate, $endDate);
        
        // Sales trend data
        $salesTrend = Sale::summarizeByPeriod('day', $startDate, $endDate)
            ->get()
            ->map(fn($item) => [
                'date' => Carbon::parse($item->period)->format('Y-m-d'),
                'revenue' => (float) $item->revenue,
                'orders' => $item->total_orders,
                'customers' => $item->unique_customers,
            ]);

        // Top products
        $topProducts = Sale::topProducts(5, $startDate, $endDate)
            ->get()
            ->map(fn($item) => [
                'product_id' => $item->product_id,
                'category' => $item->category,
                'brand' => $item->brand,
                'revenue' => (float) $item->revenue,
                'units_sold' => $item->units_sold,
            ]);

        // Sales by region
        $salesByRegion = Sale::query()
            ->dateRange($startDate, $endDate)
            ->select([
                'region',
                \DB::raw('COUNT(DISTINCT order_id) as orders'),
                \DB::raw('SUM(total_amount) as revenue'),
                \DB::raw('AVG(total_amount) as avg_order_value'),
            ])
            ->groupBy('region')
            ->orderBy('revenue', 'desc')
            ->get()
            ->map(fn($item) => [
                'region' => $item->region,
                'orders' => $item->orders,
                'revenue' => (float) $item->revenue,
                'avgOrderValue' => (float) $item->avg_order_value,
            ]);

        // Customer segments
        $customerSegments = Customer::segmentationStats()
            ->get()
            ->map(fn($item) => [
                'segment' => $item->segment,
                'customerCount' => $item->customer_count,
                'totalLtv' => (float) $item->total_ltv,
                'avgLtv' => (float) $item->avg_ltv,
            ]);

        return Inertia::render('Dashboard/Index', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'metrics' => $metrics,
            'salesTrend' => $salesTrend,
            'topProducts' => $topProducts,
            'salesByRegion' => $salesByRegion,
            'customerSegments' => $customerSegments,
        ]);
    }

    private function getKeyMetrics($startDate, $endDate)
    {
        $currentPeriod = Sale::query()
            ->dateRange($startDate, $endDate)
            ->select([
                \DB::raw('SUM(total_amount) as revenue'),
                \DB::raw('COUNT(DISTINCT order_id) as orders'),
                \DB::raw('COUNT(DISTINCT customer_id) as customers'),
                \DB::raw('AVG(total_amount) as avg_order_value'),
            ])
            ->first();

        // Calculate previous period for comparison
        $daysDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        $prevStartDate = Carbon::parse($startDate)->subDays($daysDiff)->format('Y-m-d');
        $prevEndDate = Carbon::parse($startDate)->subDay()->format('Y-m-d');

        $previousPeriod = Sale::query()
            ->dateRange($prevStartDate, $prevEndDate)
            ->select([
                \DB::raw('SUM(total_amount) as revenue'),
                \DB::raw('COUNT(DISTINCT order_id) as orders'),
                \DB::raw('COUNT(DISTINCT customer_id) as customers'),
                \DB::raw('AVG(total_amount) as avg_order_value'),
            ])
            ->first();

        return [
            'revenue' => [
                'value' => (float) $currentPeriod->revenue,
                'change' => $this->calculatePercentageChange($currentPeriod->revenue, $previousPeriod->revenue),
            ],
            'orders' => [
                'value' => $currentPeriod->orders,
                'change' => $this->calculatePercentageChange($currentPeriod->orders, $previousPeriod->orders),
            ],
            'customers' => [
                'value' => $currentPeriod->customers,
                'change' => $this->calculatePercentageChange($currentPeriod->customers, $previousPeriod->customers),
            ],
            'avgOrderValue' => [
                'value' => (float) $currentPeriod->avg_order_value,
                'change' => $this->calculatePercentageChange($currentPeriod->avg_order_value, $previousPeriod->avg_order_value),
            ],
        ];
    }

    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }
}