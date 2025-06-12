<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('product_id');
            $table->string('region', 50);
            $table->string('category', 100);
            $table->string('brand', 100);
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('revenue', 12, 2);
            $table->decimal('cost', 10, 2);
            $table->decimal('profit', 12, 2);
            $table->timestamp('sale_date');
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index(['sale_date']);
            $table->index(['region']);
            $table->index(['category']);
            $table->index(['customer_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};