<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'base','counter','rate','provider','effective_at'
    ];

    protected $casts = [
        'effective_at' => 'datetime',
    ];
}

