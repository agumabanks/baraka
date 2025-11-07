<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'client_id',
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
