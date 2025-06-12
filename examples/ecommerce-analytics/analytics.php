<?php

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/app/Analytics/SalesAnalytics.php';
require __DIR__ . '/app/Analytics/CustomerAnalytics.php';
require __DIR__ . '/app/Analytics/ProductAnalytics.php';

use App\Analytics\SalesAnalytics;
use App\Analytics\CustomerAnalytics;
use App\Analytics\ProductAnalytics;

output("E-Commerce Analytics Examples", 'info');
output("=============================\n", 'info');

try {
    // 1. Executive Dashboard
    output("1. EXECUTIVE DASHBOARD", 'info');
    output("---------------------", 'info');
    
    $dashboard = SalesAnalytics::executiveDashboard();
    
    output("\nCurrent Period ({$dashboard['current_period']['start_date']} to {$dashboard['current_period']['end_date']}):", 'info');
    output("  Orders: " . number_format($dashboard['current_period']['total_orders']), 'info');
    output("  Revenue: $" . number_format($dashboard['current_period']['total_revenue'], 2), 'info');
    output("  Customers: " . number_format($dashboard['current_period']['unique_customers']), 'info');
    output("  AOV: $" . number_format($dashboard['current_period']['avg_order_value'], 2), 'info');
    
    output("\nGrowth vs Previous Period:", 'info');
    output("  Orders: " . ($dashboard['growth']['orders_growth'] >= 0 ? '+' : '') . $dashboard['growth']['orders_growth'] . "%", 
           $dashboard['growth']['orders_growth'] >= 0 ? 'success' : 'warning');
    output("  Revenue: " . ($dashboard['growth']['revenue_growth'] >= 0 ? '+' : '') . $dashboard['growth']['revenue_growth'] . "%",
           $dashboard['growth']['revenue_growth'] >= 0 ? 'success' : 'warning');
    
    // 2. Sales Trend
    output("\n\n2. SALES TREND (Last 7 Days)", 'info');
    output("----------------------------", 'info');
    
    $salesTrend = SalesAnalytics::salesTrend('day', 7);
    
    foreach ($salesTrend as $day) {
        $date = date('Y-m-d', strtotime($day->period));
        output("  {$date}: {$day->order_count} orders, $" . number_format($day->revenue, 2), 'info');
    }
    
    // 3. Revenue Breakdown
    output("\n\n3. REVENUE BY CATEGORY", 'info');
    output("----------------------", 'info');
    
    $revenueBreakdown = SalesAnalytics::revenueBreakdown('category');
    
    foreach ($revenueBreakdown->take(5) as $category) {
        $percentage = ($category->revenue / $revenueBreakdown->sum('revenue')) * 100;
        output("  {$category->category}: $" . number_format($category->revenue, 2) . 
               " (" . round($percentage, 1) . "%)", 'info');
    }
    
    // 4. Customer Segmentation
    output("\n\n4. CUSTOMER SEGMENTATION (RFM)", 'info');
    output("------------------------------", 'info');
    
    $segmentation = CustomerAnalytics::customerSegmentation();
    
    foreach ($segmentation['segments'] as $segment => $data) {
        output("  {$segment}: {$data['count']} customers, $" . 
               number_format($data['total_value'], 2) . " total value", 'info');
    }
    
    // 5. Product Performance
    output("\n\n5. TOP SELLING PRODUCTS", 'info');
    output("----------------------", 'info');
    
    $topProducts = \App\Models\OrderItem::topSellingProducts(5)->get();
    
    foreach ($topProducts as $index => $product) {
        output("  " . ($index + 1) . ". {$product->product_name}: " . 
               number_format($product->units_sold) . " units, $" . 
               number_format($product->revenue, 2), 'info');
    }
    
    // 6. Market Basket Analysis
    output("\n\n6. FREQUENTLY BOUGHT TOGETHER", 'info');
    output("----------------------------", 'info');
    
    $basketAnalysis = ProductAnalytics::marketBasketAnalysis(0.001, 0.1);
    
    foreach ($basketAnalysis->take(5) as $basket) {
        output("  {$basket->product_a_name} + {$basket->product_b_name}:", 'info');
        output("    Support: " . round($basket->support * 100, 2) . "%", 'info');
        output("    Confidence: " . round($basket->confidence_a_to_b * 100, 1) . "%", 'info');
        output("    Lift: " . round($basket->lift, 2), 'info');
    }
    
    // 7. Customer Lifetime Value
    output("\n\n7. CUSTOMER LIFETIME VALUE BY COHORT", 'info');
    output("-----------------------------------", 'info');
    
    $cltvAnalysis = CustomerAnalytics::lifetimeValueAnalysis('month');
    
    $cohortSummary = collect($cltvAnalysis)
        ->groupBy('cohort')
        ->map(function ($cohort) {
            return [
                'cohort_size' => $cohort->first()->cohort_size,
                'max_ltv' => $cohort->max('cumulative_ltv'),
                'periods_tracked' => $cohort->count()
            ];
        })
        ->take(6);
    
    foreach ($cohortSummary as $cohort => $data) {
        output("  " . date('Y-m', strtotime($cohort)) . ": " . 
               number_format($data['cohort_size']) . " customers, $" . 
               number_format($data['max_ltv'], 2) . " LTV", 'info');
    }
    
    // 8. Year-over-Year Comparison
    output("\n\n8. YEAR-OVER-YEAR COMPARISON", 'info');
    output("---------------------------", 'info');
    
    $yoyComparison = SalesAnalytics::yearOverYearComparison('revenue', 'month');
    
    foreach ($yoyComparison->take(12) as $month) {
        if ($month->current_year && $month->last_year) {
            output("  Month {$month->period_number}: " . 
                   ($month->yoy_growth >= 0 ? '+' : '') . 
                   round($month->yoy_growth, 1) . "% " .
                   "($" . number_format($month->last_year, 0) . " → $" . 
                   number_format($month->current_year, 0) . ")", 
                   $month->yoy_growth >= 0 ? 'success' : 'warning');
        }
    }
    
    // 9. Product Velocity
    output("\n\n9. FASTEST MOVING PRODUCTS", 'info');
    output("-------------------------", 'info');
    
    $velocity = ProductAnalytics::productVelocityAnalysis();
    
    foreach ($velocity->take(5) as $product) {
        output("  {$product->name}:", 'info');
        output("    Velocity: " . round($product->velocity_score, 2) . " units/day", 'info');
        output("    Consistency: " . round($product->consistency_score * 100, 1) . "%", 'info');
    }
    
    // 10. Customer Retention
    output("\n\n10. RETENTION ANALYSIS", 'info');
    output("---------------------", 'info');
    
    $retention = CustomerAnalytics::retentionAnalysis('month', 6);
    
    $recentCohort = collect($retention)
        ->where('cohort_month', '>=', now()->subMonths(6)->startOfMonth())
        ->groupBy('cohort_month')
        ->first();
    
    if ($recentCohort) {
        output("  Cohort: " . date('Y-m', strtotime($recentCohort->first()->cohort_month)), 'info');
        foreach ($recentCohort as $month) {
            output("    Month {$month->months_since_first}: {$month->retention_rate}% retained", 'info');
        }
    }
    
    output("\n✓ Analytics examples completed successfully!", 'success');
    
} catch (\Exception $e) {
    output("Error: " . $e->getMessage(), 'error');
    output("Stack trace: " . $e->getTraceAsString(), 'error');
}