<?php

namespace App\Models;

use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'alert_type',
        'severity',
        'status',
        'title',
        'message',
        'context',
        'triggered_at',
        'resolved_at',
    ];

    protected $casts = [
        'context' => 'array',
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function markResolved(?string $note = null): void
    {
        $payload = [
            'status' => 'RESOLVED',
            'resolved_at' => now(),
        ];

        if ($note) {
            $context = $this->context ?? [];
            $context['resolution_note'] = $note;
            $payload['context'] = $context;
        }

        $this->fill($payload)->save();
    }
}
