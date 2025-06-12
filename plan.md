# Complex Types Integration & Fix Plan

**Objective**: Complete and fix DuckDB complex types support in Laraduck to enable full utilization of DuckDB's advanced analytical type system within Laravel migrations and Eloquent models.

## Current State Analysis

### ✅ What's Already Implemented

**Blueprint Methods** (`src/Schema/Blueprint.php`):

- `list($column, $type)` - Array/list types (LINE 9-12)
- `struct($column, array $fields)` - Nested structures (LINE 14-17)
- `map($column, $keyType, $valueType)` - Key-value mappings (LINE 39-42)
- `union($column, array $types)` - Union types (LINE 44-47)
- `enum8/16/32($column, array $values)` - Sized enums (LINE 49-62)
- `decimal128($column, $scale)` - High precision decimals (LINE 19-22)
- `hugeInt($column)` - 128-bit integers (LINE 24-27)
- `interval($column)` - Time intervals (LINE 29-32)
- `blob($column)` - Binary data (LINE 34-37)
- Multiple unsigned integer types (`uInt8/16/32/64/128`)
- `bitString($column, $length)` - Bit strings
- `generated($column, $expression)` - Computed columns
- `sequence($column, $start, $increment)` - Sequences

**Grammar Support** (`src/Schema/Grammars/DuckDBGrammar.php`):

- Basic type mappings for `list`, `struct`, `map` (LINE 367-379)
- `decimal128`, `hugeInt`, `interval`, `blob` support (LINE 381-399)
- Spatial types (geometry, point, linestring, etc.) (LINE 327-365)

### ❌ Critical Issues Identified

#### 1. **Incomplete Grammar Implementation**

- **Missing type handlers**: `union`, `enum8/16/32`, `bitString`, `generated`, `sequence`
- **Incorrect list syntax**: Uses `{type}[]` instead of proper `LIST({type})` (LINE 369)
- **No validation**: Complex field definitions aren't validated
- **No constraint support**: Default values, nullability broken for complex types

#### 2. **Testing Gaps**

- **No complex type tests**: `SchemaBuilderTest.php` only tests basic conditional logic (LINE 38-63)
- **Grammar tests missing**: No validation of actual SQL generation for complex types
- **Integration tests missing**: No end-to-end testing with real DuckDB instances

#### 3. **Model Integration Missing**

- **No casting support**: Eloquent models can't properly cast complex types
- **No query support**: Can't query nested fields in complex types
- **No relationship mapping**: Complex types don't integrate with Eloquent relationships

#### 4. **Edge Cases Not Handled**

- **Nested complex types**: STRUCT containing LIST containing STRUCT
- **NULL handling**: Complex types with nullable components
- **Index support**: No indexing for complex type fields
- **Migration rollbacks**: No support for dropping/modifying complex columns

## Implementation Plan

### Phase 1: Fix Core Grammar Issues 🔧

#### 1.1 Complete Missing Type Handlers

**Priority**: CRITICAL
**Files**: `src/Schema/Grammars/DuckDBGrammar.php`
**Timeline**: 1-2 days

**Tasks**:

```php
// Add missing type handlers
protected function typeUnion(Fluent $column): string
{
    return "UNION(" . implode(', ', $column->types) . ")";
}

protected function typeEnum8(Fluent $column): string
{
    return sprintf("ENUM8(%s)",
        implode(', ', array_map(fn($v) => "'$v'", $column->allowed))
    );
}

protected function typeEnum16(Fluent $column): string
{
    return sprintf("ENUM16(%s)",
        implode(', ', array_map(fn($v) => "'$v'", $column->allowed))
    );
}

protected function typeEnum32(Fluent $column): string
{
    return sprintf("ENUM32(%s)",
        implode(', ', array_map(fn($v) => "'$v'", $column->allowed))
    );
}

protected function typeBitString(Fluent $column): string
{
    return $column->length ? "BIT({$column->length})" : "BIT";
}

protected function typeGenerated(Fluent $column): string
{
    return "AS ({$column->expression})";
}

protected function typeSequence(Fluent $column): string
{
    $start = $column->start ?? 1;
    $increment = $column->increment ?? 1;
    return "INTEGER DEFAULT nextval('seq_{$column->name}')";
}
```

