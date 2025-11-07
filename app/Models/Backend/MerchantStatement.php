<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Backend\Parcel;
use App\Models\Backend\Merchant;
use App\Models\Backend\DeliveryMan;

class MerchantStatement extends Model
{
    use HasFactory;

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class, 'parcel_id', 'id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id', 'id');
    }
}
