<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SmsSendSetting extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = ['sms_send_status','status'];
    protected $table = 'sms_send_settings';
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'asc');
    }

    /**
    * Activity Log
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('SmsSendSetting')
        ->logOnly(['sms_send_status' ])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

}
