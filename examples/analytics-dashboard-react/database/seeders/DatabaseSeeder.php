<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        // Clear existing data
        DB::connection('duckdb')->table('sales')->truncate();
        DB::connection('duckdb')->table('products')->truncate();
        DB::connection('duckdb')->table('customers')->truncate();
        
        // Configuration
        $numCustomers = 10000;
        $numProducts = 500;
        $numOrders = 50000;
        $startDate = Carbon::now()->subYear();
        
        // Define constants
        $categories = ['Electronics', 'Clothing', 'Home & Garden', 'Sports', 'Books', 'Toys', 'Food & Beverage', 'Health & Beauty'];
        $brands = ['TechPro', 'StyleMax', 'HomeEssentials', 'SportZone', 'BookWorld', 'FunTime', 'GourmetPlus', 'WellnessFirst'];
        $regions = ['North', 'South', 'East', 'West'];
        $segments = ['Regular', 'VIP', 'New', 'At Risk'];
        $channels = ['Organic Search', 'Paid Search', 'Social Media', 'Email', 'Direct', 'Referral'];
        $paymentMethods = ['Credit Card', 'PayPal', 'Bank Transfer', 'Apple Pay', 'Google Pay'];
        
        echo "Seeding customers...\n";
        $customers = [];
        for ($i = 1; $i <= $numCustomers; $i++) {
            $firstPurchase = $faker->dateTimeBetween($startDate, '-30 days');
            $lastPurchase = $faker->dateTimeBetween($firstPurchase, 'now');
            $totalOrders = $faker->numberBetween(1, 50);
            $totalSpent = $faker->randomFloat(2, 50, 10000);
            
            $customers[] = [
                'customer_id' => $i,
                'name' => $faker->name,
                'email' => $faker->unique()->email,
                'phone' => $faker->phoneNumber,
                'segment' => $faker->randomElement($segments),
                'lifetime_value' => $totalSpent,
                'first_purchase_date' => $firstPurchase->format('Y-m-d'),
                'last_purchase_date' => $lastPurchase->format('Y-m-d'),
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'avg_order_value' => $totalSpent / max($totalOrders, 1),
                'preferred_category' => $faker->randomElement($categories),
                'preferred_brand' => $faker->randomElement($brands),
                'city' => $faker->city,
                'state' => $faker->state,
                'country' => $faker->country,
                'acquisition_channel' => $faker->randomElement($channels),
                'churn_risk_score' => $faker->randomFloat(2, 0, 1),
            ];
            
            if ($i % 1000 === 0) {
                DB::connection('duckdb')->table('customers')->insert($customers);
                $customers = [];
                echo "  Inserted $i customers\n";
            }
        }
        if (!empty($customers)) {
            DB::connection('duckdb')->table('customers')->insert($customers);
        }
        
        echo "Seeding products...\n";
        $products = [];
        for ($i = 1; $i <= $numProducts; $i++) {
            $category = $faker->randomElement($categories);
            $brand = $faker->randomElement($brands);
            $cost = $faker->randomFloat(2, 10, 500);
            $price = $cost * $faker->randomFloat(2, 1.2, 3.0);
            $margin = (($price - $cost) / $price) * 100;
            
            $products[] = [
                'product_id' => $i,
                'sku' => strtoupper($faker->bothify('??##??##')),
                'name' => $faker->words(3, true) . ' ' . $category,
                'category' => $category,
                'subcategory' => $faker->words(2, true),
                'brand' => $brand,
                'price' => $price,
                'cost' => $cost,
                'margin' => $margin,
                'weight' => $faker->randomFloat(2, 0.1, 50),
                'dimensions' => $faker->numberBetween(10, 100) . 'x' . $faker->numberBetween(10, 100) . 'x' . $faker->numberBetween(10, 100),
                'stock_quantity' => $faker->numberBetween(0, 1000),
                'reorder_point' => $faker->numberBetween(10, 100),
                'supplier' => $faker->company,
                'launch_date' => $faker->dateTimeBetween('-2 years', '-1 month')->format('Y-m-d'),
                'discontinue_date' => $faker->optional(0.1)->dateTimeBetween('+1 month', '+1 year')?->format('Y-m-d'),
                'is_active' => $faker->boolean(90),
                'rating' => $faker->randomFloat(1, 3.0, 5.0),
                'review_count' => $faker->numberBetween(0, 500),
            ];
            
            if ($i % 100 === 0) {
                DB::connection('duckdb')->table('products')->insert($products);
                $products = [];
                echo "  Inserted $i products\n";
            }
        }
        if (!empty($products)) {
            DB::connection('duckdb')->table('products')->insert($products);
        }
        
        echo "Seeding sales...\n";
        $sales = [];
        $orderId = 1000;
        
        for ($i = 1; $i <= $numOrders; $i++) {
            $customerId = $faker->numberBetween(1, $numCustomers);
            $saleDate = $faker->dateTimeBetween($startDate, 'now');
            $region = $faker->randomElement($regions);
            $paymentMethod = $faker->randomElement($paymentMethods);
            
            // Create 1-5 line items per order
            $numItems = $faker->numberBetween(1, 5);
            $orderTotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $productId = $faker->numberBetween(1, $numProducts);
                $quantity = $faker->numberBetween(1, 5);
                $unitPrice = $faker->randomFloat(2, 20, 500);
                $discountPercent = $faker->optional(0.3)->numberBetween(5, 30) ?? 0;
                $lineTotal = $quantity * $unitPrice;
                $discountAmount = $lineTotal * ($discountPercent / 100);
                $totalAmount = $lineTotal - $discountAmount;
                $orderTotal += $totalAmount;
                
                // Get product details for consistent data
                $productIndex = ($productId - 1) % count($categories);
                $category = $categories[$productIndex % count($categories)];
                $brand = $brands[$productIndex % count($brands)];
                
                $sales[] = [
                    'order_id' => $orderId,
                    'customer_id' => $customerId,
                    'product_id' => $productId,
                    'sale_date' => $saleDate->format('Y-m-d'),
                    'sale_time' => $saleDate->format('H:i:s'),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'category' => $category,
                    'brand' => $brand,
                    'region' => $region,
                    'channel' => $faker->randomElement($channels),
                    'payment_method' => $paymentMethod,
                    'shipping_cost' => $faker->randomFloat(2, 0, 20),
                    'tax_amount' => $totalAmount * 0.08,
                    'profit_margin' => $faker->randomFloat(2, 10, 40),
                ];
                
                if (count($sales) >= 1000) {
                    DB::connection('duckdb')->table('sales')->insert($sales);
                    $sales = [];
                    echo "  Inserted " . (($i - 1) * $numItems + $j) . " sales records\n";
                }
            }
            
            $orderId++;
        }
        
        if (!empty($sales)) {
            DB::connection('duckdb')->table('sales')->insert($sales);
        }
        
        echo "Seeding complete!\n";
        echo "- Customers: " . number_format($numCustomers) . "\n";
        echo "- Products: " . number_format($numProducts) . "\n";
        echo "- Orders: " . number_format($numOrders) . "\n";
        echo "- Sales records: ~" . number_format($numOrders * 3) . "\n";
    }
}