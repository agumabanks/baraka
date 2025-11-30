<?php

namespace App\Models;

use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'branch_id',
        'user_id',
        'branch_id',
        'activity_type',
        'subject',
        'description',
        'occurred_at',
        'duration_minutes',
        'outcome',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
        'duration_minutes' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope recent activities
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope by outcome
     */
    public function scopeWithOutcome($query, string $outcome)
    {
        return $query->where('outcome', $outcome);
    }

    /**
     * Scope to filter by branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get activities visible to current user based on role
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
