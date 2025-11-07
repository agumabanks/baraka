<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GeneralSettings extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'phone',
        'name',
        'tracking_id',
        'details',
        'prefix',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [

            'phone',
            'name',
            'tracking_id',
            'details',
            'prefix',
        ];

        return LogOptions::defaults()
            ->useLogName('General Settings')
            ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}");
    }

    // Get single row in Upload table.
    public function rxlogo()
    {
        return $this->belongsTo(Upload::class, 'logo', 'id');
    }

    public function lightlogo()
    {
        return $this->belongsTo(Upload::class, 'light_logo', 'id');
    }

    public function rxfavicon()
    {
        return $this->belongsTo(Upload::class, 'favicon', 'id');
    }

    public function getLogoImageAttribute()
    {
        $path = $this->rxlogo?->getAttribute('original');
        if (! empty($path) && file_exists(public_path($path))) {
            return static_asset($path);
        }

        return static_asset('images/default/logo1.png');
    }

    public function getPLogoImageAttribute()
    {
        $path = $this->rxlogo?->getAttribute('original');
        if (! empty($path) && file_exists(public_path($path))) {
            return public_path($path);
        }

        return public_path('images/default/logo1.png');
    }

    public function getLightLogoImageAttribute()
    {
        $path = $this->lightlogo?->getAttribute('original');
        if (! empty($path) && file_exists(public_path($path))) {
            return static_asset($path);
        }

        return static_asset('images/default/light-logo.png');
    }

    public function getFaviconImageAttribute()
    {
        $path = $this->rxfavicon?->getAttribute('original');
        if (! empty($path) && file_exists(public_path($path))) {
            return static_asset($path);
        }

        return static_asset('images/default/favicon.png');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function excenseRate()
    {
        return $this->belongsTo(Currency::class, 'currency', 'symbol');
    }
}
