<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = ['name', 'endpoint', 'events', 'secret'];

    protected $casts = ['events' => 'array'];
}
