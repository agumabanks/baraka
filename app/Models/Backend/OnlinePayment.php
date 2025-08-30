<?php

namespace App\Models\Backend;

use App\Enums\BooleanStatus;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlinePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payer_details','source','parcel_id','wallet_id','merchant_id','transaction_id','amount','payment_method','status','order_id'
    ];
   
    protected $casts = ['payer_details'=>AsArrayObject::class];


    public function getPaidStatusAttribute(){
        if($this->is_paid == BooleanStatus::YES):
            $status  = '<span class="badge badge-success">Paid</span>';
        else:
            $status  = '<span class="badge badge-warning">Unpaid</span>';
        endif;
        return $status;
    }


}
