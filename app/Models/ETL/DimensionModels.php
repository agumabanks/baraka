<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimensionRoute extends Model
{
    protected $table = 'dim_route';
    public $timestamps = false;
    protected $primaryKey = 'route_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'route_key',
        'route_name',
        'route_type',
        'distance_miles',
        'estimated_time_hours',
        'origin_address',
        'destination_address',
        'is_active',
        'created_date'
    ];

    protected $casts = [
        'distance_miles' => 'decimal:2',
        'estimated_time_hours' => 'decimal:2',
        'is_active' => 'boolean',
        'created_date' => 'date'
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(FactShipment::class, 'route_key', 'route_key');
    }
}

class DimensionDriver extends Model
{
    protected $table = 'dim_driver';
    public $timestamps = false;
    protected $primaryKey = 'driver_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'driver_key',
        'driver_name',
        'license_class',
        'experience_years',
        'safety_rating',
        'hire_date',
        'termination_date',
        'is_active'
    ];

    protected $casts = [
        'experience_years' => 'integer',
        'safety_rating' => 'decimal:2',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(FactShipment::class, 'driver_key', 'driver_key');
    }
}

class DimensionBranch extends Model
{
    protected $table = 'dim_branch';
    public $timestamps = false;
    protected $primaryKey = 'branch_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'branch_key',
        'branch_name',
        'branch_type',
        'region',
        'address',
        'manager',
        'phone',
        'email',
        'is_active',
        'opened_date'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'opened_date' => 'date'
    ];

    public function originShipments(): HasMany
    {
        return $this->hasMany(FactShipment::class, 'origin_branch_key', 'branch_key');
    }

    public function destinationShipments(): HasMany
    {
        return $this->hasMany(FactShipment::class, 'destination_branch_key', 'branch_key');
    }
}

class DimensionCarrier extends Model
{
    protected $table = 'dim_carrier';
    public $timestamps = false;
    protected $primaryKey = 'carrier_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'carrier_key',
        'carrier_name',
        'carrier_type',
        'service_areas',
        'contract_terms',
        'rating',
        'contact_info',
        'is_active',
        'contract_start_date'
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
        'contract_start_date' => 'date'
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(FactShipment::class, 'carrier_key', 'carrier_key');
    }
}

class DimensionDate extends Model
{
    protected $table = 'dim_date';
    public $timestamps = false;
    protected $primaryKey = 'date_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'date_key',
        'date_value',
        'year',
        'quarter',
        'month',
        'week',
        'day',
        'day_name',
        'month_name',
        'is_weekend',
        'is_holiday',
        'day_of_year',
        'week_of_year'
    ];

    protected $casts = [
        'date_value' => 'date',
        'year' => 'integer',
        'quarter' => 'integer',
        'month' => 'integer',
        'week' => 'integer',
        'day' => 'integer',
        'is_weekend' => 'boolean',
        'is_holiday' => 'boolean',
        'day_of_year' => 'integer',
        'week_of_year' => 'integer'
    ];

    public function pickupShipments(): HasMany
    {
        return $this->hasMany(FactShipment::class, 'pickup_date_key', 'date_key');
    }

    public function deliveryShipments(): HasMany
    {
        return $this->hasMany(FactShipment::class, 'delivery_date_key', 'date_key');
    }
}