#### 1.2 Fix List Type Syntax

**Priority**: CRITICAL
**Current Issue**: LINE 369 generates `{type}[]` but DuckDB expects `LIST({type})`

```php
// BEFORE (incorrect):
protected function typeList(Fluent $column)
{
    return "{$column->type}[]";  // WRONG SYNTAX
}

// AFTER (correct):
protected function typeList(Fluent $column): string
{
    return "LIST({$column->type})";
}
```

#### 1.3 Enhanced Struct Type Support

**Priority**: HIGH
**Current Issue**: No validation or nested type support

```php
// Enhanced struct implementation
protected function typeStruct(Fluent $column): string
{
    if (empty($column->fields)) {
        throw new InvalidArgumentException("Struct column '{$column->name}' must define fields");
    }

    $fields = collect($column->fields)->map(function ($type, $name) {
        // Support nested complex types
        if (is_array($type)) {
            $type = $this->resolveNestedType($type);
        }
        return "{$name} {$type}";
    })->implode(', ');

    return "STRUCT({$fields})";
}

private function resolveNestedType(array $typeDefinition): string
{
    // Handle nested LIST, STRUCT, MAP definitions
    if (isset($typeDefinition['type'])) {
        switch ($typeDefinition['type']) {
            case 'list':
                return "LIST({$typeDefinition['element_type']})";
            case 'map':
                return "MAP({$typeDefinition['key_type']}, {$typeDefinition['value_type']})";
            case 'struct':
                return $this->typeStruct((object) $typeDefinition);
        }
    }
    return $typeDefinition['type'] ?? 'VARCHAR';
}
```

### Phase 2: Enhanced Blueprint Methods 🛠️

#### 2.1 Nested Type Builder Support

**Priority**: HIGH
**Files**: `src/Schema/Blueprint.php`
**Timeline**: 2-3 days

**Add Fluent Builder Support**:

```php
// Enhanced struct builder with fluent interface
public function struct(string $column, $fields = null): ColumnDefinition
{
    if (is_callable($fields)) {
        $builder = new StructBuilder();
        $fields($builder);
        $fields = $builder->getFields();
    }

    return $this->addColumn('struct', $column, compact('fields'));
}

// Nested list builder
public function listOf(string $column): ListTypeBuilder
{
    return new ListTypeBuilder($this, $column);
}

// Nested map builder
public function mapOf(string $column): MapTypeBuilder
{
    return new MapTypeBuilder($this, $column);
}
```

**Usage Examples**:

```php
// Complex nested structures
Schema::create('analytics', function (Blueprint $table) {
    $table->id();

    // Simple list
    $table->list('tags', 'VARCHAR');

    // Nested struct with fluent builder
    $table->struct('customer', function ($struct) {
        $struct->field('id', 'INTEGER');
        $struct->field('name', 'VARCHAR');
        $struct->field('preferences', 'LIST(VARCHAR)');
        $struct->field('demographics', function ($nested) {
            $nested->field('age', 'INTEGER');
            $nested->field('income', 'DOUBLE');
            $nested->field('location', 'STRUCT(lat DOUBLE, lng DOUBLE)');
        });
    });

    // Fluent list builder
    $table->listOf('daily_metrics')->ofType('DOUBLE');
    $table->listOf('nested_data')->ofStruct(function ($struct) {
        $struct->field('timestamp', 'TIMESTAMP');
        $struct->field('value', 'DOUBLE');
    });

    // Fluent map builder
    $table->mapOf('settings')->withKeys('VARCHAR')->andValues('JSON');
    $table->mapOf('counters')->withKeys('VARCHAR')->andValues('INTEGER');
});
```

#### 2.2 Type Validation & Constraints

