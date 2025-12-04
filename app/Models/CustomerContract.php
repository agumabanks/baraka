<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerContract extends Model
{
    protected $fillable = [
        'customer_id',
        'contract_number',
        'name',
        'start_date',
        'end_date',
        'credit_limit',
        'payment_terms_days',
        'discount_percent',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'credit_limit' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerContractItem::class, 'contract_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }

    public static function findActiveForCustomer(int $customerId): ?self
    {
        return static::active()
            ->where('customer_id', $customerId)
            ->first();
    }
}
