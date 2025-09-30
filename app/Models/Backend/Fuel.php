<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fuel extends Model
{
    use HasFactory;

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }

    public function invoiceofFuel()
    {
        return $this->belongsTo(Upload::class, 'invoice_of_fuel', 'id');
    }

    public function getMyInvoiceOfFuelAttribute()
    {
        if (! empty($this->invoiceofFuel->original['original']) && file_exists(public_path($this->invoiceofFuel->original['original']))) {
            return static_asset($this->invoiceofFuel->original['original']);
        }

        return '';
    }
}
