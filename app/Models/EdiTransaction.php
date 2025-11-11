<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdiTransaction extends Model
{
    protected $fillable = [
        'edi_type',
        'sender_code',
        'receiver_code',
        'reference',
        'raw_document',
        'processed_data',
        'status',
        'error_message',
    ];

    protected $casts = [
        'processed_data' => 'array',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
