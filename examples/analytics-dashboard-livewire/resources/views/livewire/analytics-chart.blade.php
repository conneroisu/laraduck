<flux:card>
    <div class="flex justify-between items-center mb-4">
        <flux:heading size="lg">Analytics Chart</flux:heading>
        <div class="flex space-x-4">
            <flux:select wire:model.live="chartType">
                <flux:option value="revenue">Revenue</flux:option>
                <flux:option value="transactions">Transactions</flux:option>
                <flux:option value="trends">Trends with Moving Average</flux:option>
            </flux:select>

            <flux:select wire:model.live="timeframe">
                <flux:option value="7days">Last 7 Days</flux:option>
                <flux:option value="30days">Last 30 Days</flux:option>
                <flux:option value="90days">Last 90 Days</flux:option>
            </flux:select>
        </div>
    </div>

    <div class="mt-4">
        @if(count($chartData) > 0)
            @if($chartType === 'trends')
                <!-- Dual line chart for trends with moving average -->
                <div class="h-80 bg-gray-50 rounded-lg p-4 relative">
                    <div class="flex items-end justify-around h-full space-x-1">
                        @foreach($chartData as $point)
                            <div class="flex flex-col items-center relative" style="height: 100%;">
                                <!-- Daily Revenue Bar -->
                                <div 
                                    class="bg-blue-500 rounded-t absolute bottom-0"
                                    style="height: {{ ($point['value'] / max(array_column($chartData, 'value'))) * 80 }}%; width: 8px;"
                                    title="Daily: ${{ number_format($point['value'], 2) }} - {{ $point['date'] }}"
                                ></div>
                                <!-- Moving Average Line Point -->
                                <div 
                                    class="bg-red-500 rounded-full absolute"
                                    style="bottom: {{ ($point['moving_avg'] / max(array_column($chartData, 'moving_avg'))) * 80 }}%; width: 4px; height: 4px; left: 2px;"
                                    title="7-day avg: ${{ number_format($point['moving_avg'], 2) }}"
                                ></div>
                                <div class="text-xs text-gray-600 mt-1 transform -rotate-45 origin-left absolute -bottom-6">
                                    {{ \Carbon\Carbon::parse($point['date'])->format('M j') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!-- Legend -->
                    <div class="absolute top-2 right-2 flex space-x-4 text-sm">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded mr-1"></div>
                            <span>Daily Revenue</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-1"></div>
                            <span>7-Day Average</span>
                        </div>
                    </div>
                </div>
            @else
                <!-- Regular bar chart -->
                <div class="h-80 bg-gray-50 rounded-lg flex items-end justify-around p-4 space-x-1">
                    @foreach($chartData as $point)
                        <div class="flex flex-col items-center">
                            <div 
                                class="bg-{{ $chartType === 'revenue' ? 'green' : 'blue' }}-500 rounded-t"
                                style="height: {{ ($point['value'] / max(array_column($chartData, 'value'))) * 250 }}px; min-height: 4px; width: 16px;"
                                title="{{ $point['label'] }} - {{ $point['date'] }}"
                            ></div>
                            <div class="text-xs text-gray-600 mt-1 transform -rotate-45 origin-left">
                                {{ \Carbon\Carbon::parse($point['date'])->format('M j') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Chart Summary -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div class="bg-blue-50 p-3 rounded-lg">
                    <div class="text-lg font-semibold">{{ count($chartData) }}</div>
                    <div class="text-sm text-gray-600">Data Points</div>
                </div>
                <div class="bg-green-50 p-3 rounded-lg">
                    <div class="text-lg font-semibold">
                        @if($chartType === 'revenue')
                            ${{ number_format(array_sum(array_column($chartData, 'value')), 2) }}
                        @else
                            {{ number_format(array_sum(array_column($chartData, 'value'))) }}
                        @endif
                    </div>
                    <div class="text-sm text-gray-600">Total {{ ucfirst($chartType) }}</div>
                </div>
                <div class="bg-purple-50 p-3 rounded-lg">
                    <div class="text-lg font-semibold">
                        @if($chartType === 'revenue')
                            ${{ number_format(array_sum(array_column($chartData, 'value')) / count($chartData), 2) }}
                        @else
                            {{ number_format(array_sum(array_column($chartData, 'value')) / count($chartData)) }}
                        @endif
                    </div>
                    <div class="text-sm text-gray-600">Average</div>
                </div>
            </div>
        @else
            <div class="text-center text-gray-500 py-12">
                No data available for the selected timeframe
            </div>
        @endif
    </div>
</flux:card>