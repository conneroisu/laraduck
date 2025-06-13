<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::connection('analytics')->statement('
            CREATE TABLE sales (
                id BIGINT PRIMARY KEY,
                product_id BIGINT NOT NULL,
                customer_id BIGINT NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                discount_amount DECIMAL(10,2) DEFAULT 0,
                sale_date TIMESTAMP NOT NULL,
                region VARCHAR(100) NOT NULL,
                channel VARCHAR(50) DEFAULT \'online\',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    public function down()
    {
        DB::connection('analytics')->statement('DROP TABLE IF EXISTS sales');
    }
};