**Priority**: MEDIUM
**Add validation for complex type definitions**:

```php
// Add to Blueprint.php
private function validateComplexType(string $type, array $definition): void
{
    switch ($type) {
        case 'struct':
            if (empty($definition['fields'])) {
                throw new InvalidArgumentException("STRUCT must define at least one field");
            }
            break;
        case 'list':
            if (empty($definition['type'])) {
                throw new InvalidArgumentException("LIST must specify element type");
            }
            break;
        case 'map':
            if (empty($definition['keyType']) || empty($definition['valueType'])) {
                throw new InvalidArgumentException("MAP must specify both key and value types");
            }
            break;
    }
}
```

### Phase 3: Eloquent Integration 🏗️

#### 3.1 Complex Type Casting

**Priority**: HIGH
**Files**: `src/Eloquent/Casts/`, New files
**Timeline**: 3-4 days

**Create Custom Casts**:

```php
// src/Eloquent/Casts/StructCast.php
class StructCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        // DuckDB returns struct as JSON-like string
        return json_decode($value, true);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        // Convert array back to DuckDB struct format
        return $this->arrayToStruct($value);
    }

    private function arrayToStruct(array $data): string
    {
        $pairs = [];
        foreach ($data as $key => $value) {
            $pairs[] = "'{$key}': " . $this->formatValue($value);
        }
        return '{' . implode(', ', $pairs) . '}';
    }
}

// src/Eloquent/Casts/ListCast.php
class ListCast implements CastsAttributes
{
    protected string $elementType;

    public function __construct(string $elementType = 'string')
    {
        $this->elementType = $elementType;
    }

    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        // DuckDB returns list as [item1, item2, ...]
        $decoded = json_decode($value, true);

        // Cast elements to appropriate type
        return array_map(function ($item) {
            return $this->castElement($item);
        }, $decoded);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        return '[' . implode(', ', array_map([$this, 'formatElement'], $value)) . ']';
    }
}
```

#### 3.2 Query Builder Extensions

**Priority**: MEDIUM
**Files**: `src/Query/Builder.php`, `src/Eloquent/Builder.php`

**Add Complex Type Query Methods**:

```php
// src/Query/Builder.php additions
public function whereStruct(string $column, string $field, $operator, $value = null): self
{
    if (func_num_args() === 3) {
        $value = $operator;
        $operator = '=';
    }

    return $this->where("{$column}.{$field}", $operator, $value);
}

public function whereListContains(string $column, $value): self
{
    return $this->whereRaw("list_contains({$column}, ?)", [$value]);
}

public function whereMapHasKey(string $column, string $key): self
{
    return $this->whereRaw("map_has_key({$column}, ?)", [$key]);
}

public function selectStruct(string $column, array $fields): self
{
    $selections = array_map(fn($field) => "{$column}.{$field}", $fields);
    return $this->select($selections);
}
```

**Usage Examples**:

```php
// Query nested struct fields
$customers = Customer::whereStruct('demographics', 'age', '>', 25)
    ->whereStruct('demographics', 'income', '>', 50000)
    ->get();

// Query list contents
$products = Product::whereListContains('tags', 'electronics')
    ->whereListContains('categories', 'mobile')
    ->get();

// Query map keys/values
$users = User::whereMapHasKey('settings', 'notifications')
    ->whereRaw("settings['theme'] = ?", ['dark'])
    ->get();

// Select specific struct fields
$analytics = Analytics::selectStruct('customer', ['id', 'segment'])
    ->selectStruct('metrics', ['revenue', 'conversion_rate'])
    ->get();
```

### Phase 4: Comprehensive Testing 🧪

#### 4.1 Grammar Tests

**Priority**: CRITICAL
**Files**: `tests/Unit/DuckDBGrammarTest.php`
**Timeline**: 2 days

**Add Complex Type Grammar Tests**:

