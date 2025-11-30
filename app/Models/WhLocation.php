<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'parent_id', 'code', 'barcode', 'type', 'level', 'capacity', 'status', 'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'level' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(WhLocation::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(WhLocation::class, 'parent_id');
    }

    public function parcels()
    {
        return $this->hasMany(Parcel::class, 'current_location_id');
    }

    public function getFullNameAttribute()
    {
        if ($this->parent) {
            return $this->parent->full_name . ' > ' . $this->code;
        }
        return $this->code;
    }
}
