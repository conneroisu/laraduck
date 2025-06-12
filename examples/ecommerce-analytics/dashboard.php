<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/app/Analytics/SalesAnalytics.php';
require __DIR__ . '/app/Analytics/CustomerAnalytics.php';
require __DIR__ . '/app/Analytics/ProductAnalytics.php';
require __DIR__ . '/app/Models/Customer.php';
require __DIR__ . '/app/Models/Product.php';
require __DIR__ . '/app/Models/Order.php';
require __DIR__ . '/app/Models/OrderItem.php';

use App\Analytics\SalesAnalytics;
use App\Analytics\CustomerAnalytics;
use App\Analytics\ProductAnalytics;

// Simple web dashboard using PHP's built-in server
// Run with: php -S localhost:8000 dashboard.php

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestUri = strtok($requestUri, '?');

// API endpoints
if (strpos($requestUri, '/api/') === 0) {
    header('Content-Type: application/json');
    
    try {
        switch ($requestUri) {
            case '/api/dashboard':
                echo json_encode(SalesAnalytics::executiveDashboard());
                break;
                
            case '/api/sales-trend':
                $days = $_GET['days'] ?? 30;
                $granularity = $_GET['granularity'] ?? 'day';
                echo json_encode(SalesAnalytics::salesTrend($granularity, $days));
                break;
                
            case '/api/revenue-breakdown':
                $dimension = $_GET['dimension'] ?? 'category';
                echo json_encode(SalesAnalytics::revenueBreakdown($dimension));
                break;
                
            case '/api/top-products':
                $limit = $_GET['limit'] ?? 10;
                echo json_encode(\App\Models\OrderItem::topSellingProducts($limit)->get());
                break;
                
            case '/api/customer-segments':
                $segments = CustomerAnalytics::customerSegmentation();
                echo json_encode($segments);
                break;
                
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Serve the HTML dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Analytics Dashboard - Laravel DuckDB</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">E-Commerce Analytics Dashboard</h1>
        <p class="text-gray-600 mb-8">Powered by Laravel DuckDB Extension</p>
        
        <!-- Executive Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Total Orders</h3>
                <p class="text-2xl font-bold text-gray-800" id="totalOrders">-</p>
                <p class="text-sm mt-2">
                    <span id="ordersGrowth" class="font-medium">-</span>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Revenue</h3>
                <p class="text-2xl font-bold text-gray-800" id="totalRevenue">-</p>
                <p class="text-sm mt-2">
                    <span id="revenueGrowth" class="font-medium">-</span>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Customers</h3>
                <p class="text-2xl font-bold text-gray-800" id="uniqueCustomers">-</p>
                <p class="text-sm mt-2">
                    <span id="customersGrowth" class="font-medium">-</span>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Avg Order Value</h3>
                <p class="text-2xl font-bold text-gray-800" id="avgOrderValue">-</p>
                <p class="text-sm mt-2">
                    <span id="aovGrowth" class="font-medium">-</span>
                </p>
            </div>
        </div>
        
        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Sales Trend Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Sales Trend (Last 30 Days)</h2>
                <canvas id="salesTrendChart"></canvas>
            </div>
            
            <!-- Revenue by Category -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Revenue by Category</h2>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
        
        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Top Products -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Top Selling Products</h2>
                <div id="topProducts" class="space-y-2"></div>
            </div>
            
            <!-- Customer Segments -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Customer Segments</h2>
                <canvas id="segmentsChart"></canvas>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm mt-8">
            <p>This dashboard demonstrates the Laravel DuckDB extension capabilities</p>
            <p>Data is queried directly from DuckDB using analytical queries</p>
        </div>
    </div>
    
    <script>
        // Helper function to display errors inline
        function showError(section, error) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
            errorDiv.innerHTML = `
                <strong class="font-bold">${section} Error:</strong>
                <span class="block sm:inline">${error.message || 'Failed to load data'}</span>
                <details class="mt-2">
                    <summary class="cursor-pointer font-medium">Error Details</summary>
                    <pre class="mt-2 text-xs bg-red-50 p-2 rounded overflow-auto">${error.stack || JSON.stringify(error, null, 2)}</pre>
                </details>
            `;
            
            const container = document.querySelector('.container');
            container.insertBefore(errorDiv, container.firstChild);
        }

        // Fetch and display dashboard data
        async function loadDashboard() {
            // Load executive metrics
            try {
                const dashboardData = await fetch('/api/dashboard').then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    return r.json();
                });
                
                document.getElementById('totalOrders').textContent = dashboardData.current_period.total_orders.toLocaleString();
                document.getElementById('totalRevenue').textContent = '$' + dashboardData.current_period.total_revenue.toLocaleString();
                document.getElementById('uniqueCustomers').textContent = dashboardData.current_period.unique_customers.toLocaleString();
                document.getElementById('avgOrderValue').textContent = '$' + dashboardData.current_period.avg_order_value.toFixed(2);
                
                // Growth indicators
                const growthElements = [
                    { id: 'ordersGrowth', value: dashboardData.growth.orders_growth },
                    { id: 'revenueGrowth', value: dashboardData.growth.revenue_growth },
                    { id: 'customersGrowth', value: dashboardData.growth.customers_growth },
                    { id: 'aovGrowth', value: dashboardData.growth.aov_growth }
                ];
                
                growthElements.forEach(el => {
                    const element = document.getElementById(el.id);
                    const value = el.value;
                    element.textContent = (value >= 0 ? '+' : '') + value + '%';
                    element.className = 'font-medium ' + (value >= 0 ? 'text-green-600' : 'text-red-600');
                });
            } catch (error) {
                showError('Executive Dashboard', error);
            }
                
            // Load sales trend
            try {
                const salesTrend = await fetch('/api/sales-trend?days=30').then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    return r.json();
                });
                
                const salesCtx = document.getElementById('salesTrendChart').getContext('2d');
                new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: salesTrend.map(d => new Date(d.period).toLocaleDateString()),
                        datasets: [{
                            label: 'Revenue',
                            data: salesTrend.map(d => d.revenue),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                showError('Sales Trend Chart', error);
            }
                
            // Load revenue by category
            try {
                const categoryData = await fetch('/api/revenue-breakdown?dimension=category').then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    return r.json();
                });
                
                const categoryCtx = document.getElementById('categoryChart').getContext('2d');
                new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: categoryData.map(d => d.category),
                        datasets: [{
                            data: categoryData.map(d => d.revenue),
                            backgroundColor: [
                                'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)',
                                'rgb(251, 146, 60)',
                                'rgb(147, 51, 234)',
                                'rgb(244, 63, 94)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            } catch (error) {
                showError('Category Chart', error);
            }
                
            // Load top products
            try {
                const topProducts = await fetch('/api/top-products?limit=5').then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    return r.json();
                });
                
                const productsHtml = topProducts.map((p, i) => `
                    <div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded">
                        <div>
                            <span class="text-gray-500">${i + 1}.</span>
                            <span class="font-medium">${p.product_name}</span>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">$${parseFloat(p.revenue).toLocaleString()}</div>
                            <div class="text-sm text-gray-500">${p.units_sold} units</div>
                        </div>
                    </div>
                `).join('');
                
                document.getElementById('topProducts').innerHTML = productsHtml;
            } catch (error) {
                showError('Top Products', error);
            }
                
            // Load customer segments
            try {
                const segments = await fetch('/api/customer-segments').then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    return r.json();
                });
                
                const segmentCtx = document.getElementById('segmentsChart').getContext('2d');
                new Chart(segmentCtx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(segments),
                        datasets: [{
                            label: 'Customers',
                            data: Object.values(segments).map(s => s.count),
                            backgroundColor: 'rgba(59, 130, 246, 0.8)'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            } catch (error) {
                showError('Customer Segments Chart', error);
            }
        }
        
        // Load dashboard on page load
        loadDashboard();
    </script>
</body>
</html>