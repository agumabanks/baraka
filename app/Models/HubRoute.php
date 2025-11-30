<?php

namespace App\Models;

use App\Models\Backend\Hub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HubRoute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'origin_hub_id',
        'destination_hub_id',
        'distance_km',
        'transit_time_hours',
        'base_cost',
        'cost_per_kg',
        'cost_per_cbm',
        'service_level',
        'transport_mode',
        'departure_days',
        'departure_time',
        'cutoff_time',
        'max_weight_kg',
        'max_volume_cbm',
        'max_shipments',
        'is_active',
        'priority',
        'congestion_factor',
        'congestion_updated_at',
        'metadata',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'base_cost' => 'decimal:2',
        'cost_per_kg' => 'decimal:2',
        'cost_per_cbm' => 'decimal:2',
        'max_volume_cbm' => 'decimal:2',
        'congestion_factor' => 'decimal:2',
        'departure_days' => 'array',
        'is_active' => 'boolean',
        'congestion_updated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_hub_id');
    }

    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'destination_hub_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByServiceLevel($query, string $level)
    {
        return $query->where('service_level', $level);
    }

    public function scopeByTransportMode($query, string $mode)
    {
        return $query->where('transport_mode', $mode);
    }

    /**
     * Calculate total cost for a shipment
     */
    public function calculateCost(float $weightKg, float $volumeCbm): float
    {
        $weightCost = $weightKg * $this->cost_per_kg;
        $volumeCost = $volumeCbm * $this->cost_per_cbm;
        
        // Use higher of weight or volume cost (dimensional pricing)
        return $this->base_cost + max($weightCost, $volumeCost);
    }

    /**
     * Calculate adjusted transit time with congestion
     */
    public function getAdjustedTransitTime(): int
    {
        return (int) ceil($this->transit_time_hours * $this->congestion_factor);
    }

    /**
     * Check if route operates on a given day
     */
    public function operatesOnDay(int $dayOfWeek): bool
    {
        if (empty($this->departure_days)) {
            return true; // Operates every day
        }
        
        return in_array($dayOfWeek, $this->departure_days);
    }

    /**
     * Get next available departure
     */
    public function getNextDeparture(): ?\Carbon\Carbon
    {
        $now = now();
        $currentDay = $now->dayOfWeekIso;
        
        // If route has no schedule, available immediately
        if (empty($this->departure_days)) {
            return $now;
        }
        
        // Check next 7 days for departure
        for ($i = 0; $i < 7; $i++) {
            $checkDay = (($currentDay + $i - 1) % 7) + 1;
            
            if (in_array($checkDay, $this->departure_days)) {
                $departureDate = $now->copy()->addDays($i);
                
                if ($this->departure_time) {
                    $departureDate->setTimeFromTimeString($this->departure_time);
                }
                
                // If same day, check if we're past cutoff
                if ($i === 0 && $this->cutoff_time) {
                    $cutoff = $now->copy()->setTimeFromTimeString($this->cutoff_time);
                    if ($now->gt($cutoff)) {
                        continue; // Missed cutoff, try next day
                    }
                }
                
                return $departureDate;
            }
        }
        
        return null;
    }
}
