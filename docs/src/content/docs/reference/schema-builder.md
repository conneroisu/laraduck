---
title: Schema Builder
description: Database schema management with LaraDuck
---

## Schema Builder Overview

LaraDuck extends Laravel's Schema Builder to support DuckDB-specific column types and constraints.

## Creating Tables

```php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::connection('duckdb')->create('analytics', function (Blueprint $table) {
    $table->id();
    $table->string('event_type');
    $table->json('payload');
    $table->timestamp('occurred_at');
    $table->decimal('value', 19, 4);
    $table->boolean('processed')->default(false);
});
```

## DuckDB Column Types

### Numeric Types

```php
Schema::create('numbers', function (Blueprint $table) {
    // Standard integers
    $table->tinyInteger('tiny');      // TINYINT
    $table->smallInteger('small');    // SMALLINT
    $table->integer('regular');       // INTEGER
    $table->bigInteger('big');        // BIGINT
    $table->hugeInteger('huge');      // HUGEINT (128-bit)

    // Unsigned variants
    $table->unsignedTinyInteger('utiny');
    $table->unsignedInteger('uint');

    // Decimals
    $table->decimal('price', 19, 4);  // DECIMAL(19,4)
    $table->double('measurement');    // DOUBLE
    $table->float('ratio');          // REAL
});
```

### String Types

```php
Schema::create('texts', function (Blueprint $table) {
    $table->string('name');          // VARCHAR
    $table->string('code', 10);      // VARCHAR(10)
    $table->text('description');     // TEXT
    $table->uuid('identifier');      // UUID
});
```

### Date/Time Types

```php
Schema::create('temporal', function (Blueprint $table) {
    $table->date('birth_date');      // DATE
    $table->time('start_time');      // TIME
    $table->timestamp('created_at'); // TIMESTAMP
    $table->timestampTz('updated_at'); // TIMESTAMPTZ
    $table->interval('duration');    // INTERVAL
});
```

### Array/List Types

```php
Schema::create('arrays', function (Blueprint $table) {
    $table->array('integer', 'scores');     // INTEGER[]
    $table->array('string', 'tags');        // VARCHAR[]
    $table->array('decimal', 'prices', [10, 2]); // DECIMAL(10,2)[]
});
```

### Struct Types

```php
Schema::create('structs', function (Blueprint $table) {
    $table->struct('address', [
        'street' => 'string',
        'city' => 'string',
        'zip' => 'string',
        'coords' => ['lat' => 'double', 'lng' => 'double']
    ]);
});
```

### Special Types

```php
Schema::create('special', function (Blueprint $table) {
    $table->json('metadata');        // JSON
    $table->map('string', 'string', 'settings'); // MAP(VARCHAR, VARCHAR)
    $table->enum('status', ['active', 'inactive']); // ENUM
    $table->bit('flags');           // BIT
    $table->blob('data');           // BLOB
});
```

## Indexes

```php
Schema::table('users', function (Blueprint $table) {
    // Standard index
    $table->index('email');

    // Composite index
    $table->index(['last_name', 'first_name']);

    // Unique index
    $table->unique('username');

    // ART index (Adaptive Radix Tree)
    $table->artIndex('email');
});
```

## Constraints

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->decimal('total', 10, 2);

    // Check constraint
    $table->check('total >= 0');

    // Composite unique
    $table->unique(['user_id', 'order_date']);
});
```

## Altering Tables

```php
// Add columns
Schema::table('users', function (Blueprint $table) {
    $table->array('string', 'preferences')->nullable();
});

// Modify columns
Schema::table('users', function (Blueprint $table) {
    $table->string('email', 255)->change();
});

// Drop columns
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn(['temporary', 'obsolete']);
});
```

## Views

```php
// Create view
Schema::createView('active_users', 'SELECT * FROM users WHERE active = true');

// Create materialized view
Schema::createMaterializedView('user_stats', '
    SELECT user_id,
           COUNT(*) as order_count,
           SUM(total) as total_spent
    FROM orders
    GROUP BY user_id
');

// Drop view
Schema::dropView('active_users');
```

## Sequences

```php
// Create sequence
Schema::createSequence('order_number_seq', [
    'start' => 1000,
    'increment' => 1,
]);

// Use in table
Schema::create('orders', function (Blueprint $table) {
    $table->bigInteger('order_number')
          ->default(DB::raw("nextval('order_number_seq')"));
});

// Drop sequence
Schema::dropSequence('order_number_seq');
```

## Temporary Tables

```php
// Create temporary table
Schema::createTemporary('temp_calculations', function (Blueprint $table) {
    $table->id();
    $table->decimal('result', 19, 4);
});

// Temporary table exists only for the session
```

## Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnalyticsTable extends Migration
{
    protected $connection = 'duckdb';

    public function up()
    {
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->json('properties');
            $table->timestamp('occurred_at')->index();
            $table->array('string', 'tags')->nullable();

            $table->index(['event_type', 'occurred_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('analytics');
    }
}
```
