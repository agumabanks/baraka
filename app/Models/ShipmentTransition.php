<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'from_status',
        'to_status',
        'trigger',
        'source_type',
        'source_id',
        'performed_by',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
