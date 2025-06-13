<x-layouts.app :title="__('Analytics Dashboard - Powered by Laraduck & DuckDB')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        
        <!-- Sales Overview Section -->
        <div>
            <flux:heading size="lg" class="mb-4">Sales Overview</flux:heading>
            <livewire:sales-overview />
        </div>

        <!-- Analytics Chart Section -->
        <div>
            <flux:heading size="lg" class="mb-4">Detailed Analytics</flux:heading>
            <livewire:analytics-chart />
        </div>

        <!-- Product Analytics Section -->
        <div>
            <flux:heading size="lg" class="mb-4">Product Performance</flux:heading>
            <livewire:product-analytics />
        </div>

        <!-- Laraduck Features Showcase -->
        <flux:card>
            <flux:heading size="lg">🦆 Laraduck Features Demonstrated</flux:heading>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800">AnalyticalModel</h4>
                    <p class="text-sm text-blue-600 mt-1">Base model class optimized for analytical workloads with DuckDB</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-green-800">Advanced Queries</h4>
                    <p class="text-sm text-green-600 mt-1">Window functions, CTEs, and complex aggregations using DuckDB's analytical SQL</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-purple-800">Laravel Integration</h4>
                    <p class="text-sm text-purple-600 mt-1">Seamless integration with Laravel's Eloquent ORM and Livewire</p>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-yellow-800">Real-time Analytics</h4>
                    <p class="text-sm text-yellow-600 mt-1">Interactive dashboards with live data updates using Livewire</p>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-red-800">Performance</h4>
                    <p class="text-sm text-red-600 mt-1">Fast analytical queries on large datasets with DuckDB's columnar engine</p>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-indigo-800">File Querying</h4>
                    <p class="text-sm text-indigo-600 mt-1">Direct querying of Parquet, CSV, and JSON files (demonstrated in models)</p>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <flux:button variant="primary" href="https://github.com/laraduck/eloquent-duckdb" target="_blank">
                    View Laraduck on GitHub
                </flux:button>
            </div>
        </flux:card>

    </div>
</x-layouts.app>
