<?php

namespace App\Models\Backend;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Hub extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'branch_code',
        'branch_type',
        'parent_hub_id',
    ];

    // Get all row. Descending order using scope.
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Hub')
            ->logOnly(['name', 'phone', 'address'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}");
    }

    public function getMyStatusAttribute()
    {
        return trans('status.'.$this->status);
    }

    // Scope: only active hubs
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE);
    }

    public function parcels()
    {
        return $this->hasMany(Parcel::class, 'hub_id', 'id');
    }

    // Hierarchy
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_hub_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_hub_id');
    }

    // Scope by branch code
    public function scopeCode($query, string $code)
    {
        return $query->where('branch_code', $code);
    }
}
