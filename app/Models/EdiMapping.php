<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdiMapping extends Model
{
    protected $fillable = [
        'edi_type',
        'sender_code',
        'transformations',
    ];

    protected $casts = [
        'transformations' => 'array',
    ];
}
