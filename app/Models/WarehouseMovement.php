<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'parcel_id',
        'from_location_id',
        'to_location_id',
        'user_id',
        'type',
        'reference',
        'notes',
    ];

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function fromLocation()
    {
        return $this->belongsTo(WhLocation::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(WhLocation::class, 'to_location_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
