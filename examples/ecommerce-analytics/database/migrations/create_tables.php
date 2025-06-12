<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Laraduck\EloquentDuckDB\Schema\Blueprint;

class CreateTables
{
    public function up()
    {
        $schema = Capsule::schema('duckdb');
        
        // Create customers table
        $schema->create('customers', function (Blueprint $table) {
            $table->string('customer_id')->primary();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('registration_date');
            $table->string('country');
            $table->string('city');
            $table->string('age_group'); // '18-24', '25-34', '35-44', '45-54', '55+'
            $table->string('gender')->nullable(); // 'M', 'F', 'Other'
            $table->string('customer_segment'); // 'Premium', 'Regular', 'Occasional'
            $table->index('country');
            $table->index('customer_segment');
        });
        
        // Create products table
        $schema->create('products', function (Blueprint $table) {
            $table->string('product_id')->primary();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('subcategory');
            $table->string('brand');
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->decimal('weight', 8, 2)->nullable();
            $table->date('launch_date');
            $table->boolean('is_active')->default(true);
            $table->index('category');
            $table->index('brand');
        });
        
        // Create orders table
        $schema->create('orders', function (Blueprint $table) {
            $table->string('order_id')->primary();
            $table->string('customer_id');
            $table->timestamp('order_date');
            $table->string('status'); // 'pending', 'processing', 'completed', 'cancelled', 'refunded'
            $table->decimal('total_amount', 12, 2);
            $table->json('shipping_address');
            $table->string('payment_method'); // 'credit_card', 'paypal', 'bank_transfer'
            $table->string('region'); // 'North America', 'Europe', 'Asia', etc.
            $table->string('channel'); // 'web', 'mobile', 'store'
            $table->index('customer_id');
            $table->index('order_date');
            $table->index('status');
            $table->index(['region', 'order_date']);
        });
        
        // Create order_items table
        $schema->create('order_items', function (Blueprint $table) {
            $table->string('order_id');
            $table->string('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->index('order_id');
            $table->index('product_id');
            $table->index(['order_id', 'product_id']);
        });
        
        // Create product_views table for web analytics
        $schema->create('product_views', function (Blueprint $table) {
            $table->string('session_id');
            $table->string('customer_id')->nullable();
            $table->string('product_id');
            $table->timestamp('view_timestamp');
            $table->integer('duration_seconds');
            $table->string('referrer_source'); // 'search', 'social', 'email', 'direct'
            $table->string('device_type'); // 'desktop', 'mobile', 'tablet'
            $table->index('product_id');
            $table->index('view_timestamp');
            $table->index('customer_id');
        });
        
        // Create marketing_campaigns table
        $schema->create('marketing_campaigns', function (Blueprint $table) {
            $table->string('campaign_id')->primary();
            $table->string('campaign_name');
            $table->string('campaign_type'); // 'email', 'social', 'ppc', 'affiliate'
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('budget', 12, 2);
            $table->decimal('actual_spend', 12, 2);
            $table->json('target_segments'); // Array of customer segments
            $table->string('status'); // 'planned', 'active', 'completed'
        });
        
        // Create campaign_conversions table
        $schema->create('campaign_conversions', function (Blueprint $table) {
            $table->string('campaign_id');
            $table->string('order_id');
            $table->timestamp('conversion_timestamp');
            $table->string('attribution_model'); // 'first_touch', 'last_touch', 'linear'
            $table->decimal('attributed_revenue', 12, 2);
            $table->index(['campaign_id', 'conversion_timestamp']);
            $table->index('order_id');
        });
    }
    
    public function down()
    {
        $schema = Capsule::schema('duckdb');
        
        $schema->dropIfExists('campaign_conversions');
        $schema->dropIfExists('marketing_campaigns');
        $schema->dropIfExists('product_views');
        $schema->dropIfExists('order_items');
        $schema->dropIfExists('orders');
        $schema->dropIfExists('products');
        $schema->dropIfExists('customers');
    }
}