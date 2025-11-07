<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactShipment extends Model
{
    protected $table = 'fact_shipments';
    public $timestamps = false;
    protected $primaryKey = 'shipment_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'shipment_key',
        'client_key',
        'route_key',
        'driver_key',
        'origin_branch_key',
        'destination_branch_key',
        'carrier_key',
        'pickup_date_key',
        'delivery_date_key',
        'shipment_status',
        'actual_delivery_time',
        'scheduled_delivery_time',
        'distance_miles',
        'weight_lbs',
        'dimensions_cubic_feet',
        'shipping_cost',
        'fuel_cost',
        'labor_cost',
        'total_cost',
        'revenue',
        'on_time_indicator',
        'late_penalty_cost',
        'exception_flag',
        'exception_type',
        'container_id',
        'stops_count',
        'route_efficiency_score',
        'transit_time_hours',
        'billable_weight'
    ];

    protected $casts = [
        'actual_delivery_time' => 'datetime',
        'scheduled_delivery_time' => 'datetime',
        'pickup_date_key' => 'integer',
        'delivery_date_key' => 'integer',
        'distance_miles' => 'decimal:2',
        'weight_lbs' => 'decimal:2',
        'dimensions_cubic_feet' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'fuel_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'revenue' => 'decimal:2',
        'late_penalty_cost' => 'decimal:2',
        'route_efficiency_score' => 'decimal:4',
        'transit_time_hours' => 'decimal:2',
        'billable_weight' => 'decimal:2',
        'on_time_indicator' => 'boolean',
        'exception_flag' => 'boolean',
        'stops_count' => 'integer'
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(DimensionClient::class, 'client_key', 'client_key');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DimensionRoute::class, 'route_key', 'route_key');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DimensionDriver::class, 'driver_key', 'driver_key');
    }

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(DimensionBranch::class, 'origin_branch_key', 'branch_key');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(DimensionBranch::class, 'destination_branch_key', 'branch_key');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(DimensionCarrier::class, 'carrier_key', 'carrier_key');
    }

    public function pickupDate(): BelongsTo
    {
        return $this->belongsTo(DimensionDate::class, 'pickup_date_key', 'date_key');
    }

    public function deliveryDate(): BelongsTo
    {
        return $this->belongsTo(DimensionDate::class, 'delivery_date_key', 'date_key');
    }

    // Scopes for reporting
    public function scopeOnTime($query)
    {
        return $query->where('on_time_indicator', true);
    }

    public function scopeDelayed($query)
    {
        return $query->where('on_time_indicator', false);
    }

    public function scopeWithExceptions($query)
    {
        return $query->where('exception_flag', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('shipment_status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('delivery_date_key', [$startDate, $endDate]);
    }

    public function scopeByClient($query, $clientKey)
    {
        return $query->where('client_key', $clientKey);
    }

    public function scopeByRoute($query, $routeKey)
    {
        return $query->where('route_key', $routeKey);
    }

    public function scopeByDriver($query, $driverKey)
    {
        return $query->where('driver_key', $driverKey);
    }

    // Helper methods for reporting
    public function isOnTime(): bool
    {
        return $this->on_time_indicator;
    }

    public function hasException(): bool
    {
        return $this->exception_flag;
    }

    public function getDeliveryDelay(): ?float
    {
        if (!$this->actual_delivery_time || !$this->scheduled_delivery_time) {
            return null;
        }
        
        return $this->actual_delivery_time->diffInMinutes($this->scheduled_delivery_time) / 60.0;
    }

    public function getProfitMargin(): float
    {
        return $this->revenue > 0 ? (($this->revenue - $this->total_cost) / $this->revenue) * 100 : 0;
    }

    public function getCostPerMile(): float
    {
        return $this->distance_miles > 0 ? $this->total_cost / $this->distance_miles : 0;
    }
}