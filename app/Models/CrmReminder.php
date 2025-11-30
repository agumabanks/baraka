<?php

namespace App\Models;

use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'created_by',
        'branch_id',
        'title',
        'description',
        'reminder_at',
        'priority',
        'status',
        'completed_at',
        'completion_notes',
    ];

    protected $casts = [
        'reminder_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Mark reminder as completed
     */
    public function markCompleted(?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_notes' => $notes,
        ]);
    }

    /**
     * Scope pending reminders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope overdue reminders
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('reminder_at', '<', now());
    }

    /**
     * Scope upcoming reminders
     */
    public function scopeUpcoming($query, int $hours = 24)
    {
        return $query->where('status', 'pending')
            ->where('reminder_at', '>=', now())
            ->where('reminder_at', '<=', now()->addHours($hours));
    }

    /**
     * Scope by priority
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Scope to filter by branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get reminders visible to current user based on role
     */
    public function scopeVisibleToUser($query, $user = null)
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(['super-admin', 'super_admin', 'regional-manager', 'regional_manager', 'admin'])) {
            return $query;
        }

        $branchId = $user->primary_branch_id ?? $user->branchWorker?->branch_id ?? null;

        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }

        return $query->whereRaw('1 = 0');
    }
}
