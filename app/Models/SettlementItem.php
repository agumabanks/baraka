<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    protected $fillable = [
        'settlement_id',
        'shipment_id',
        'shipping_fee',
        'cod_amount',
        'insurance_fee',
        'other_charges',
        'deductions',
        'net_amount',
        'deduction_reason',
    ];

    protected $casts = [
        'shipping_fee' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'insurance_fee' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(MerchantSettlement::class, 'settlement_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Calculate net amount
     */
    public function calculateNet(): float
    {
        return $this->cod_amount - $this->shipping_fee - $this->insurance_fee - $this->other_charges - $this->deductions;
    }
}