```php
// Add to DuckDBGrammarTest.php
public function test_compile_struct_type()
{
    $column = new Fluent([
        'name' => 'customer',
        'type' => 'struct',
        'fields' => [
            'id' => 'INTEGER',
            'name' => 'VARCHAR',
            'settings' => 'JSON'
        ]
    ]);

    $sql = $this->grammar->typeStruct($column);

    $this->assertEquals('STRUCT(id INTEGER, name VARCHAR, settings JSON)', $sql);
}

public function test_compile_nested_complex_types()
{
    $column = new Fluent([
        'name' => 'analytics',
        'type' => 'struct',
        'fields' => [
            'customer_id' => 'INTEGER',
            'tags' => 'LIST(VARCHAR)',
            'metadata' => 'MAP(VARCHAR, JSON)',
            'timeline' => 'LIST(STRUCT(timestamp TIMESTAMP, event VARCHAR))'
        ]
    ]);

    $sql = $this->grammar->typeStruct($column);

    $expected = 'STRUCT(customer_id INTEGER, tags LIST(VARCHAR), metadata MAP(VARCHAR, JSON), timeline LIST(STRUCT(timestamp TIMESTAMP, event VARCHAR)))';
    $this->assertEquals($expected, $sql);
}

public function test_compile_list_types()
{
    $integerList = new Fluent(['name' => 'scores', 'type' => 'INTEGER']);
    $this->assertEquals('LIST(INTEGER)', $this->grammar->typeList($integerList));

    $structList = new Fluent(['name' => 'items', 'type' => 'STRUCT(id INTEGER, name VARCHAR)']);
    $this->assertEquals('LIST(STRUCT(id INTEGER, name VARCHAR))', $this->grammar->typeList($structList));
}

public function test_compile_map_types()
{
    $column = new Fluent([
        'name' => 'settings',
        'keyType' => 'VARCHAR',
        'valueType' => 'JSON'
    ]);

    $sql = $this->grammar->typeMap($column);

    $this->assertEquals('MAP(VARCHAR, JSON)', $sql);
}

public function test_compile_union_types()
{
    $column = new Fluent([
        'name' => 'flexible_value',
        'types' => ['INTEGER', 'VARCHAR', 'BOOLEAN']
    ]);

    $sql = $this->grammar->typeUnion($column);

    $this->assertEquals('UNION(INTEGER, VARCHAR, BOOLEAN)', $sql);
}

public function test_compile_enum_types()
{
    $enum8 = new Fluent(['name' => 'status', 'allowed' => ['active', 'inactive']]);
    $this->assertEquals("ENUM8('active', 'inactive')", $this->grammar->typeEnum8($enum8));

    $enum16 = new Fluent(['name' => 'priority', 'allowed' => ['low', 'medium', 'high', 'critical']]);
    $this->assertEquals("ENUM16('low', 'medium', 'high', 'critical')", $this->grammar->typeEnum16($enum16));
}
```

#### 4.2 Integration Tests

**Priority**: HIGH
**Files**: `tests/Feature/ComplexTypesIntegrationTest.php` (NEW)

**Create Comprehensive Integration Tests**:

