<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManifestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'manifest_id',
        'manifestable_id',
        'manifestable_type',
        'loaded_at',
        'unloaded_at',
        'status',
    ];

    protected $casts = [
        'loaded_at' => 'datetime',
        'unloaded_at' => 'datetime',
    ];

    public function manifest()
    {
        return $this->belongsTo(Manifest::class);
    }

    public function manifestable()
    {
        return $this->morphTo();
    }
}
