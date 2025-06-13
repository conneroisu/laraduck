<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;
use Laraduck\EloquentDuckDB\Eloquent\Traits\QueriesFiles;

class Product extends AnalyticalModel
{
    use HasFactory, QueriesFiles;

    protected $connection = 'analytics';

    protected $fillable = [
        'name',
        'category',
        'price',
        'cost',
        'description',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * Get profit margin percentage
     */
    public function getProfitMarginAttribute()
    {
        if ($this->price <= 0) {
            return 0;
        }

        return round((($this->price - $this->cost) / $this->price) * 100, 2);
    }

    /**
     * Scope to get only active products
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get products by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
