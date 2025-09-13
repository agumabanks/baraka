<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserConsent extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'type', 'version', 'ip', 'user_agent'];
}

