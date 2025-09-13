<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'address', 'channel', 'code', 'expires_at', 'consumed_at',
        'attempts', 'locked_until', 'sent_count', 'last_sent_at', 'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_sent_at' => 'datetime',
        'meta' => 'array',
    ];
}

