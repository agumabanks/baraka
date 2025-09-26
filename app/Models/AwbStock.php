<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AwbStock extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'carrier_code','iata_prefix','range_start','range_end','used_count','voided_count','hub_id','assigned_to_user_id','status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('AwbStock')
            ->logOnly($this->fillable)
            ->setDescriptionForEvent(fn (string $eventName) => $eventName);
    }
}
