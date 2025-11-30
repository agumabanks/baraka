<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverCashAccount extends Model
{
    protected $fillable = [
        'driver_id',
        'balance',
        'pending_remittance',
        'currency',
        'last_remittance_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'pending_remittance' => 'decimal:2',
        'last_remittance_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Get or create account for driver
     */
    public static function getOrCreate(int $driverId): self
    {
        return self::firstOrCreate(
            ['driver_id' => $driverId],
            ['balance' => 0, 'pending_remittance' => 0]
        );
    }

    /**
     * Add collection to balance
     */
    public function addCollection(float $amount): self
    {
        $this->increment('balance', $amount);
        $this->increment('pending_remittance', $amount);
        return $this->fresh();
    }

    /**
     * Record remittance
     */
    public function recordRemittance(float $amount): self
    {
        $this->decrement('balance', min($amount, $this->balance));
        $this->decrement('pending_remittance', min($amount, $this->pending_remittance));
        $this->update(['last_remittance_at' => now()]);
        return $this->fresh();
    }

    /**
     * Get total outstanding
     */
    public function getTotalOutstandingAttribute(): float
    {
        return $this->balance;
    }
}
