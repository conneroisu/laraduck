<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sale;
use Carbon\Carbon;

class SalesOverview extends Component
{
    public $totalRevenue;
    public $totalTransactions;
    public $averageTransaction;
    public $dailySales = [];
    public $regionSales = [];
    public $topProducts = [];

    public function mount()
    {
        $this->loadOverviewData();
    }

    public function loadOverviewData()
    {
        // Get overall metrics
        $overallMetrics = Sale::selectRaw('
            SUM(total_amount) as total_revenue,
            COUNT(*) as total_transactions,
            AVG(total_amount) as avg_transaction
        ')->first();

        $this->totalRevenue = number_format($overallMetrics->total_revenue ?? 0, 2);
        $this->totalTransactions = number_format($overallMetrics->total_transactions ?? 0);
        $this->averageTransaction = number_format($overallMetrics->avg_transaction ?? 0, 2);

        // Get daily sales for the last 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $this->dailySales = Sale::where('sale_date', '>=', $thirtyDaysAgo)
            ->dailySales()
            ->get()
            ->map(function ($sale) {
                return [
                    'date' => Carbon::parse($sale->date)->format('M j'),
                    'revenue' => (float) $sale->revenue,
                    'transactions' => (int) $sale->transactions,
                ];
            })
            ->toArray();

        // Get sales by region
        $this->regionSales = Sale::regionSales()
            ->get()
            ->map(function ($sale) {
                return [
                    'region' => $sale->region,
                    'revenue' => (float) $sale->revenue,
                    'transactions' => (int) $sale->transactions,
                    'customers' => (int) $sale->unique_customers,
                ];
            })
            ->toArray();

        // Get top products
        $this->topProducts = Sale::topProducts(5)
            ->get()
            ->map(function ($sale) {
                return [
                    'product_id' => $sale->product_id,
                    'revenue' => (float) $sale->revenue,
                    'units_sold' => (int) $sale->units_sold,
                    'transactions' => (int) $sale->transactions,
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.sales-overview');
    }
}