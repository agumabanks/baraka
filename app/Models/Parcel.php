<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parcel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipment_id',
        'current_location_id',
        'barcode',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'volume_cbm',
        'description',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'volume_cbm' => 'decimal:4',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function currentLocation()
    {
        return $this->belongsTo(WhLocation::class, 'current_location_id');
    }

    public function calculateVolume()
    {
        $this->volume_cbm = ($this->length_cm * $this->width_cm * $this->height_cm) / 1000000;
        return $this->volume_cbm;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($parcel) {
            if (!$parcel->volume_cbm && $parcel->length_cm && $parcel->width_cm && $parcel->height_cm) {
                $parcel->calculateVolume();
            }
        });
    }
}
