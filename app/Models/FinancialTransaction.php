<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'type',
        'transactable_type',
        'transactable_id',
        'branch_id',
        'user_id',
        'amount',
        'currency',
        'direction',
        'payment_method',
        'payment_reference',
        'balance_before',
        'balance_after',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (!$transaction->transaction_id) {
                $transaction->transaction_id = self::generateTransactionId();
            }
        });
    }

    public static function generateTransactionId(): string
    {
        return 'TXN-' . strtoupper(Str::random(12));
    }

    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCredits($query)
    {
        return $query->where('direction', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('direction', 'debit');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Create COD collection transaction
     */
    public static function recordCodCollection(
        Shipment $shipment,
        float $amount,
        ?int $userId = null,
        ?int $branchId = null,
        ?string $method = null,
        ?string $reference = null
    ): self {
        return self::create([
            'type' => 'cod_collection',
            'transactable_type' => Shipment::class,
            'transactable_id' => $shipment->id,
            'branch_id' => $branchId,
            'user_id' => $userId,
            'amount' => $amount,
            'direction' => 'credit',
            'payment_method' => $method,
            'payment_reference' => $reference,
            'description' => "COD collection for shipment {$shipment->tracking_number}",
        ]);
    }

    /**
     * Create settlement payment transaction
     */
    public static function recordSettlementPayment(
        MerchantSettlement $settlement,
        ?int $userId = null,
        ?string $method = null,
        ?string $reference = null
    ): self {
        return self::create([
            'type' => 'settlement_payment',
            'transactable_type' => MerchantSettlement::class,
            'transactable_id' => $settlement->id,
            'branch_id' => $settlement->branch_id,
            'user_id' => $userId,
            'amount' => $settlement->net_payable,
            'currency' => $settlement->currency,
            'direction' => 'debit',
            'payment_method' => $method,
            'payment_reference' => $reference,
            'description' => "Settlement payment {$settlement->settlement_number}",
        ]);
    }
}
