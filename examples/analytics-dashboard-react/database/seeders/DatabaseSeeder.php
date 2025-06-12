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
        
        // Clear existing data (skip if tables don't exist)
        try {
            DB::connection('duckdb')->table('sales')->truncate();
        } catch (\Exception $e) {
            // Table doesn't exist, skip
        }
        try {
            DB::connection('duckdb')->table('products')->truncate();
        } catch (\Exception $e) {
            // Table doesn't exist, skip
        }
        try {
            DB::connection('duckdb')->table('customers')->truncate();
        } catch (\Exception $e) {
            // Table doesn't exist, skip
        }
        
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
        
        // Skip customers and products seeding since tables don't exist
        // Focus on sales table only
        
        echo "Seeding sales...\n";
        $sales = [];
        
        for ($i = 1; $i <= $numOrders; $i++) {
            $customerId = $faker->numberBetween(1, $numCustomers);
            $productId = $faker->numberBetween(1, $numProducts);
            $saleDate = $faker->dateTimeBetween($startDate, 'now');
            $region = $faker->randomElement($regions);
            $category = $faker->randomElement($categories);
            $brand = $faker->randomElement($brands);
            $quantity = $faker->numberBetween(1, 5);
            $price = $faker->randomFloat(2, 20, 500);
            $cost = $price * $faker->randomFloat(2, 0.3, 0.7); // Cost is 30-70% of price
            $revenue = $quantity * $price;
            $profit = $revenue - ($quantity * $cost);
            
            $sales[] = [
                'id' => $i, // Manual ID since no auto-increment
                'customer_id' => $customerId,
                'product_id' => $productId,
                'region' => $region,
                'category' => $category,
                'brand' => $brand,
                'price' => $price,
                'quantity' => $quantity,
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit,
                'sale_date' => $saleDate->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            if (count($sales) >= 1000) {
                DB::connection('duckdb')->table('sales')->insert($sales);
                $sales = [];
                echo "  Inserted $i sales records\n";
            }
        }
        
        if (!empty($sales)) {
            DB::connection('duckdb')->table('sales')->insert($sales);
        }
        
        echo "Seeding complete!\n";
        echo "- Sales records: " . number_format($numOrders) . "\n";
    }
}