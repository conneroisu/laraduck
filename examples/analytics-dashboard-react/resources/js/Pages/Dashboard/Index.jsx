import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, Grid, Text, Metric, Flex, ProgressBar, AreaChart, BarChart, DonutChart, BadgeDelta } from '@tremor/react';
import { ArrowUpIcon, ArrowDownIcon, CurrencyDollarIcon, ShoppingCartIcon, UsersIcon, ChartBarIcon } from '@heroicons/react/24/solid';

const MetricCard = ({ title, metric, change, icon: Icon }) => {
    const isPositive = change >= 0;
    
    return (
        <Card>
            <Flex justifyContent="between" alignItems="center">
                <div>
                    <Text>{title}</Text>
                    <Metric>{metric}</Metric>
                    <Flex justifyContent="start" alignItems="center" className="mt-2">
                        <BadgeDelta
                            deltaType={isPositive ? 'increase' : 'decrease'}
                            size="xs"
                        >
                            {Math.abs(change)}%
                        </BadgeDelta>
                        <Text className="ml-2 text-xs">vs previous period</Text>
                    </Flex>
                </div>
                <Icon className="h-12 w-12 text-gray-400" />
            </Flex>
        </Card>
    );
};

const Dashboard = ({ filters, metrics, salesTrend, topProducts, salesByRegion, customerSegments }) => {
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    };

    const formatNumber = (value) => {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Analytics Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Date Range Selector */}
                    <Card>
                        <Flex justifyContent="between" alignItems="center">
                            <div>
                                <Text className="text-lg font-semibold">Analytics Dashboard</Text>
                                <Text className="text-sm text-gray-500">
                                    {filters.start_date} to {filters.end_date}
                                </Text>
                            </div>
                            <div className="flex space-x-2">
                                <input
                                    type="date"
                                    value={filters.start_date}
                                    className="px-3 py-2 border border-gray-300 rounded-md"
                                    onChange={(e) => {
                                        window.location.href = `?start_date=${e.target.value}&end_date=${filters.end_date}`;
                                    }}
                                />
                                <input
                                    type="date"
                                    value={filters.end_date}
                                    className="px-3 py-2 border border-gray-300 rounded-md"
                                    onChange={(e) => {
                                        window.location.href = `?start_date=${filters.start_date}&end_date=${e.target.value}`;
                                    }}
                                />
                            </div>
                        </Flex>
                    </Card>

                    {/* Key Metrics */}
                    <Grid numItems={1} numItemsSm={2} numItemsLg={4} className="gap-6">
                        <MetricCard
                            title="Total Revenue"
                            metric={formatCurrency(metrics.revenue.value)}
                            change={metrics.revenue.change}
                            icon={CurrencyDollarIcon}
                        />
                        <MetricCard
                            title="Total Orders"
                            metric={formatNumber(metrics.orders.value)}
                            change={metrics.orders.change}
                            icon={ShoppingCartIcon}
                        />
                        <MetricCard
                            title="Unique Customers"
                            metric={formatNumber(metrics.customers.value)}
                            change={metrics.customers.change}
                            icon={UsersIcon}
                        />
                        <MetricCard
                            title="Avg Order Value"
                            metric={formatCurrency(metrics.avgOrderValue.value)}
                            change={metrics.avgOrderValue.change}
                            icon={ChartBarIcon}
                        />
                    </Grid>

                    {/* Sales Trend Chart */}
                    <Card>
                        <Text className="text-lg font-semibold mb-4">Sales Trend</Text>
                        <AreaChart
                            className="h-72 mt-4"
                            data={salesTrend}
                            index="date"
                            categories={["revenue", "orders"]}
                            colors={["indigo", "cyan"]}
                            valueFormatter={(value) => formatCurrency(value)}
                            showLegend={true}
                            showYAxis={true}
                            showGradient={true}
                            showAnimation={true}
                        />
                    </Card>

                    <Grid numItems={1} numItemsLg={2} className="gap-6">
                        {/* Top Products */}
                        <Card>
                            <Text className="text-lg font-semibold mb-4">Top Products</Text>
                            <div className="space-y-4">
                                {topProducts.map((product, index) => (
                                    <div key={product.product_id}>
                                        <Flex justifyContent="between" alignItems="center">
                                            <div>
                                                <Text className="font-medium">Product #{product.product_id}</Text>
                                                <Text className="text-sm text-gray-500">
                                                    {product.category} - {product.brand}
                                                </Text>
                                            </div>
                                            <div className="text-right">
                                                <Text className="font-semibold">{formatCurrency(product.revenue)}</Text>
                                                <Text className="text-sm text-gray-500">
                                                    {formatNumber(product.units_sold)} units
                                                </Text>
                                            </div>
                                        </Flex>
                                        <ProgressBar
                                            value={(product.revenue / topProducts[0].revenue) * 100}
                                            color={index === 0 ? "indigo" : "gray"}
                                            className="mt-2"
                                        />
                                    </div>
                                ))}
                            </div>
                        </Card>

                        {/* Sales by Region */}
                        <Card>
                            <Text className="text-lg font-semibold mb-4">Sales by Region</Text>
                            <DonutChart
                                className="h-64"
                                data={salesByRegion}
                                category="revenue"
                                index="region"
                                valueFormatter={formatCurrency}
                                colors={["slate", "violet", "indigo", "rose", "cyan", "amber"]}
                                showAnimation={true}
                            />
                            <div className="mt-4 space-y-2">
                                {salesByRegion.map((region) => (
                                    <Flex key={region.region} justifyContent="between">
                                        <Text>{region.region}</Text>
                                        <Text className="font-medium">{formatCurrency(region.revenue)}</Text>
                                    </Flex>
                                ))}
                            </div>
                        </Card>
                    </Grid>

                    {/* Customer Segments */}
                    <Card>
                        <Text className="text-lg font-semibold mb-4">Customer Segments</Text>
                        <BarChart
                            className="h-72"
                            data={customerSegments}
                            index="segment"
                            categories={["customerCount", "avgLtv"]}
                            colors={["blue", "emerald"]}
                            valueFormatter={(value) => 
                                value > 1000 ? formatCurrency(value) : formatNumber(value)
                            }
                            showLegend={true}
                            showAnimation={true}
                        />
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default Dashboard;