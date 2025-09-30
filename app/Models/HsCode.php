<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HsCode extends Model
{
    protected $table = 'hs_codes';

    protected $fillable = ['code', 'description'];
}
