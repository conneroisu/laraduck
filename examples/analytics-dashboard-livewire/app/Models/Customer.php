<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laraduck\EloquentDuckDB\Eloquent\AnalyticalModel;

class Customer extends AnalyticalModel
{
    use HasFactory;

    protected $connection = 'analytics';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'region',
        'acquisition_date',
        'customer_type',
    ];

    protected $casts = [
        'acquisition_date' => 'datetime',
    ];

    /**
     * Scope to get customers by region
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope to get customers by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('customer_type', $type);
    }
}
