<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContractItem extends Model
{
    protected $fillable = [
        'contract_id',
        'service_level',
        'zone',
        'weight_from',
        'weight_to',
        'base_rate',
        'per_kg_rate',
        'discount_percent',
        'active',
    ];

    protected $casts = [
        'weight_from' => 'decimal:3',
        'weight_to' => 'decimal:3',
        'base_rate' => 'decimal:2',
        'per_kg_rate' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(CustomerContract::class, 'contract_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForService($query, string $serviceLevel)
    {
        return $query->where('service_level', $serviceLevel);
    }

    public function scopeForWeight($query, float $weight)
    {
        return $query
            ->where('weight_from', '<=', $weight)
            ->where(fn($q) => $q->whereNull('weight_to')->orWhere('weight_to', '>=', $weight));
    }
}