```php
// tests/Feature/ComplexTypesIntegrationTest.php
class ComplexTypesIntegrationTest extends TestCase
{
    public function test_create_table_with_complex_types()
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();

            // Test all complex types
            $table->list('tags', 'VARCHAR');
            $table->struct('customer', [
                'id' => 'INTEGER',
                'name' => 'VARCHAR',
                'tier' => 'VARCHAR'
            ]);
            $table->map('metadata', 'VARCHAR', 'JSON');
            $table->union('flexible_value', ['INTEGER', 'VARCHAR']);
            $table->enum8('status', ['active', 'inactive', 'pending']);
            $table->hugeInt('big_number');
            $table->interval('duration');

            $table->timestamps();
        });

        $this->assertTrue(Schema::hasTable('analytics_events'));

        // Verify all columns exist
        $columns = ['id', 'tags', 'customer', 'metadata', 'flexible_value', 'status', 'big_number', 'duration'];
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('analytics_events', $column));
        }
    }

    public function test_insert_and_query_complex_data()
    {
        // Create test table
        Schema::create('test_complex', function (Blueprint $table) {
            $table->id();
            $table->list('tags', 'VARCHAR');
            $table->struct('customer', ['id' => 'INTEGER', 'name' => 'VARCHAR']);
            $table->map('settings', 'VARCHAR', 'VARCHAR');
        });

        // Insert complex data
        DB::table('test_complex')->insert([
            'id' => 1,
            'tags' => "['electronics', 'mobile', 'smartphone']",
            'customer' => "{'id': 123, 'name': 'John Doe'}",
            'settings' => "{'theme': 'dark', 'notifications': 'enabled'}"
        ]);

        // Query the data
        $result = DB::table('test_complex')->where('id', 1)->first();

        $this->assertNotNull($result);
        $this->assertStringContainsString('electronics', $result->tags);
        $this->assertStringContainsString('John Doe', $result->customer);
        $this->assertStringContainsString('dark', $result->settings);
    }

    public function test_nested_struct_creation()
    {
        Schema::create('nested_test', function (Blueprint $table) {
            $table->id();
            $table->struct('deep_struct', [
                'level1' => 'VARCHAR',
                'level2' => 'STRUCT(field1 VARCHAR, field2 INTEGER)',
                'level3' => 'LIST(STRUCT(id INTEGER, data JSON))'
            ]);
        });

        $this->assertTrue(Schema::hasTable('nested_test'));
        $this->assertTrue(Schema::hasColumn('nested_test', 'deep_struct'));
    }
}
```

#### 4.3 Model Integration Tests

**Priority**: HIGH
**Files**: `tests/Feature/EloquentComplexTypesTest.php` (NEW)

```php
// tests/Feature/EloquentComplexTypesTest.php
class EloquentComplexTypesTest extends TestCase
{
    public function test_model_with_complex_type_casts()
    {
        // Create model with complex casts
        $model = new class extends Model {
            protected $table = 'analytics_events';
            protected $casts = [
                'tags' => ListCast::class . ':string',
                'customer' => StructCast::class,
                'metadata' => MapCast::class
            ];
        };

        // Test casting functionality
        $data = [
            'tags' => ['electronics', 'mobile'],
            'customer' => ['id' => 123, 'name' => 'John'],
            'metadata' => ['theme' => 'dark', 'locale' => 'en']
        ];

        $instance = new $model($data);

        $this->assertIsArray($instance->tags);
        $this->assertIsArray($instance->customer);
        $this->assertIsArray($instance->metadata);
    }

    public function test_complex_type_queries()
    {
        // Create and populate test data
        // ... setup code ...

        $model = new class extends Model {
            protected $table = 'analytics_events';
            use QueriesComplexTypes; // New trait
        };

        // Test complex queries
        $results = $model::whereStruct('customer', 'tier', 'premium')
            ->whereListContains('tags', 'electronics')
            ->get();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
    }
}
```

### Phase 5: Documentation & Examples 📚

#### 5.1 Update Documentation

**Priority**: MEDIUM
**Files**: `docs/complex-types.md` (NEW), Update existing docs
**Timeline**: 2 days

**Create Comprehensive Documentation**:

````markdown
# Complex Types Guide

## Overview

DuckDB supports advanced analytical types that are perfect for complex data analysis. Laraduck provides full Laravel integration for these types.

## Available Complex Types

### STRUCT - Nested Objects

```php
// Migration
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->struct('profile', [
        'name' => 'VARCHAR',
        'age' => 'INTEGER',
        'preferences' => 'LIST(VARCHAR)'
    ]);
});

// Model with casting
class Customer extends Model
{
    protected $casts = [
        'profile' => StructCast::class
    ];
}

// Usage
$customer = Customer::create([
    'profile' => [
        'name' => 'John Doe',
        'age' => 30,
        'preferences' => ['email', 'sms']
    ]
]);

// Querying
$customers = Customer::whereStruct('profile', 'age', '>', 25)->get();
```
````

