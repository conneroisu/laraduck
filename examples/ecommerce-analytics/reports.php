<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/app/Analytics/SalesAnalytics.php';
require __DIR__ . '/app/Analytics/CustomerAnalytics.php';
require __DIR__ . '/app/Analytics/ProductAnalytics.php';

use App\Analytics\SalesAnalytics;
use App\Analytics\CustomerAnalytics;
use App\Analytics\ProductAnalytics;

output("E-Commerce Analytics - Comprehensive Reports", 'info');
output("==========================================\n", 'info');

try {
    $reportsDir = __DIR__ . '/exports/reports';
    if (!is_dir($reportsDir)) {
        mkdir($reportsDir, 0777, true);
    }
    
    // 1. Monthly Business Report
    output("1. Generating Monthly Business Report...", 'info');
    
    $startDate = now()->startOfMonth();
    $endDate = now()->endOfMonth();
    
    // Executive metrics
    $dashboard = SalesAnalytics::executiveDashboard($startDate, $endDate);
    
    // Sales by category
    $categoryRevenue = SalesAnalytics::revenueBreakdown('category', $startDate, $endDate);
    
    // Customer segments
    $segments = CustomerAnalytics::customerSegmentation();
    
    // Top products
    $topProducts = \App\Models\OrderItem::topSellingProducts(20, $startDate, $endDate)->get();
    
    // Create report
    $report = "MONTHLY BUSINESS REPORT\n";
    $report .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
    $report .= "Period: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}\n\n";
    
    $report .= "EXECUTIVE SUMMARY\n";
    $report .= "-----------------\n";
    $report .= "Total Orders: " . number_format($dashboard['current_period']['total_orders']) . "\n";
    $report .= "Total Revenue: $" . number_format($dashboard['current_period']['total_revenue'], 2) . "\n";
    $report .= "Unique Customers: " . number_format($dashboard['current_period']['unique_customers']) . "\n";
    $report .= "Average Order Value: $" . number_format($dashboard['current_period']['avg_order_value'], 2) . "\n\n";
    
    $report .= "REVENUE BY CATEGORY\n";
    $report .= "------------------\n";
    foreach ($categoryRevenue as $cat) {
        $report .= sprintf("%-20s $%12s (%5.1f%%)\n", 
            $cat->category, 
            number_format($cat->revenue, 2),
            ($cat->revenue / $categoryRevenue->sum('revenue')) * 100
        );
    }
    
    file_put_contents($reportsDir . '/monthly_report_' . now()->format('Y_m') . '.txt', $report);
    output("   ✓ Saved to: reports/monthly_report_" . now()->format('Y_m') . ".txt", 'success');
    
    // 2. Customer Analytics Report (CSV)
    output("\n2. Generating Customer Analytics Report...", 'info');
    
    $customerReport = CustomerAnalytics::rfmAnalysis()->limit(1000)->get();
    
    $csv = "customer_id,email,segment,recency_days,frequency,monetary,r_score,f_score,m_score,rfm_segment\n";
    foreach ($customerReport as $customer) {
        $csv .= "{$customer->customer_id},{$customer->email},{$customer->customer_segment}," .
                "{$customer->recency},{$customer->frequency},{$customer->monetary}," .
                "{$customer->r_score},{$customer->f_score},{$customer->m_score},{$customer->rfm_segment}\n";
    }
    
    file_put_contents($reportsDir . '/customer_rfm_analysis.csv', $csv);
    output("   ✓ Saved to: reports/customer_rfm_analysis.csv", 'success');
    
    // 3. Product Performance Report (JSON)
    output("\n3. Generating Product Performance Report...", 'info');
    
    $productPerformance = ProductAnalytics::productPerformanceDashboard();
    
    file_put_contents(
        $reportsDir . '/product_performance.json', 
        json_encode($productPerformance, JSON_PRETTY_PRINT)
    );
    output("   ✓ Saved to: reports/product_performance.json", 'success');
    
    // 4. Sales Forecast Report
    output("\n4. Generating Sales Forecast...", 'info');
    
    $forecast = SalesAnalytics::salesForecast(30, 90);
    
    $forecastCsv = "date,actual_revenue,ma7,ma30,forecast_revenue,confidence_lower,confidence_upper\n";
    
    // Historical data
    foreach ($forecast['historical'] as $day) {
        $forecastCsv .= "{$day->date},{$day->revenue},{$day->ma7},{$day->ma30},,,\n";
    }
    
    // Forecast data
    foreach ($forecast['forecast'] as $day) {
        $forecastCsv .= "{$day['date']},,,,{$day['forecast_revenue']},{$day['confidence_lower']},{$day['confidence_upper']}\n";
    }
    
    file_put_contents($reportsDir . '/sales_forecast.csv', $forecastCsv);
    output("   ✓ Saved to: reports/sales_forecast.csv", 'success');
    
    // 5. Retention Cohort Report
    output("\n5. Generating Retention Cohort Report...", 'info');
    
    $retention = CustomerAnalytics::retentionAnalysis('month', 12);
    
    // Pivot retention data for better visualization
    $cohorts = collect($retention)->groupBy('cohort_month');
    $retentionMatrix = [];
    
    foreach ($cohorts as $cohort => $data) {
        $row = ['cohort' => date('Y-m', strtotime($cohort))];
        foreach ($data as $month) {
            $row['month_' . $month->months_since_first] = $month->retention_rate;
        }
        $retentionMatrix[] = $row;
    }
    
    // Create CSV with retention matrix
    $headers = ['cohort'];
    for ($i = 0; $i <= 12; $i++) {
        $headers[] = "month_{$i}";
    }
    
    $retentionCsv = implode(',', $headers) . "\n";
    foreach ($retentionMatrix as $row) {
        $values = [$row['cohort']];
        for ($i = 0; $i <= 12; $i++) {
            $values[] = $row["month_{$i}"] ?? '';
        }
        $retentionCsv .= implode(',', $values) . "\n";
    }
    
    file_put_contents($reportsDir . '/retention_cohorts.csv', $retentionCsv);
    output("   ✓ Saved to: reports/retention_cohorts.csv", 'success');
    
    // 6. Export all reports to Parquet for further analysis
    output("\n6. Creating Parquet archive of all analytics...", 'info');
    
    // Sales trend data
    SalesAnalytics::salesTrend('day', 365)
        ->toParquet($reportsDir . '/sales_trend_yearly.parquet');
    
    // Customer data with RFM
    CustomerAnalytics::rfmAnalysis()
        ->toParquet($reportsDir . '/customer_rfm_full.parquet');
    
    // Product ABC analysis
    ProductAnalytics::abcAnalysis()
        ->toParquet($reportsDir . '/product_abc_analysis.parquet');
    
    output("   ✓ Created Parquet archives for further analysis", 'success');
    
    // Summary
    output("\n=== Report Generation Complete ===", 'success');
    output("All reports saved to: " . $reportsDir, 'info');
    
    $reportFiles = glob($reportsDir . '/*');
    output("\nGenerated files:", 'info');
    foreach ($reportFiles as $file) {
        if (is_file($file)) {
            $size = filesize($file);
            $sizeFormatted = $size > 1024*1024 
                ? number_format($size/1024/1024, 2) . ' MB'
                : number_format($size/1024, 2) . ' KB';
            
            output("  - " . basename($file) . " ({$sizeFormatted})", 'info');
        }
    }
    
} catch (\Exception $e) {
    output("Error: " . $e->getMessage(), 'error');
    output("Stack trace: " . $e->getTraceAsString(), 'error');
}