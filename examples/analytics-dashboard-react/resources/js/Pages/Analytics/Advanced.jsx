import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, Grid, Text, Flex, BarChart, Badge, ProgressBar, Tab, TabGroup, TabList, TabPanels, TabPanel, List, ListItem } from '@tremor/react';
import { SparklesIcon, ArrowTrendingUpIcon, ShoppingCartIcon, ChartBarIcon } from '@heroicons/react/24/outline';

const Advanced = ({ salesRanking, crossSellAnalysis }) => {
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

    // Define region colors
    const regionColors = {
        'North': 'slate',
        'South': 'violet',
        'East': 'indigo',
        'West': 'rose'
    };

    return (
        <AuthenticatedLayout>
            <Head title="Advanced Analytics" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <Card>
                        <Flex justifyContent="between" alignItems="center">
                            <div>
                                <Text className="text-xl font-semibold">Advanced Analytics</Text>
                                <Text className="text-sm text-gray-500 mt-1">
                                    Window functions, cross-sell analysis, and advanced SQL features
                                </Text>
                            </div>
                            <SparklesIcon className="h-8 w-8 text-gray-400" />
                        </Flex>
                    </Card>

                    {/* Window Functions - Sales Ranking */}
                    <Card>
                        <Flex justifyContent="between" alignItems="center" className="mb-6">
                            <div>
                                <Text className="text-lg font-semibold">Regional Sales Ranking</Text>
                                <Text className="text-sm text-gray-500 mt-1">
                                    Demonstrating window functions with RANK() and PERCENT_RANK()
                                </Text>
                            </div>
                            <ChartBarIcon className="h-5 w-5 text-gray-400" />
                        </Flex>

                        <TabGroup>
                            <TabList>
                                {Object.keys(salesRanking).map(region => (
                                    <Tab key={region}>{region}</Tab>
                                ))}
                            </TabList>
                            <TabPanels>
                                {Object.entries(salesRanking).map(([region, sales]) => (
                                    <TabPanel key={region}>
                                        <div className="space-y-4">
                                            {/* Regional Summary */}
                                            <div className="p-4 bg-gray-50 rounded-lg mb-6">
                                                <Grid numItems={1} numItemsSm={3} className="gap-4">
                                                    <div>
                                                        <Text className="text-sm text-gray-500">Region Total</Text>
                                                        <Text className="text-xl font-semibold">
                                                            {formatCurrency(sales[0]?.regionTotal || 0)}
                                                        </Text>
                                                    </div>
                                                    <div>
                                                        <Text className="text-sm text-gray-500">Average Order</Text>
                                                        <Text className="text-xl font-semibold">
                                                            {formatCurrency(sales[0]?.regionAvg || 0)}
                                                        </Text>
                                                    </div>
                                                    <div>
                                                        <Text className="text-sm text-gray-500">Orders in Top 10</Text>
                                                        <Text className="text-xl font-semibold">
                                                            {sales.length}
                                                        </Text>
                                                    </div>
                                                </Grid>
                                            </div>

                                            {/* Ranked Orders */}
                                            {sales.map((sale) => (
                                                <div key={sale.orderId} className="p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                                                    <Flex justifyContent="between" alignItems="start">
                                                        <div className="flex items-start space-x-4">
                                                            <div className="flex flex-col items-center">
                                                                <Badge 
                                                                    color={sale.rank === 1 ? 'amber' : sale.rank <= 3 ? 'gray' : 'slate'}
                                                                    size="lg"
                                                                >
                                                                    #{sale.rank}
                                                                </Badge>
                                                                <Text className="text-xs text-gray-500 mt-1">
                                                                    Rank
                                                                </Text>
                                                            </div>
                                                            <div>
                                                                <Text className="font-semibold">Order #{sale.orderId}</Text>
                                                                <Text className="text-2xl font-bold mt-1">
                                                                    {formatCurrency(sale.amount)}
                                                                </Text>
                                                                <Flex className="mt-2 space-x-4">
                                                                    <Badge color={regionColors[region]}>
                                                                        {region} Region
                                                                    </Badge>
                                                                    <Text className="text-sm text-gray-500">
                                                                        Top {sale.percentile}%
                                                                    </Text>
                                                                </Flex>
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <Text className="text-sm text-gray-500">% of Region Avg</Text>
                                                            <Text className="text-lg font-semibold">
                                                                {((sale.amount / sale.regionAvg) * 100).toFixed(0)}%
                                                            </Text>
                                                        </div>
                                                    </Flex>
                                                    
                                                    <div className="mt-3">
                                                        <Flex justifyContent="between" className="mb-1">
                                                            <Text className="text-xs text-gray-500">Relative to region average</Text>
                                                            <Text className="text-xs font-medium">
                                                                {sale.amount > sale.regionAvg ? '+' : ''}{formatCurrency(sale.amount - sale.regionAvg)}
                                                            </Text>
                                                        </Flex>
                                                        <ProgressBar
                                                            value={Math.min((sale.amount / sale.regionAvg) * 100, 150)}
                                                            color={sale.amount > sale.regionAvg ? 'emerald' : 'amber'}
                                                        />
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </TabPanel>
                                ))}
                            </TabPanels>
                        </TabGroup>

                        {/* SQL Example */}
                        <div className="mt-6 p-4 bg-gray-900 rounded-lg">
                            <Text className="text-xs text-gray-400 mb-2">SQL Example using Window Functions:</Text>
                            <pre className="text-xs text-gray-300 overflow-x-auto">
{`WITH ranked_sales AS (
    SELECT 
        order_id,
        region,
        total_amount,
        RANK() OVER (PARTITION BY region ORDER BY total_amount DESC) as rank_in_region,
        PERCENT_RANK() OVER (PARTITION BY region ORDER BY total_amount DESC) as percentile,
        SUM(total_amount) OVER (PARTITION BY region) as region_total,
        AVG(total_amount) OVER (PARTITION BY region) as region_avg
    FROM sales
    WHERE sale_date >= CURRENT_DATE - INTERVAL '7 days'
)
SELECT * FROM ranked_sales 
WHERE rank_in_region <= 10
ORDER BY region, rank_in_region;`}
                            </pre>
                        </div>
                    </Card>

                    {/* Cross-sell Analysis */}
                    <Card>
                        <Flex justifyContent="between" alignItems="center" className="mb-6">
                            <div>
                                <Text className="text-lg font-semibold">Cross-Sell Recommendations</Text>
                                <Text className="text-sm text-gray-500 mt-1">
                                    Products frequently bought together using CTEs and complex joins
                                </Text>
                            </div>
                            <ShoppingCartIcon className="h-5 w-5 text-gray-400" />
                        </Flex>

                        <Grid numItems={1} numItemsLg={2} className="gap-6">
                            {Object.entries(crossSellAnalysis).map(([productId, data]) => (
                                <Card key={productId}>
                                    <div className="mb-4">
                                        <Text className="font-semibold text-lg">{data.product}</Text>
                                        <Text className="text-sm text-gray-500">Product #{productId}</Text>
                                    </div>
                                    
                                    <Text className="text-sm font-medium mb-3">Frequently Bought Together:</Text>
                                    
                                    <div className="space-y-3">
                                        {data.recommendations.map((rec, index) => (
                                            <div key={rec.productId} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div className="flex items-center space-x-3">
                                                    <Badge 
                                                        color={index === 0 ? 'emerald' : 'gray'}
                                                        size="sm"
                                                    >
                                                        #{index + 1}
                                                    </Badge>
                                                    <div>
                                                        <Text className="font-medium">{rec.name}</Text>
                                                        <Text className="text-xs text-gray-500">
                                                            {rec.coOccurrence} co-purchases
                                                        </Text>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <Text className="font-semibold">{formatCurrency(rec.revenue)}</Text>
                                                    <Text className="text-xs text-gray-500">revenue</Text>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    
                                    <div className="mt-4 p-3 bg-blue-50 rounded-lg">
                                        <Text className="text-xs font-medium text-blue-900">💡 Recommendation:</Text>
                                        <Text className="text-xs text-blue-800 mt-1">
                                            Bundle these products for a {Math.floor(Math.random() * 5 + 10)}% discount to increase basket size
                                        </Text>
                                    </div>
                                </Card>
                            ))}
                        </Grid>

                        {/* SQL Example for Cross-sell */}
                        <div className="mt-6 p-4 bg-gray-900 rounded-lg">
                            <Text className="text-xs text-gray-400 mb-2">SQL Example using CTEs for Cross-sell Analysis:</Text>
                            <pre className="text-xs text-gray-300 overflow-x-auto">
{`WITH orders_with_product AS (
    SELECT DISTINCT order_id 
    FROM sales 
    WHERE product_id = :target_product_id
),
cross_sell_products AS (
    SELECT 
        p.product_id,
        p.name,
        p.category,
        COUNT(DISTINCT s.order_id) as co_occurrence_count,
        SUM(s.total_amount) as total_revenue
    FROM products p
    JOIN sales s ON p.product_id = s.product_id
    JOIN orders_with_product owp ON s.order_id = owp.order_id
    WHERE p.product_id != :target_product_id
    GROUP BY p.product_id, p.name, p.category
)
SELECT * FROM cross_sell_products
ORDER BY co_occurrence_count DESC
LIMIT 5;`}
                            </pre>
                        </div>
                    </Card>

                    {/* Additional Advanced Features */}
                    <Card>
                        <Text className="text-lg font-semibold mb-4">Laraduck Advanced Features Showcase</Text>
                        
                        <Grid numItems={1} numItemsSm={2} className="gap-4">
                            <div className="p-4 border rounded-lg">
                                <Flex justifyContent="start" alignItems="center" className="mb-2">
                                    <Badge color="indigo">Window Functions</Badge>
                                </Flex>
                                <Text className="text-sm font-medium">Supported Functions:</Text>
                                <List className="mt-2">
                                    <ListItem>
                                        <Text className="text-sm">ROW_NUMBER() - Sequential numbering</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">RANK() - Ranking with gaps</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">DENSE_RANK() - Ranking without gaps</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">PERCENT_RANK() - Percentile ranking</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">LAG/LEAD - Access previous/next rows</Text>
                                    </ListItem>
                                </List>
                            </div>
                            
                            <div className="p-4 border rounded-lg">
                                <Flex justifyContent="start" alignItems="center" className="mb-2">
                                    <Badge color="emerald">CTEs & Subqueries</Badge>
                                </Flex>
                                <Text className="text-sm font-medium">Common Table Expressions:</Text>
                                <List className="mt-2">
                                    <ListItem>
                                        <Text className="text-sm">Recursive CTEs for hierarchies</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">Multiple CTEs in single query</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">Materialized CTEs for performance</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">Complex nested subqueries</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">Correlated subqueries</Text>
                                    </ListItem>
                                </List>
                            </div>
                            
                            <div className="p-4 border rounded-lg">
                                <Flex justifyContent="start" alignItems="center" className="mb-2">
                                    <Badge color="cyan">Analytical Functions</Badge>
                                </Flex>
                                <Text className="text-sm font-medium">Built-in Analytics:</Text>
                                <List className="mt-2">
                                    <ListItem>
                                        <Text className="text-sm">PIVOT/UNPIVOT operations</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">Time series analysis</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">Statistical aggregations</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">Moving averages</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">Cumulative calculations</Text>
                                    </ListItem>
                                </List>
                            </div>
                            
                            <div className="p-4 border rounded-lg">
                                <Flex justifyContent="start" alignItems="center" className="mb-2">
                                    <Badge color="amber">File Operations</Badge>
                                </Flex>
                                <Text className="text-sm font-medium">Data Import/Export:</Text>
                                <List className="mt-2">
                                    <ListItem>
                                        <Text className="text-sm">Parquet file support</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">CSV import/export</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">JSON data handling</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">Direct file querying</Text>
                                    </ListItem>
                                    <ListItem>
                                        <Text className="text-sm">S3 integration ready</Text>
                                    </ListItem>
                                </List>
                            </div>
                        </Grid>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default Advanced;