<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BranchScoped;

class Payment extends Model
{
    use HasFactory, BranchScoped;

    protected $fillable = [
        'shipment_id',
        'shipment_id',
        'client_id',
        'branch_id',
        'amount',
        'payment_method',
        'status',
        'transaction_id',
        'transaction_reference',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        // Link via shipment_id since payments table has no invoice_id column.
        return $this->belongsTo(Invoice::class, 'shipment_id', 'shipment_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $payment): void {
            if (! $payment->transaction_id && $payment->transaction_reference) {
                $payment->transaction_id = $payment->transaction_reference;
            }

            if (! $payment->transaction_reference && $payment->transaction_id) {
                $payment->transaction_reference = $payment->transaction_id;
            }
        });
    }
}
