<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::connection('analytics')->statement('
            CREATE TABLE customers (
                id BIGINT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                phone VARCHAR(50),
                region VARCHAR(100) NOT NULL,
                acquisition_date TIMESTAMP NOT NULL,
                customer_type VARCHAR(50) DEFAULT \'regular\',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    public function down()
    {
        DB::connection('analytics')->statement('DROP TABLE IF EXISTS customers');
    }
};