<div class="space-y-6">
    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">${{ $totalRevenue }}</div>
                <div class="text-gray-600">Total Revenue</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $totalTransactions }}</div>
                <div class="text-gray-600">Total Transactions</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">${{ $averageTransaction }}</div>
                <div class="text-gray-600">Average Transaction</div>
            </div>
        </flux:card>
    </div>

    <!-- Daily Sales Chart -->
    <flux:card>
        <flux:heading size="lg">Daily Sales (Last 30 Days)</flux:heading>
        <div class="mt-4">
            @if(count($dailySales) > 0)
                <div class="h-64 bg-gray-50 rounded-lg flex items-end justify-around p-4 space-x-1">
                    @foreach($dailySales as $day)
                        <div class="flex flex-col items-center">
                            <div 
                                class="bg-blue-500 rounded-t"
                                style="height: {{ ($day['revenue'] / max(array_column($dailySales, 'revenue'))) * 200 }}px; min-height: 4px; width: 12px;"
                                title="${{ number_format($day['revenue'], 2) }} - {{ $day['date'] }}"
                            ></div>
                            <div class="text-xs text-gray-600 mt-1 transform -rotate-45 origin-left">
                                {{ $day['date'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-500 py-8">No sales data available</div>
            @endif
        </div>
    </flux:card>

    <!-- Regional Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <flux:card>
            <flux:heading size="lg">Sales by Region</flux:heading>
            <div class="mt-4 space-y-3">
                @foreach($regionSales as $region)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div>
                            <div class="font-semibold">{{ $region['region'] }}</div>
                            <div class="text-sm text-gray-600">
                                {{ number_format($region['transactions']) }} transactions • 
                                {{ number_format($region['customers']) }} customers
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-green-600">${{ number_format($region['revenue'], 2) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Top Products</flux:heading>
            <div class="mt-4 space-y-3">
                @foreach($topProducts as $index => $product)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div>
                            <div class="font-semibold">Product #{{ $product['product_id'] }}</div>
                            <div class="text-sm text-gray-600">
                                {{ number_format($product['units_sold']) }} units • 
                                {{ number_format($product['transactions']) }} orders
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-green-600">${{ number_format($product['revenue'], 2) }}</div>
                            <div class="text-xs text-gray-500">#{{ $index + 1 }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>

    <!-- Refresh Button -->
    <div class="flex justify-center">
        <flux:button wire:click="loadOverviewData" variant="primary">
            Refresh Data
        </flux:button>
    </div>
</div>