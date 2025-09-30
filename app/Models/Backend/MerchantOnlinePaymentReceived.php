<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantOnlinePaymentReceived extends Model
{
    use HasFactory;

    protected $fillable = ['created_at', 'updated_at', 'merchant_id', 'account_id', 'amount', 'reference', 'status', 'payment_type'];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
