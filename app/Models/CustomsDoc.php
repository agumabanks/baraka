<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomsDoc extends Model
{
    protected $table = 'customs_docs';

    protected $fillable = ['shipment_id', 'type', 'path'];
}
