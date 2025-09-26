<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Support\UploadOriginal;

class Upload extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = ['original','one','two','three'];

    /**
    * Activity Log
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Upload')
        ->logOnly(['original'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    /**
     * Accessor to keep backward compatibility for usages like
     * $upload->original['original'] and also allow string casting.
     */
    public function getOriginalAttribute($value)
    {
        return new UploadOriginal([
            'original' => $value,
            'one'      => $this->attributes['one'] ?? null,
            'two'      => $this->attributes['two'] ?? null,
            'three'    => $this->attributes['three'] ?? null,
        ]);
    }
}
