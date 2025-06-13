<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sale;
use Carbon\Carbon;

class AnalyticsChart extends Component
{
    public $chartType = 'revenue';
    public $timeframe = '30days';
    public $chartData = [];

    public function mount()
    {
        $this->loadChartData();
    }

    public function updatedChartType()
    {
        $this->loadChartData();
    }

    public function updatedTimeframe()
    {
        $this->loadChartData();
    }

    public function loadChartData()
    {
        $days = match($this->timeframe) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            default => 30
        };

        $startDate = Carbon::now()->subDays($days);

        switch ($this->chartType) {
            case 'revenue':
                $this->chartData = $this->getRevenueData($startDate);
                break;
            case 'transactions':
                $this->chartData = $this->getTransactionData($startDate);
                break;
            case 'trends':
                $this->chartData = $this->getTrendsData($startDate);
                break;
            default:
                $this->chartData = $this->getRevenueData($startDate);
        }
    }

    private function getRevenueData($startDate)
    {
        return Sale::where('sale_date', '>=', $startDate)
            ->dailySales()
            ->get()
            ->map(function ($sale) {
                return [
                    'date' => Carbon::parse($sale->date)->format('Y-m-d'),
                    'value' => (float) $sale->revenue,
                    'label' => '$' . number_format($sale->revenue, 0)
                ];
            })
            ->toArray();
    }

    private function getTransactionData($startDate)
    {
        return Sale::where('sale_date', '>=', $startDate)
            ->dailySales()
            ->get()
            ->map(function ($sale) {
                return [
                    'date' => Carbon::parse($sale->date)->format('Y-m-d'),
                    'value' => (int) $sale->transactions,
                    'label' => number_format($sale->transactions) . ' transactions'
                ];
            })
            ->toArray();
    }

    private function getTrendsData($startDate)
    {
        // Use DuckDB's window functions for moving averages
        return Sale::where('sale_date', '>=', $startDate)
            ->salesTrends(7)
            ->get()
            ->map(function ($sale) {
                return [
                    'date' => Carbon::parse($sale->sale_date)->format('Y-m-d'),
                    'value' => (float) $sale->daily_revenue,
                    'moving_avg' => (float) $sale->moving_avg,
                    'label' => '$' . number_format($sale->daily_revenue, 0)
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.analytics-chart');
    }
}