<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DpsScreening extends Model
{
    use HasFactory;

    protected $fillable = [
        'screened_type', 'screened_id', 'query', 'response_json', 'result', 'list_name', 'match_score', 'screened_at',
    ];

    protected $casts = [
        'response_json' => 'array',
        'screened_at' => 'datetime',
    ];
}
