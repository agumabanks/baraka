<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AccountingEntry extends Model
{
    protected $fillable = [
        'uuid',
        'payment_transaction_id',
        'shipment_id',
        'account_code',
        'account_name',
        'entry_type',
        'amount',
        'currency',
        'reference',
        'description',
        'posting_date',
        'status',
        'external_sync_id',
        'synced_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'posting_date' => 'date',
        'synced_at' => 'datetime',
    ];

    public const TYPE_DEBIT = 'debit';
    public const TYPE_CREDIT = 'credit';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_REVERSED = 'reversed';

    // Standard GL Account Codes
    public const ACCOUNT_CASH = '1100';
    public const ACCOUNT_RECEIVABLES = '1200';
    public const ACCOUNT_REVENUE_FREIGHT = '4100';
    public const ACCOUNT_REVENUE_SURCHARGES = '4200';
    public const ACCOUNT_REVENUE_INSURANCE = '4300';
    public const ACCOUNT_TAX_PAYABLE = '2100';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function markPosted(): bool
    {
        return $this->update(['status' => self::STATUS_POSTED]);
    }
}
