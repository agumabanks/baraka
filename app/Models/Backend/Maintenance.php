<?php

namespace App\Models\Backend;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;


    public function asset (){
        return $this->belongsTo(Asset::class,'asset_id','id');
    }

    public function invoiceOfThePurchases()
    {
        return $this->belongsTo(Upload::class, 'invoice_of_the_purchases', 'id');
    }

  public function getMyInvoiceOfThePurchasesAttribute()
    {
        if (!empty($this->invoiceOfThePurchases->original['original']) && file_exists(public_path($this->invoiceOfThePurchases->original['original']))) {
            return static_asset($this->invoiceOfThePurchases->original['original']);
        }
        return '';
    }

    public function getDueDaysAttribute(){
        $start_date = Carbon::parse($this->start_date)->startOfDay()->toDateTimeString();
        $end_date   = Carbon::parse($this->end_date)->endOfDay()->addSecond(1)->toDateTimeString();
        $totalDays  = Carbon::parse($start_date)->diffInDays($end_date);
        $DueDays    = Carbon::parse(Carbon::now()->startOfDay()->toDateTimeString())->diffInDays($end_date);
        $e = strtotime($end_date);
        $now = strtotime(Carbon::now()->toDateTimeString());
        if($e < $now ):
            $DueDays = 0;
        endif;
        $days['total_days'] = $totalDays;
        $days['due_days']   = $DueDays;
        return $days;

    }



}
