<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class DataGenerator
{
    protected $faker;
    protected $customers = [];
    protected $products = [];
    
    protected $regions = ['North America', 'Europe', 'Asia', 'South America', 'Africa', 'Oceania'];
    protected $channels = ['web', 'mobile', 'store'];
    protected $paymentMethods = ['credit_card', 'paypal', 'bank_transfer', 'crypto'];
    protected $customerSegments = ['Premium', 'Regular', 'Occasional'];
    protected $ageGroups = ['18-24', '25-34', '35-44', '45-54', '55+'];
    
    protected $categories = [
        'Electronics' => ['Smartphones', 'Laptops', 'Tablets', 'Accessories'],
        'Clothing' => ['Men', 'Women', 'Kids', 'Shoes'],
        'Home & Garden' => ['Furniture', 'Decor', 'Kitchen', 'Garden'],
        'Sports' => ['Fitness', 'Outdoor', 'Team Sports', 'Water Sports'],
        'Books' => ['Fiction', 'Non-Fiction', 'Educational', 'Comics']
    ];
    
    protected $brands = [
        'Electronics' => ['TechCorp', 'ElectroMax', 'DigitalPro', 'SmartLife'],
        'Clothing' => ['FashionForward', 'StyleHub', 'TrendSetters', 'UrbanWear'],
        'Home & Garden' => ['HomeStyle', 'GardenPro', 'CozyLiving', 'ModernSpace'],
        'Sports' => ['AthletePro', 'FitGear', 'SportMax', 'ActiveLife'],
        'Books' => ['ReadMore', 'BookWorld', 'LitHub', 'PageTurners']
    ];
    
    public function __construct()
    {
        $this->faker = Faker::create();
    }
    
    public function generate($customerCount = 10000, $productCount = 500, $orderCount = 50000)
    {
        output("Starting data generation...", 'info');
        
        // Generate customers
        output("Generating {$customerCount} customers...", 'info');
        $this->generateCustomers($customerCount);
        
        // Generate products
        output("Generating {$productCount} products...", 'info');
        $this->generateProducts($productCount);
        
        // Generate orders
        output("Generating {$orderCount} orders...", 'info');
        $this->generateOrders($orderCount);
        
        output("Data generation completed!", 'success');
    }
    
    protected function generateCustomers($count)
    {
        $batchSize = 100;
        $batches = ceil($count / $batchSize);
        
        for ($i = 0; $i < $batches; $i++) {
            $data = [];
            $currentBatchSize = min($batchSize, $count - ($i * $batchSize));
            
            for ($j = 0; $j < $currentBatchSize; $j++) {
                $customerId = 'CUST' . str_pad(($i * $batchSize) + $j + 1, 8, '0', STR_PAD_LEFT);
                
                $customer = [
                    'customer_id' => $customerId,
                    'email' => $this->faker->unique()->safeEmail,
                    'first_name' => $this->faker->firstName,
                    'last_name' => $this->faker->lastName,
                    'registration_date' => $this->faker->dateTimeBetween('-3 years', '-1 week')->format('Y-m-d'),
                    'country' => $this->faker->country,
                    'city' => $this->faker->city,
                    'age_group' => $this->faker->randomElement($this->ageGroups),
                    'gender' => $this->faker->randomElement(['M', 'F', 'Other']),
                    'customer_segment' => $this->getCustomerSegment()
                ];
                
                $data[] = $customer;
                $this->customers[] = $customerId;
            }
            
            Customer::insertBatch($data, 10);
            output("  Generated " . (($i + 1) * $batchSize) . " customers", 'info');
        }
    }
    
    protected function generateProducts($count)
    {
        $data = [];
        $productIndex = 1;
        
        foreach ($this->categories as $category => $subcategories) {
            $productsPerCategory = (int)($count / count($this->categories));
            
            for ($i = 0; $i < $productsPerCategory; $i++) {
                $subcategory = $this->faker->randomElement($subcategories);
                $brand = $this->faker->randomElement($this->brands[$category]);
                $baseCost = $this->faker->randomFloat(2, 10, 500);
                
                $product = [
                    'product_id' => 'PROD' . str_pad($productIndex++, 6, '0', STR_PAD_LEFT),
                    'sku' => strtoupper($this->faker->unique()->lexify('???-???-####')),
                    'name' => $brand . ' ' . $this->faker->words(3, true),
                    'category' => $category,
                    'subcategory' => $subcategory,
                    'brand' => $brand,
                    'cost' => $baseCost,
                    'price' => $baseCost * $this->faker->randomFloat(2, 1.2, 3.0), // 20-200% markup
                    'weight' => $this->faker->randomFloat(2, 0.1, 20),
                    'launch_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                    'is_active' => $this->faker->boolean(90) // 90% active
                ];
                
                $data[] = $product;
                $this->products[] = $product['product_id'];
            }
        }
        
        Product::insertBatch($data, 10);
    }
    
    protected function generateOrders($count)
    {
        $batchSize = 100;
        $batches = ceil($count / $batchSize);
        $startDate = now()->subYears(2);
        
        for ($i = 0; $i < $batches; $i++) {
            $orderData = [];
            $itemData = [];
            $currentBatchSize = min($batchSize, $count - ($i * $batchSize));
            
            for ($j = 0; $j < $currentBatchSize; $j++) {
                $orderId = 'ORD' . str_pad(($i * $batchSize) + $j + 1, 10, '0', STR_PAD_LEFT);
                $customerId = $this->faker->randomElement($this->customers);
                $orderDate = $this->faker->dateTimeBetween($startDate, 'now');
                $itemCount = $this->faker->numberBetween(1, 8);
                $orderTotal = 0;
                
                // Generate order
                $order = [
                    'order_id' => $orderId,
                    'customer_id' => $customerId,
                    'order_date' => $orderDate->format('Y-m-d H:i:s'),
                    'status' => $this->getOrderStatus($orderDate),
                    'shipping_address' => json_encode([
                        'street' => $this->faker->streetAddress,
                        'city' => $this->faker->city,
                        'state' => $this->faker->state,
                        'postal_code' => $this->faker->postcode,
                        'country' => $this->faker->country
                    ]),
                    'payment_method' => $this->faker->randomElement($this->paymentMethods),
                    'region' => $this->faker->randomElement($this->regions),
                    'channel' => $this->getOrderChannel()
                ];
                
                // Generate order items
                $selectedProducts = $this->faker->randomElements($this->products, $itemCount);
                foreach ($selectedProducts as $productId) {
                    $product = Product::where('product_id', $productId)->first();
                    if (!$product) continue;
                    
                    $quantity = $this->faker->numberBetween(1, 5);
                    $discount = $this->faker->boolean(30) ? $this->faker->randomFloat(2, 0, $product->price * 0.3) : 0;
                    $itemPrice = ($product->price - $discount) * $quantity;
                    $tax = $itemPrice * 0.08; // 8% tax
                    $totalPrice = $itemPrice + $tax;
                    
                    $itemData[] = [
                        'order_id' => $orderId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                        'discount' => $discount,
                        'tax' => $tax,
                        'total_price' => $totalPrice
                    ];
                    
                    $orderTotal += $totalPrice;
                }
                
                $order['total_amount'] = $orderTotal;
                $orderData[] = $order;
            }
            
            Order::insertBatch($orderData, 10);
            OrderItem::insertBatch($itemData, 10);
            output("  Generated " . (($i + 1) * $batchSize) . " orders", 'info');
        }
    }
    
    protected function getCustomerSegment()
    {
        $rand = $this->faker->randomFloat(2, 0, 1);
        if ($rand < 0.1) return 'Premium';      // 10%
        if ($rand < 0.4) return 'Regular';      // 30%
        return 'Occasional';                     // 60%
    }
    
    protected function getOrderStatus($orderDate)
    {
        $daysSince = now()->diffInDays($orderDate);
        
        if ($daysSince < 7) {
            return $this->faker->randomElement(['pending', 'processing', 'completed']);
        }
        
        $rand = $this->faker->randomFloat(2, 0, 1);
        if ($rand < 0.85) return 'completed';   // 85%
        if ($rand < 0.95) return 'refunded';    // 10%
        return 'cancelled';                      // 5%
    }
    
    protected function getOrderChannel()
    {
        $rand = $this->faker->randomFloat(2, 0, 1);
        if ($rand < 0.6) return 'web';          // 60%
        if ($rand < 0.85) return 'mobile';      // 25%
        return 'store';                          // 15%
    }
}