### LIST - Arrays

```php
// Migration
$table->list('tags', 'VARCHAR');
$table->listOf('scores')->ofType('DOUBLE');

// Model
protected $casts = [
    'tags' => ListCast::class . ':string',
    'scores' => ListCast::class . ':float'
];

// Querying
$products = Product::whereListContains('tags', 'electronics')->get();
```

### MAP - Key-Value Pairs

```php
// Migration
$table->map('settings', 'VARCHAR', 'JSON');
$table->mapOf('counters')->withKeys('VARCHAR')->andValues('INTEGER');

// Querying
$users = User::whereMapHasKey('settings', 'theme')->get();
```

````

#### 5.2 Create Working Examples
**Priority**: MEDIUM
**Files**: `examples/complex-types-demo/` (NEW)

**Practical Examples**:
```php
// examples/complex-types-demo/migrations/create_analytics_tables.php
class CreateAnalyticsTables extends Migration
{
    public function up()
    {
        // E-commerce analytics with complex types
        Schema::create('order_analytics', function (Blueprint $table) {
            $table->id();
            $table->struct('customer', [
                'id' => 'INTEGER',
                'tier' => 'VARCHAR',
                'demographics' => 'STRUCT(age INTEGER, income DOUBLE, location VARCHAR)'
            ]);
            $table->list('product_categories', 'VARCHAR');
            $table->map('promotion_metrics', 'VARCHAR', 'DOUBLE');
            $table->list('daily_sales', 'DOUBLE');
            $table->timestamps();
        });

        // Time series data with nested metrics
        Schema::create('performance_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('recorded_at');
            $table->struct('system_metrics', [
                'cpu' => 'DOUBLE',
                'memory' => 'DOUBLE',
                'disk' => 'STRUCT(read_iops DOUBLE, write_iops DOUBLE, usage_percent DOUBLE)'
            ]);
            $table->list('error_codes', 'INTEGER');
            $table->map('custom_metrics', 'VARCHAR', 'JSON');
        });
    }
}

// examples/complex-types-demo/models/OrderAnalytics.php
class OrderAnalytics extends AnalyticalModel
{
    use QueriesFiles, QueriesComplexTypes;

    protected $casts = [
        'customer' => StructCast::class,
        'product_categories' => ListCast::class . ':string',
        'promotion_metrics' => MapCast::class,
        'daily_sales' => ListCast::class . ':float'
    ];

    // Analytical queries using complex types
    public function premiumCustomersByCategory(string $category): Collection
    {
        return $this->whereStruct('customer', 'tier', 'premium')
            ->whereListContains('product_categories', $category)
            ->get();
    }

    public function customersByDemographics(int $minAge, float $minIncome): Collection
    {
        return $this->whereStruct('customer.demographics', 'age', '>=', $minAge)
            ->whereStruct('customer.demographics', 'income', '>=', $minIncome)
            ->get();
    }
}
````

### Phase 6: Performance Optimization ⚡

#### 6.1 Query Optimization

**Priority**: LOW
**Timeline**: 1-2 days

**Optimize Complex Type Queries**:

```php
// src/Query/Optimizations/ComplexTypeOptimizer.php
class ComplexTypeOptimizer
{
    public function optimizeStructQuery(Builder $query): Builder
    {
        // Use DuckDB's struct field access optimization
        // Convert multiple whereStruct calls to single complex WHERE
        return $query;
    }

    public function optimizeListQuery(Builder $query): Builder
    {
        // Use DuckDB's list_contains with multiple values
        // Convert multiple whereListContains to list_has_any
        return $query;
    }
}
```

#### 6.2 Index Support

**Priority**: LOW
**Files**: `src/Schema/Grammars/DuckDBGrammar.php`

**Add Index Support for Complex Types**:

```php
// Enhanced index compilation for complex types
public function compileIndex(Blueprint $blueprint, Fluent $command)
{
    $columns = [];
    foreach ($command->columns as $column) {
        if (str_contains($column, '.')) {
            // Handle struct field indexing: customer.id, customer.demographics.age
            $columns[] = $this->wrapStructField($column);
        } else {
            $columns[] = $this->wrap($column);
        }
    }

    return sprintf('create index %s on %s (%s)',
        $this->wrap($command->index),
        $this->wrapTable($blueprint),
        implode(', ', $columns)
    );
}

