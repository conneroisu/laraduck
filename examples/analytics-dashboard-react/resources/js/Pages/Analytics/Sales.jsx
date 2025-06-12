import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, Grid, Text, Flex, AreaChart, BarChart, DonutChart, LineChart, Select, SelectItem, Button, DateRangePicker, DateRangePickerItem, Tab, TabGroup, TabList, TabPanels, TabPanel } from '@tremor/react';
import { CalendarIcon, ChartBarIcon, ClockIcon, CreditCardIcon, ArrowDownTrayIcon } from '@heroicons/react/24/outline';

const Sales = ({ filters, salesTrend, movingAverages, categoryAnalysis, hourlyPatterns, paymentMethods }) => {
    const [selectedPeriod, setSelectedPeriod] = useState(filters.period);
    const [dateRange, setDateRange] = useState({
        start: new Date(filters.start_date),
        end: new Date(filters.end_date)
    });

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    };

    const formatNumber = (value) => {
        return new Intl.NumberFormat('en-US').format(value);
    };

    const handlePeriodChange = (value) => {
        setSelectedPeriod(value);
        window.location.href = `?period=${value}&start_date=${filters.start_date}&end_date=${filters.end_date}`;
    };

    const handleDateRangeChange = (value) => {
        setDateRange(value);
        if (value.start && value.end) {
            const startDate = value.start.toISOString().split('T')[0];
            const endDate = value.end.toISOString().split('T')[0];
            window.location.href = `?period=${selectedPeriod}&start_date=${startDate}&end_date=${endDate}`;
        }
    };

    const handleExport = (format) => {
        window.location.href = `/analytics/export?type=sales&format=${format}&start_date=${filters.start_date}&end_date=${filters.end_date}`;
    };

    // Process hourly data for 24-hour visualization
    const hourlyData = Array.from({ length: 24 }, (_, hour) => {
        const data = hourlyPatterns.find(h => h.hour === hour) || {
            hour,
            orders: 0,
            revenue: 0,
            avgOrderValue: 0
        };
        return {
            ...data,
            hourLabel: `${hour.toString().padStart(2, '0')}:00`
        };
    });

    return (
        <AuthenticatedLayout>
            <Head title="Sales Analytics" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header with Controls */}
                    <Card>
                        <Flex justifyContent="between" alignItems="start">
                            <div>
                                <Text className="text-xl font-semibold">Sales Analytics</Text>
                                <Text className="text-sm text-gray-500 mt-1">
                                    Comprehensive sales performance analysis
                                </Text>
                            </div>
                            <div className="flex items-center space-x-4">
                                <Select
                                    value={selectedPeriod}
                                    onValueChange={handlePeriodChange}
                                    className="w-32"
                                    icon={ChartBarIcon}
                                >
                                    <SelectItem value="hour">Hourly</SelectItem>
                                    <SelectItem value="day">Daily</SelectItem>
                                    <SelectItem value="week">Weekly</SelectItem>
                                    <SelectItem value="month">Monthly</SelectItem>
                                </Select>

                                <DateRangePicker
                                    value={dateRange}
                                    onValueChange={handleDateRangeChange}
                                    selectPlaceholder="Select"
                                    className="w-64"
                                >
                                    <DateRangePickerItem from={new Date(2024, 0, 1)} to={new Date()} />
                                </DateRangePicker>

                                <Button
                                    size="sm"
                                    variant="secondary"
                                    icon={ArrowDownTrayIcon}
                                    onClick={() => handleExport('csv')}
                                >
                                    Export
                                </Button>
                            </div>
                        </Flex>
                    </Card>

                    {/* Main Sales Trends */}
                    <TabGroup>
                        <TabList>
                            <Tab icon={ChartBarIcon}>Trends</Tab>
                            <Tab icon={CalendarIcon}>Moving Averages</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel>
                                <Card>
                                    <Text className="text-lg font-semibold mb-4">Sales Performance Trends</Text>
                                    <AreaChart
                                        className="h-80"
                                        data={salesTrend}
                                        index="period"
                                        categories={["revenue", "orders", "customers"]}
                                        colors={["indigo", "cyan", "amber"]}
                                        valueFormatter={(value) => 
                                            value > 1000 ? formatCurrency(value) : formatNumber(value)
                                        }
                                        showLegend={true}
                                        showYAxis={true}
                                        showGradient={true}
                                        showAnimation={true}
                                    />
                                    <Grid numItems={1} numItemsSm={3} className="gap-4 mt-6">
                                        <Card decoration="left" decorationColor="indigo">
                                            <Text>Total Revenue</Text>
                                            <Text className="text-2xl font-semibold">
                                                {formatCurrency(salesTrend.reduce((sum, item) => sum + item.revenue, 0))}
                                            </Text>
                                        </Card>
                                        <Card decoration="left" decorationColor="cyan">
                                            <Text>Total Orders</Text>
                                            <Text className="text-2xl font-semibold">
                                                {formatNumber(salesTrend.reduce((sum, item) => sum + item.orders, 0))}
                                            </Text>
                                        </Card>
                                        <Card decoration="left" decorationColor="amber">
                                            <Text>Unique Customers</Text>
                                            <Text className="text-2xl font-semibold">
                                                {formatNumber(salesTrend.reduce((sum, item) => sum + item.customers, 0))}
                                            </Text>
                                        </Card>
                                    </Grid>
                                </Card>
                            </TabPanel>
                            <TabPanel>
                                <Card>
                                    <Text className="text-lg font-semibold mb-4">7-Day Moving Averages</Text>
                                    <LineChart
                                        className="h-80"
                                        data={movingAverages}
                                        index="date"
                                        categories={["dailyRevenue", "movingAvg7d"]}
                                        colors={["gray", "indigo"]}
                                        valueFormatter={formatCurrency}
                                        showLegend={true}
                                        showYAxis={true}
                                        showAnimation={true}
                                    />
                                    <Text className="text-sm text-gray-500 mt-4">
                                        Moving averages help identify trends by smoothing out short-term fluctuations
                                    </Text>
                                </Card>
                            </TabPanel>
                        </TabPanels>
                    </TabGroup>

                    {/* Category and Brand Analysis */}
                    <Card>
                        <Text className="text-lg font-semibold mb-4">Category & Brand Performance</Text>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Category / Brand
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Orders
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Revenue
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Units Sold
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Avg Discount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {categoryAnalysis.map((item, index) => (
                                        <tr key={index} className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <Text className="font-medium">{item.category}</Text>
                                                    <Text className="text-sm text-gray-500">{item.brand}</Text>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right">
                                                {formatNumber(item.orders)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right font-medium">
                                                {formatCurrency(item.revenue)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right">
                                                {formatNumber(item.units_sold)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right">
                                                {item.avg_discount_percent?.toFixed(1)}%
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <Grid numItems={1} numItemsLg={2} className="gap-6">
                        {/* Hourly Patterns */}
                        <Card>
                            <Flex justifyContent="between" alignItems="center" className="mb-4">
                                <Text className="text-lg font-semibold">Hourly Sales Patterns</Text>
                                <ClockIcon className="h-5 w-5 text-gray-400" />
                            </Flex>
                            <BarChart
                                className="h-72"
                                data={hourlyData}
                                index="hourLabel"
                                categories={["revenue"]}
                                colors={["indigo"]}
                                valueFormatter={formatCurrency}
                                showLegend={false}
                                showAnimation={true}
                            />
                            <Text className="text-sm text-gray-500 mt-4">
                                Peak hours: {hourlyData.slice().sort((a, b) => b.revenue - a.revenue).slice(0, 3).map(h => h.hourLabel).join(', ')}
                            </Text>
                        </Card>

                        {/* Payment Methods */}
                        <Card>
                            <Flex justifyContent="between" alignItems="center" className="mb-4">
                                <Text className="text-lg font-semibold">Payment Method Distribution</Text>
                                <CreditCardIcon className="h-5 w-5 text-gray-400" />
                            </Flex>
                            <DonutChart
                                className="h-64"
                                data={paymentMethods}
                                category="revenue"
                                index="payment_method"
                                valueFormatter={formatCurrency}
                                colors={["slate", "violet", "indigo", "rose", "cyan"]}
                                showAnimation={true}
                            />
                            <div className="mt-4 space-y-2">
                                {paymentMethods.map((method) => (
                                    <Flex key={method.payment_method} justifyContent="between">
                                        <Text>{method.payment_method}</Text>
                                        <div className="text-right">
                                            <Text className="font-medium">{formatCurrency(method.revenue)}</Text>
                                            <Text className="text-xs text-gray-500">
                                                {formatNumber(method.transactions)} transactions
                                            </Text>
                                        </div>
                                    </Flex>
                                ))}
                            </div>
                        </Card>
                    </Grid>

                    {/* Average Order Value Trend */}
                    <Card>
                        <Text className="text-lg font-semibold mb-4">Average Order Value Trend</Text>
                        <LineChart
                            className="h-64"
                            data={salesTrend}
                            index="period"
                            categories={["avgOrderValue"]}
                            colors={["emerald"]}
                            valueFormatter={formatCurrency}
                            showLegend={false}
                            showYAxis={true}
                            showAnimation={true}
                        />
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default Sales;