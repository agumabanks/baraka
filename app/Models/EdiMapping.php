<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdiMapping extends Model
{
    protected $fillable = [
        'document_type',
        'version',
        'field_map',
        'description',
        'active',
    ];

    protected $casts = [
        'field_map' => 'array',
        'active' => 'boolean',
    ];
}
