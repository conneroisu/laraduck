import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, Grid, Text, Flex, BarChart, DonutChart, ScatterChart, Badge, BadgeDelta, ProgressBar, Tab, TabGroup, TabList, TabPanels, TabPanel, List, ListItem } from '@tremor/react';
import { UserGroupIcon, CurrencyDollarIcon, ChartBarIcon, MapIcon, MegaphoneIcon, ArrowTrendingUpIcon } from '@heroicons/react/24/outline';

const Customers = ({ rfmAnalysis, clvPrediction, churnAnalysis, geoDistribution, acquisitionChannels, cohortAnalysis }) => {
    const [selectedTab, setSelectedTab] = useState(0);

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

    // Process churn data for visualization
    const churnData = churnAnalysis.map(item => ({
        ...item,
        status: item.is_churned ? 'Churned' : 'Active',
        percentage: (item.customer_count / churnAnalysis.reduce((sum, i) => sum + i.customer_count, 0)) * 100
    }));

    // RFM segment colors
    const rfmColors = {
        'Champions': 'emerald',
        'Loyal Customers': 'indigo',
        'Potential Loyalists': 'blue',
        'New Customers': 'cyan',
        'At Risk': 'amber',
        'Cant Lose Them': 'orange',
        'Lost': 'red'
    };

    // Process cohort data for retention matrix
    const cohortMatrix = cohortAnalysis.map(cohort => {
        const retentionData = { cohort: cohort.cohort };
        Object.entries(cohort.retention).forEach(([month, data]) => {
            retentionData[`month_${month}`] = {
                customers: data.customers,
                revenue: data.revenue,
                percentage: cohort.retention[0] ? (data.customers / cohort.retention[0].customers * 100) : 0
            };
        });
        return retentionData;
    });

    return (
        <AuthenticatedLayout>
            <Head title="Customer Analytics" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <Card>
                        <Flex justifyContent="between" alignItems="center">
                            <div>
                                <Text className="text-xl font-semibold">Customer Analytics</Text>
                                <Text className="text-sm text-gray-500 mt-1">
                                    Deep insights into customer behavior and value
                                </Text>
                            </div>
                            <UserGroupIcon className="h-8 w-8 text-gray-400" />
                        </Flex>
                    </Card>

                    {/* RFM Analysis */}
                    <Card>
                        <Text className="text-lg font-semibold mb-4">RFM Segmentation Analysis</Text>
                        <Text className="text-sm text-gray-500 mb-6">
                            Customer segments based on Recency, Frequency, and Monetary value
                        </Text>
                        
                        <Grid numItems={1} numItemsLg={2} className="gap-6">
                            <DonutChart
                                className="h-72"
                                data={rfmAnalysis}
                                category="count"
                                index="segment"
                                valueFormatter={formatNumber}
                                colors={Object.values(rfmColors)}
                                showAnimation={true}
                            />
                            
                            <div className="space-y-4">
                                {rfmAnalysis.map((segment) => (
                                    <div key={segment.segment} className="p-4 border rounded-lg">
                                        <Flex justifyContent="between" alignItems="start">
                                            <div className="flex-1">
                                                <Flex justifyContent="start" alignItems="center" className="mb-2">
                                                    <Text className="font-semibold">{segment.segment}</Text>
                                                    <Badge color={rfmColors[segment.segment]} className="ml-2">
                                                        {segment.count} customers
                                                    </Badge>
                                                </Flex>
                                                <Grid numItems={3} className="gap-2 text-sm">
                                                    <div>
                                                        <Text className="text-gray-500">Avg Recency</Text>
                                                        <Text className="font-medium">{segment.avgRecency} days</Text>
                                                    </div>
                                                    <div>
                                                        <Text className="text-gray-500">Avg Frequency</Text>
                                                        <Text className="font-medium">{segment.avgFrequency} orders</Text>
                                                    </div>
                                                    <div>
                                                        <Text className="text-gray-500">Avg Value</Text>
                                                        <Text className="font-medium">{formatCurrency(segment.avgMonetary)}</Text>
                                                    </div>
                                                </Grid>
                                            </div>
                                        </Flex>
                                    </div>
                                ))}
                            </div>
                        </Grid>
                    </Card>

                    {/* CLV Prediction */}
                    <Card>
                        <Flex justifyContent="between" alignItems="center" className="mb-4">
                            <Text className="text-lg font-semibold">Customer Lifetime Value Prediction</Text>
                            <ArrowTrendingUpIcon className="h-5 w-5 text-gray-400" />
                        </Flex>
                        
                        <ScatterChart
                            className="h-80"
                            data={clvPrediction.slice(0, 50)}
                            x="currentLtv"
                            y="predictedValue"
                            size="ordersPerMonth"
                            category="segment"
                            colors={["emerald", "indigo", "amber", "rose"]}
                            valueFormatter={formatCurrency}
                            showLegend={true}
                            showAnimation={true}
                        />

                        <div className="mt-6">
                            <Text className="font-medium mb-4">Top 10 Highest Predicted CLV Customers</Text>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Customer
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Segment
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Current LTV
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Predicted 12m
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Growth
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {clvPrediction.slice(0, 10).map((customer, index) => {
                                            const growth = ((customer.predictedValue - customer.currentLtv) / customer.currentLtv) * 100;
                                            return (
                                                <tr key={customer.customerId} className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Text className="font-medium">{customer.name}</Text>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Badge color={customer.segment === 'VIP' ? 'emerald' : 'gray'}>
                                                            {customer.segment}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right">
                                                        {formatCurrency(customer.currentLtv)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right font-medium">
                                                        {formatCurrency(customer.predictedValue)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right">
                                                        <BadgeDelta deltaType={growth > 0 ? 'increase' : 'decrease'}>
                                                            {growth.toFixed(0)}%
                                                        </BadgeDelta>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Card>

                    <TabGroup>
                        <TabList>
                            <Tab icon={ChartBarIcon}>Churn Analysis</Tab>
                            <Tab icon={MapIcon}>Geographic Distribution</Tab>
                            <Tab icon={MegaphoneIcon}>Acquisition Channels</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel>
                                <Card>
                                    <Text className="text-lg font-semibold mb-4">Customer Churn Analysis</Text>
                                    <Grid numItems={1} numItemsLg={2} className="gap-6">
                                        <BarChart
                                            className="h-72"
                                            data={churnData}
                                            index="segment"
                                            categories={["customer_count"]}
                                            colors={["rose", "emerald"]}
                                            stack={true}
                                            valueFormatter={formatNumber}
                                            showLegend={true}
                                            showAnimation={true}
                                        />
                                        <div className="space-y-4">
                                            {churnData.map((item) => (
                                                <div key={`${item.status}-${item.segment}`} className="p-4 border rounded-lg">
                                                    <Flex justifyContent="between" alignItems="center">
                                                        <div>
                                                            <Text className="font-medium">{item.segment}</Text>
                                                            <Text className="text-sm text-gray-500">{item.status}</Text>
                                                        </div>
                                                        <div className="text-right">
                                                            <Text className="font-semibold">{formatNumber(item.customer_count)}</Text>
                                                            <Text className="text-sm text-gray-500">
                                                                Risk Score: {item.avg_risk_score?.toFixed(2)}
                                                            </Text>
                                                        </div>
                                                    </Flex>
                                                    <ProgressBar
                                                        value={item.percentage}
                                                        color={item.is_churned ? "rose" : "emerald"}
                                                        className="mt-2"
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    </Grid>
                                </Card>
                            </TabPanel>
                            
                            <TabPanel>
                                <Card>
                                    <Text className="text-lg font-semibold mb-4">Geographic Distribution</Text>
                                    <BarChart
                                        className="h-96"
                                        data={geoDistribution}
                                        index="country"
                                        categories={["customers", "totalLtv"]}
                                        colors={["indigo", "emerald"]}
                                        valueFormatter={(value) => 
                                            value > 10000 ? formatCurrency(value) : formatNumber(value)
                                        }
                                        showLegend={true}
                                        layout="horizontal"
                                        showAnimation={true}
                                    />
                                    <Grid numItems={1} numItemsSm={2} numItemsLg={4} className="gap-4 mt-6">
                                        {geoDistribution.slice(0, 4).map((country) => (
                                            <Card key={country.country}>
                                                <Text className="text-sm text-gray-500">{country.country}</Text>
                                                <Text className="text-xl font-semibold mt-1">
                                                    {formatNumber(country.customers)}
                                                </Text>
                                                <Text className="text-sm mt-2">
                                                    Avg LTV: {formatCurrency(country.avgLtv)}
                                                </Text>
                                                <Text className="text-sm">
                                                    Avg Orders: {country.avgOrders}
                                                </Text>
                                            </Card>
                                        ))}
                                    </Grid>
                                </Card>
                            </TabPanel>
                            
                            <TabPanel>
                                <Card>
                                    <Text className="text-lg font-semibold mb-4">Acquisition Channel Performance</Text>
                                    <div className="space-y-4">
                                        {acquisitionChannels.map((channel, index) => {
                                            const maxLtv = Math.max(...acquisitionChannels.map(c => c.totalLtv));
                                            return (
                                                <div key={channel.channel} className="p-4 border rounded-lg">
                                                    <Flex justifyContent="between" alignItems="start">
                                                        <div className="flex-1">
                                                            <Text className="font-semibold text-lg">{channel.channel}</Text>
                                                            <Grid numItems={2} numItemsSm={4} className="gap-4 mt-2">
                                                                <div>
                                                                    <Text className="text-sm text-gray-500">Customers</Text>
                                                                    <Text className="font-medium">{formatNumber(channel.customers)}</Text>
                                                                </div>
                                                                <div>
                                                                    <Text className="text-sm text-gray-500">Avg LTV</Text>
                                                                    <Text className="font-medium">{formatCurrency(channel.avgLtv)}</Text>
                                                                </div>
                                                                <div>
                                                                    <Text className="text-sm text-gray-500">Total LTV</Text>
                                                                    <Text className="font-medium">{formatCurrency(channel.totalLtv)}</Text>
                                                                </div>
                                                                <div>
                                                                    <Text className="text-sm text-gray-500">Avg Lifespan</Text>
                                                                    <Text className="font-medium">{channel.avgLifespan} days</Text>
                                                                </div>
                                                            </Grid>
                                                        </div>
                                                    </Flex>
                                                    <ProgressBar
                                                        value={(channel.totalLtv / maxLtv) * 100}
                                                        color={index === 0 ? "indigo" : "gray"}
                                                        className="mt-4"
                                                    />
                                                </div>
                                            );
                                        })}
                                    </div>
                                </Card>
                            </TabPanel>
                        </TabPanels>
                    </TabGroup>

                    {/* Cohort Retention Matrix */}
                    <Card>
                        <Text className="text-lg font-semibold mb-4">Cohort Retention Analysis</Text>
                        <Text className="text-sm text-gray-500 mb-6">
                            Customer retention by monthly cohorts
                        </Text>
                        <div className="overflow-x-auto">
                            <table className="min-w-full">
                                <thead>
                                    <tr>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Cohort
                                        </th>
                                        {[0, 1, 2, 3, 4, 5, 6].map(month => (
                                            <th key={month} className="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                                Month {month}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {cohortMatrix.map((cohort) => (
                                        <tr key={cohort.cohort}>
                                            <td className="px-4 py-2 font-medium text-sm">
                                                {cohort.cohort}
                                            </td>
                                            {[0, 1, 2, 3, 4, 5, 6].map(month => {
                                                const data = cohort[`month_${month}`];
                                                if (!data) return <td key={month} className="px-4 py-2"></td>;
                                                
                                                const color = data.percentage > 80 ? 'bg-emerald-100' :
                                                            data.percentage > 60 ? 'bg-green-100' :
                                                            data.percentage > 40 ? 'bg-yellow-100' :
                                                            data.percentage > 20 ? 'bg-orange-100' : 'bg-red-100';
                                                
                                                return (
                                                    <td key={month} className={`px-4 py-2 text-center ${color}`}>
                                                        <Text className="font-medium text-sm">
                                                            {data.percentage.toFixed(0)}%
                                                        </Text>
                                                        <Text className="text-xs text-gray-600">
                                                            {data.customers}
                                                        </Text>
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default Customers;