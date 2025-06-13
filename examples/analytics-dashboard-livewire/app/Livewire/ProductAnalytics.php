<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Sale;
use Livewire\Component;

class ProductAnalytics extends Component
{
    public $products = [];

    public $selectedCategory = 'all';

    public $categories = [];

    public function mount()
    {
        $this->loadCategories();
        $this->loadProducts();
    }

    public function updatedSelectedCategory()
    {
        $this->loadProducts();
    }

    public function loadCategories()
    {
        $this->categories = Product::distinct()
            ->pluck('category')
            ->prepend('all')
            ->toArray();
    }

    public function loadProducts()
    {
        $query = Product::query();

        if ($this->selectedCategory !== 'all') {
            $query->where('category', $this->selectedCategory);
        }

        // Join with sales data to get analytics
        $this->products = $query->selectRaw('
            products.*,
            COALESCE(sales_data.total_revenue, 0) as total_revenue,
            COALESCE(sales_data.total_units, 0) as total_units,
            COALESCE(sales_data.total_transactions, 0) as total_transactions,
            CASE 
                WHEN COALESCE(sales_data.total_units, 0) > 0 
                THEN (products.price - products.cost) * COALESCE(sales_data.total_units, 0)
                ELSE 0 
            END as total_profit
        ')
            ->leftJoinSub(
                Sale::selectRaw('
                product_id,
                SUM(total_amount) as total_revenue,
                SUM(quantity) as total_units,
                COUNT(*) as total_transactions
            ')->groupBy('product_id'),
                'sales_data',
                'products.id',
                '=',
                'sales_data.product_id'
            )
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category,
                    'price' => number_format($product->price, 2),
                    'cost' => number_format($product->cost, 2),
                    'profit_margin' => $product->profit_margin,
                    'total_revenue' => number_format($product->total_revenue, 2),
                    'total_units' => number_format($product->total_units),
                    'total_transactions' => number_format($product->total_transactions),
                    'total_profit' => number_format($product->total_profit, 2),
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.product-analytics');
    }
}
