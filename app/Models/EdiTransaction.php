<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdiTransaction extends Model
{
    protected $fillable = [
        'provider_id',
        'document_type',
        'direction',
        'document_number',
        'status',
        'external_reference',
        'correlation_id',
        'payload',
        'normalized_payload',
        'ack_payload',
        'acknowledged_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'normalized_payload' => 'array',
        'ack_payload' => 'array',
        'acknowledged_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(EdiProvider::class, 'provider_id');
    }
}
