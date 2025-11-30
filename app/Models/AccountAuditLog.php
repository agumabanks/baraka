<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountAuditLog extends Model
{
    protected $table = 'account_audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'changes',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
