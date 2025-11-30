<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginSession extends Model
{
    protected $table = 'login_sessions';

    protected $fillable = [
        'user_id',
        'session_id',
        'device_name',
        'device_type',
        'ip_address',
        'user_agent',
        'location',
        'logged_in_at',
        'last_activity_at',
        'logged_out_at',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCurrent(): bool
    {
        return $this->session_id === session()->getId();
    }
}
