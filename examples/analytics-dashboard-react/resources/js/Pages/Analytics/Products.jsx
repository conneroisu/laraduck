import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, Grid, Text, Flex, BarChart, DonutChart, ScatterChart, Badge, ProgressBar, Tab, TabGroup, TabList, TabPanels, TabPanel, Metric, BadgeDelta } from '@tremor/react';
import { CubeIcon, ChartPieIcon, ArrowPathIcon, TagIcon, ArrowTrendingUpIcon, ArrowTrendingDownIcon } from '@heroicons/react/24/outline';

const Products = ({ filters, productPerformance, abcAnalysis, inventoryTurnover, categoryPivot }) => {
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

    // Process ABC analysis data for visualization
    const abcSummary = Object.values(abcAnalysis).map(category => ({
        category: category.category,
        productCount: category.productCount,
        revenue: category.totalRevenue,
        percentage: category.revenuePercentage
    }));

    // Calculate totals for metrics
    const totals = productPerformance.reduce((acc, product) => ({
        revenue: acc.revenue + product.revenue,
        units: acc.units + product.unitsSold,
        orders: acc.orders + product.orders,
        profit: acc.profit + product.profit
    }), { revenue: 0, units: 0, orders: 0, profit: 0 });

    // Process category pivot data for stacked bar chart
    const categoryPivotData = categoryPivot.map(item => ({
        category: item.category,
        North: item.north || 0,
        South: item.south || 0,
        East: item.east || 0,
        West: item.west || 0,
        total: item.total || 0
    }));

    return (
        <AuthenticatedLayout>
            <Head title="Product Analytics" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <Card>
                        <Flex justifyContent="between" alignItems="center">
                            <div>
                                <Text className="text-xl font-semibold">Product Analytics</Text>
                                <Text className="text-sm text-gray-500 mt-1">
                                    Product performance, inventory management, and profitability analysis
                                </Text>
                            </div>
                            <CubeIcon className="h-8 w-8 text-gray-400" />
                        </Flex>
                    </Card>

                    {/* Key Metrics */}
                    <Grid numItems={1} numItemsSm={2} numItemsLg={4} className="gap-6">
                        <Card>
                            <Flex justifyContent="between" alignItems="start">
                                <div>
                                    <Text>Total Revenue</Text>
                                    <Metric>{formatCurrency(totals.revenue)}</Metric>
                                </div>
                                <Badge color="emerald" icon={ArrowTrendingUpIcon}>
                                    +12.5%
                                </Badge>
                            </Flex>
                        </Card>
                        <Card>
                            <Flex justifyContent="between" alignItems="start">
                                <div>
                                    <Text>Total Profit</Text>
                                    <Metric>{formatCurrency(totals.profit)}</Metric>
                                    <Text className="text-sm text-gray-500 mt-1">
                                        {((totals.profit / totals.revenue) * 100).toFixed(1)}% margin
                                    </Text>
                                </div>
                                <ChartPieIcon className="h-8 w-8 text-gray-400" />
                            </Flex>
                        </Card>
                        <Card>
                            <Flex justifyContent="between" alignItems="start">
                                <div>
                                    <Text>Units Sold</Text>
                                    <Metric>{formatNumber(totals.units)}</Metric>
                                </div>
                                <Badge color="blue" icon={ArrowTrendingUpIcon}>
                                    +8.2%
                                </Badge>
                            </Flex>
                        </Card>
                        <Card>
                            <Flex justifyContent="between" alignItems="start">
                                <div>
                                    <Text>Active Products</Text>
                                    <Metric>{productPerformance.length}</Metric>
                                </div>
                                <TagIcon className="h-8 w-8 text-gray-400" />
                            </Flex>
                        </Card>
                    </Grid>

                    {/* ABC Analysis */}
                    <Card>
                        <Text className="text-lg font-semibold mb-4">ABC Analysis</Text>
                        <Text className="text-sm text-gray-500 mb-6">
                            Product classification by revenue contribution
                        </Text>
                        
                        <Grid numItems={1} numItemsLg={2} className="gap-6 mb-6">
                            <DonutChart
                                className="h-72"
                                data={abcSummary}
                                category="revenue"
                                index="category"
                                valueFormatter={formatCurrency}
                                colors={["emerald", "amber", "rose"]}
                                showAnimation={true}
                            />
                            
                            <div className="space-y-4">
                                {Object.entries(abcAnalysis).map(([category, data]) => (
                                    <div key={category} className="p-4 border rounded-lg">
                                        <Flex justifyContent="between" alignItems="center">
                                            <div className="flex items-center space-x-3">
                                                <div className={`w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold
                                                    ${category === 'A' ? 'bg-emerald-500' : 
                                                      category === 'B' ? 'bg-amber-500' : 'bg-rose-500'}`}>
                                                    {category}
                                                </div>
                                                <div>
                                                    <Text className="font-semibold">Category {category}</Text>
                                                    <Text className="text-sm text-gray-500">
                                                        {data.productCount} products ({data.revenuePercentage}% of revenue)
                                                    </Text>
                                                </div>
                                            </div>
                                            <Text className="font-bold text-lg">
                                                {formatCurrency(data.totalRevenue)}
                                            </Text>
                                        </Flex>
                                    </div>
                                ))}
                                
                                <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                                    <Text className="text-sm font-medium mb-2">ABC Analysis Insights:</Text>
                                    <ul className="text-sm text-gray-600 space-y-1">
                                        <li>• Category A: High-value items requiring tight control</li>
                                        <li>• Category B: Moderate-value items with normal controls</li>
                                        <li>• Category C: Low-value items with simple controls</li>
                                    </ul>
                                </div>
                            </div>
                        </Grid>
                    </Card>

                    <TabGroup>
                        <TabList>
                            <Tab icon={ChartPieIcon}>Performance Details</Tab>
                            <Tab icon={ArrowPathIcon}>Inventory Turnover</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel>
                                {/* Product Performance Table */}
                                <Card>
                                    <Text className="text-lg font-semibold mb-4">Product Performance Details</Text>
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead>
                                                <tr>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Product
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Category / Brand
                                                    </th>
                                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Price
                                                    </th>
                                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Units Sold
                                                    </th>
                                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Revenue
                                                    </th>
                                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Profit
                                                    </th>
                                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Margin %
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white divide-y divide-gray-200">
                                                {productPerformance.slice(0, 20).map((product, index) => (
                                                    <tr key={product.productId} className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <Text className="font-medium">{product.name}</Text>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <Text className="text-sm">{product.category}</Text>
                                                            <Text className="text-xs text-gray-500">{product.brand}</Text>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right">
                                                            {formatCurrency(product.price)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right">
                                                            {formatNumber(product.unitsSold)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right font-medium">
                                                            {formatCurrency(product.revenue)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right">
                                                            {formatCurrency(product.profit)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right">
                                                            <Badge color={product.margin > 30 ? 'emerald' : product.margin > 20 ? 'amber' : 'rose'}>
                                                                {product.margin.toFixed(1)}%
                                                            </Badge>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </Card>
                            </TabPanel>
                            
                            <TabPanel>
                                {/* Inventory Turnover Analysis */}
                                <Card>
                                    <Text className="text-lg font-semibold mb-4">Inventory Turnover Analysis</Text>
                                    <Text className="text-sm text-gray-500 mb-6">
                                        Products ranked by inventory turnover rate (annualized)
                                    </Text>
                                    
                                    <ScatterChart
                                        className="h-80 mb-6"
                                        data={inventoryTurnover}
                                        x="stockQuantity"
                                        y="turnoverRate"
                                        size="stockValue"
                                        category="category"
                                        colors={["indigo", "cyan", "amber", "rose", "emerald"]}
                                        valueFormatter={(value) => value.toFixed(2)}
                                        showLegend={true}
                                        showAnimation={true}
                                    />
                                    
                                    <div className="space-y-4">
                                        {inventoryTurnover.slice(0, 10).map((product, index) => (
                                            <div key={product.productId} className="p-4 border rounded-lg">
                                                <Flex justifyContent="between" alignItems="start">
                                                    <div className="flex-1">
                                                        <Text className="font-semibold">{product.name}</Text>
                                                        <Text className="text-sm text-gray-500">{product.category}</Text>
                                                        
                                                        <Grid numItems={4} className="gap-4 mt-3">
                                                            <div>
                                                                <Text className="text-xs text-gray-500">Stock Qty</Text>
                                                                <Text className="font-medium">{formatNumber(product.stockQuantity)}</Text>
                                                            </div>
                                                            <div>
                                                                <Text className="text-xs text-gray-500">Units/Month</Text>
                                                                <Text className="font-medium">{Math.round(product.unitsSold / 30 * 30)}</Text>
                                                            </div>
                                                            <div>
                                                                <Text className="text-xs text-gray-500">Turnover Rate</Text>
                                                                <Text className="font-medium">{product.turnoverRate.toFixed(2)}x</Text>
                                                            </div>
                                                            <div>
                                                                <Text className="text-xs text-gray-500">Days Supply</Text>
                                                                <Text className="font-medium">
                                                                    {product.daysOfSupply ? `${Math.round(product.daysOfSupply)} days` : 'N/A'}
                                                                </Text>
                                                            </div>
                                                        </Grid>
                                                    </div>
                                                    <div className="text-right">
                                                        <Text className="text-sm text-gray-500">Stock Value</Text>
                                                        <Text className="font-bold">{formatCurrency(product.stockValue)}</Text>
                                                    </div>
                                                </Flex>
                                                
                                                <div className="mt-3">
                                                    <Flex justifyContent="between" className="mb-1">
                                                        <Text className="text-xs text-gray-500">Inventory Health</Text>
                                                        <Text className="text-xs font-medium">
                                                            {product.turnoverRate > 12 ? 'Fast Moving' :
                                                             product.turnoverRate > 6 ? 'Normal' :
                                                             product.turnoverRate > 2 ? 'Slow Moving' : 'Dead Stock'}
                                                        </Text>
                                                    </Flex>
                                                    <ProgressBar
                                                        value={Math.min(product.turnoverRate * 8, 100)}
                                                        color={product.turnoverRate > 12 ? 'emerald' :
                                                               product.turnoverRate > 6 ? 'blue' :
                                                               product.turnoverRate > 2 ? 'amber' : 'rose'}
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </Card>
                            </TabPanel>
                        </TabPanels>
                    </TabGroup>

                    {/* Regional Category Performance */}
                    <Card>
                        <Text className="text-lg font-semibold mb-4">Category Performance by Region</Text>
                        <BarChart
                            className="h-80"
                            data={categoryPivotData}
                            index="category"
                            categories={["North", "South", "East", "West"]}
                            colors={["slate", "violet", "indigo", "rose"]}
                            valueFormatter={formatCurrency}
                            stack={true}
                            showLegend={true}
                            showAnimation={true}
                        />
                        
                        <Grid numItems={1} numItemsSm={2} numItemsLg={4} className="gap-4 mt-6">
                            {['North', 'South', 'East', 'West'].map((region) => {
                                const regionTotal = categoryPivotData.reduce((sum, cat) => sum + (cat[region] || 0), 0);
                                return (
                                    <Card key={region}>
                                        <Text className="text-sm text-gray-500">{region} Region</Text>
                                        <Text className="text-xl font-semibold mt-1">
                                            {formatCurrency(regionTotal)}
                                        </Text>
                                        <Text className="text-sm mt-2">
                                            {((regionTotal / categoryPivotData.reduce((sum, cat) => sum + cat.total, 0)) * 100).toFixed(1)}% of total
                                        </Text>
                                    </Card>
                                );
                            })}
                        </Grid>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default Products;