private function wrapStructField(string $field): string
{
    $parts = explode('.', $field);
    $struct = array_shift($parts);
    $path = implode('.', $parts);

    return "\"{$struct}\".\"{$path}\"";
}
```

## Timeline & Priorities

### Week 1: Foundation (CRITICAL)

- [ ] **Day 1-2**: Fix grammar issues (Phase 1.1-1.3)
- [ ] **Day 3-4**: Enhanced Blueprint methods (Phase 2.1-2.2)
- [ ] **Day 5**: Grammar tests (Phase 4.1)

### Week 2: Integration (HIGH)

- [ ] **Day 1-3**: Eloquent integration (Phase 3.1-3.2)
- [ ] **Day 4-5**: Integration tests (Phase 4.2-4.3)

### Week 3: Polish (MEDIUM)

- [ ] **Day 1-2**: Documentation (Phase 5.1)
- [ ] **Day 3-4**: Examples (Phase 5.2)
- [ ] **Day 5**: Performance optimization (Phase 6.1-6.2)

## Success Criteria

### ✅ Must Have (Critical Success)

1. **All complex types work in migrations**: LIST, STRUCT, MAP, UNION, ENUM variants
2. **Proper SQL generation**: Grammar produces valid DuckDB SQL
3. **Basic Eloquent casting**: Models can cast complex types to/from PHP arrays
4. **Comprehensive tests**: 90%+ test coverage for complex types

### 🎯 Should Have (High Value)

1. **Fluent builders**: Intuitive API for defining nested complex types
2. **Query support**: Can query nested fields in complex types
3. **Integration tests**: End-to-end testing with real DuckDB
4. **Documentation**: Complete guide with examples

### 🌟 Could Have (Nice to Have)

1. **Performance optimization**: Optimized queries for complex types
2. **Index support**: Indexing on complex type fields
3. **Advanced casting**: Custom cast classes for specific use cases
4. **Real-world examples**: Production-ready demo applications

## Risk Mitigation

### Technical Risks

1. **DuckDB compatibility**: Test with multiple DuckDB versions
2. **Performance impact**: Benchmark complex type operations
3. **Memory usage**: Monitor memory with large complex structures

### Development Risks

1. **Scope creep**: Focus on core functionality first
2. **Testing complexity**: Use incremental testing approach
3. **Documentation debt**: Write docs alongside code

## Files to Create/Modify

### New Files

```
src/Eloquent/Casts/StructCast.php
src/Eloquent/Casts/ListCast.php
src/Eloquent/Casts/MapCast.php
src/Eloquent/Traits/QueriesComplexTypes.php
src/Schema/Builders/StructBuilder.php
src/Schema/Builders/ListTypeBuilder.php
src/Schema/Builders/MapTypeBuilder.php
tests/Feature/ComplexTypesIntegrationTest.php
tests/Feature/EloquentComplexTypesTest.php
tests/Unit/ComplexTypeCastsTest.php
docs/complex-types.md
examples/complex-types-demo/
```

### Modified Files

```
src/Schema/Grammars/DuckDBGrammar.php (major updates)
src/Schema/Blueprint.php (enhancements)
tests/Unit/DuckDBGrammarTest.php (new tests)
tests/Feature/SchemaBuilderTest.php (complex type tests)
src/Query/Builder.php (query methods)
src/Eloquent/Builder.php (eloquent methods)
```

This plan provides a comprehensive roadmap for implementing complete DuckDB complex types support in Laraduck, with clear priorities, timelines, and success criteria.
