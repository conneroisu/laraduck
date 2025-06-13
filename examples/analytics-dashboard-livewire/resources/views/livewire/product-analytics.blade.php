<flux:card>
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="lg">Product Analytics</flux:heading>
        <flux:select wire:model.live="selectedCategory" class="w-48">
            @foreach($categories as $category)
                <flux:option value="{{ $category }}">
                    {{ $category === 'all' ? 'All Categories' : ucfirst($category) }}
                </flux:option>
            @endforeach
        </flux:select>
    </div>

    @if(count($products) > 0)
        <!-- Products Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Product
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Category
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Price
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Profit Margin
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Units Sold
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Revenue
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Profit
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $product['name'] }}</div>
                                <div class="text-sm text-gray-500">ID: {{ $product['id'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:badge variant="outline" size="sm">
                                    {{ $product['category'] }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ $product['price'] }}
                                <div class="text-xs text-gray-500">Cost: ${{ $product['cost'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="text-sm font-medium text-gray-900">{{ $product['profit_margin'] }}%</div>
                                    <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="bg-{{ $product['profit_margin'] > 50 ? 'green' : ($product['profit_margin'] > 25 ? 'yellow' : 'red') }}-500 h-2 rounded-full"
                                            style="width: {{ min($product['profit_margin'], 100) }}%"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $product['total_units'] }}</div>
                                <div class="text-xs text-gray-500">{{ $product['total_transactions'] }} orders</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                ${{ $product['total_revenue'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                ${{ $product['total_profit'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary Cards -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-lg font-semibold">{{ count($products) }}</div>
                <div class="text-sm text-gray-600">
                    {{ $selectedCategory === 'all' ? 'Total Products' : ucfirst($selectedCategory) . ' Products' }}
                </div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-lg font-semibold">
                    ${{ number_format(array_sum(array_map(fn($p) => (float)str_replace(',', '', $p['total_revenue']), $products)), 2) }}
                </div>
                <div class="text-sm text-gray-600">Total Revenue</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-lg font-semibold">
                    {{ number_format(array_sum(array_map(fn($p) => (int)str_replace(',', '', $p['total_units']), $products))) }}
                </div>
                <div class="text-sm text-gray-600">Units Sold</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-lg font-semibold">
                    ${{ number_format(array_sum(array_map(fn($p) => (float)str_replace(',', '', $p['total_profit']), $products)), 2) }}
                </div>
                <div class="text-sm text-gray-600">Total Profit</div>
            </div>
        </div>
    @else
        <div class="text-center text-gray-500 py-12">
            <div class="text-lg font-medium">No products found</div>
            <div class="text-sm">
                @if($selectedCategory !== 'all')
                    Try selecting a different category or "All Categories"
                @else
                    No products are available in the database
                @endif
            </div>
        </div>
    @endif
</flux:card>