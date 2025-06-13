<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use Carbon\Carbon;

class AnalyticsSeeder extends Seeder
{
    public function run()
    {
        // Create products
        $products = [
            ['id' => 1, 'name' => 'Wireless Headphones', 'category' => 'Electronics', 'price' => 99.99, 'cost' => 45.00],
            ['id' => 2, 'name' => 'Smart Watch', 'category' => 'Electronics', 'price' => 299.99, 'cost' => 150.00],
            ['id' => 3, 'name' => 'Laptop Stand', 'category' => 'Accessories', 'price' => 49.99, 'cost' => 20.00],
            ['id' => 4, 'name' => 'Wireless Mouse', 'category' => 'Electronics', 'price' => 29.99, 'cost' => 12.00],
            ['id' => 5, 'name' => 'USB-C Hub', 'category' => 'Accessories', 'price' => 79.99, 'cost' => 35.00],
            ['id' => 6, 'name' => 'Bluetooth Speaker', 'category' => 'Electronics', 'price' => 149.99, 'cost' => 70.00],
            ['id' => 7, 'name' => 'Phone Case', 'category' => 'Accessories', 'price' => 19.99, 'cost' => 5.00],
            ['id' => 8, 'name' => 'Power Bank', 'category' => 'Electronics', 'price' => 39.99, 'cost' => 18.00],
            ['id' => 9, 'name' => 'Webcam', 'category' => 'Electronics', 'price' => 89.99, 'cost' => 40.00],
            ['id' => 10, 'name' => 'Desk Organizer', 'category' => 'Accessories', 'price' => 24.99, 'cost' => 8.00],
        ];

        foreach ($products as $product) {
            Product::create([
                'id' => $product['id'],
                'name' => $product['name'],
                'category' => $product['category'],
                'price' => $product['price'],
                'cost' => $product['cost'],
                'description' => 'High-quality ' . $product['name'],
                'active' => true
            ]);
        }

        // Create customers
        $regions = ['North America', 'Europe', 'Asia', 'Australia'];
        $customerTypes = ['regular', 'premium', 'enterprise'];

        for ($i = 1; $i <= 50; $i++) {
            Customer::create([
                'id' => $i,
                'name' => 'Customer ' . $i,
                'email' => 'customer' . $i . '@example.com',
                'phone' => '+1-' . rand(100, 999) . '-' . rand(100, 999) . '-' . rand(1000, 9999),
                'region' => $regions[array_rand($regions)],
                'acquisition_date' => Carbon::now()->subDays(rand(30, 365)),
                'customer_type' => $customerTypes[array_rand($customerTypes)]
            ]);
        }

        // Create sales data for the last 90 days
        $channels = ['online', 'retail', 'wholesale'];
        $startDate = Carbon::now()->subDays(90);

        for ($i = 1; $i <= 500; $i++) {
            $product = $products[array_rand($products)];
            $customerId = rand(1, 50);
            $customer = Customer::find($customerId);
            $quantity = rand(1, 5);
            $unitPrice = $product['price'];
            $discountPercent = rand(0, 20) / 100;
            $discountAmount = $unitPrice * $quantity * $discountPercent;
            $totalAmount = ($unitPrice * $quantity) - $discountAmount;

            Sale::create([
                'id' => $i,
                'product_id' => $product['id'],
                'customer_id' => $customerId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'sale_date' => $startDate->copy()->addDays(rand(0, 90))->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                'region' => $customer->region,
                'channel' => $channels[array_rand($channels)]
            ]);
        }
    }
}