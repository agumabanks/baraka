<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantSettlement extends Model
{
    protected $fillable = [
        'settlement_number',
        'merchant_id',
        'branch_id',
        'period_start',
        'period_end',
        'shipment_count',
        'total_shipping_fees',
        'total_cod_collected',
        'total_deductions',
        'net_payable',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'approved_at',
        'approved_by',
        'paid_at',
        'breakdown',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_shipping_fees' => 'decimal:2',
        'total_cod_collected' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'breakdown' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($settlement) {
            if (!$settlement->settlement_number) {
                $settlement->settlement_number = self::generateSettlementNumber();
            }
        });
    }

    public static function generateSettlementNumber(): string
    {
        $prefix = 'STL';
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', today())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'merchant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class, 'settlement_id');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Submit for approval
     */
    public function submitForApproval(): self
    {
        $this->update(['status' => 'pending_approval']);
        return $this;
    }

    /**
     * Approve settlement
     */
    public function approve(int $approverId): self
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approverId,
        ]);

        return $this;
    }

    /**
     * Mark as paid
     */
    public function markPaid(string $method, string $reference): self
    {
        $this->update([
            'status' => 'paid',
            'payment_method' => $method,
            'payment_reference' => $reference,
            'paid_at' => now(),
        ]);

        return $this;
    }

    /**
     * Recalculate totals from items
     */
    public function recalculateTotals(): self
    {
        $items = $this->items;

        $this->update([
            'shipment_count' => $items->count(),
            'total_shipping_fees' => $items->sum('shipping_fee'),
            'total_cod_collected' => $items->sum('cod_amount'),
            'total_deductions' => $items->sum('deductions'),
            'net_payable' => $items->sum('net_amount'),
        ]);

        return $this;
    }
}
