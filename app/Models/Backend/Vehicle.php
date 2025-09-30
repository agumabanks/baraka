<?php

namespace App\Models\Backend;

use App\Enums\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    // public function getActivitylogOptions(): LogOptions
    // {
    //     $logAttributes = [
    //         'driver.user.name',
    //         'name',
    //         'plate_no',
    //         'chasis_number',
    //         'model',
    //         'year',
    //         'brand',
    //         'color',
    //         'description',
    //         'status'
    //     ];
    //     return LogOptions::defaults()
    //     ->useLogName('Vehicle')
    //     ->logOnly($logAttributes)
    //     ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    // }

    public function driver()
    {
        return $this->belongsTo(DeliveryMan::class, 'driver_id', 'id');
    }

    public function getMyStatusAttribute()
    {
        if ($this->status == Status::ACTIVE) {
            $status = '<span class="badge badge-pill badge-success">'.trans('status.'.$this->status).'</span>';
        } else {
            $status = '<span class="badge badge-pill badge-danger">'.trans('status.'.$this->status).'</span>';
        }

        return $status;
    }

    // public function getRenewInsuranceAttribute(){
    //     $asset = Asset::where('vehicle_id',$this->id)->get()->last();
    //     if($asset):
    //         $start_date = Carbon::parse($asset->insurance_registration)->startOfDay()->toDateTimeString();
    //         $end_date = Carbon::parse($asset->insurance_expiry_date)->endOfDay()->toDateTimeString();
    //        $total_insurance_days =  Carbon::parse($start_date)->diffInDays($end_date);
    //        $remaning_days =  Carbon::now()->diffInDays($end_date);
    //        return '<span class="text-danger">'.$remaning_days.'</span> Days remaining'.' / '.$total_insurance_days.' Days';
    //     endif;
    //     return 'N/A';
    // }

    // public function fuels(){
    //     return $this->hasMany(Fuel::class,'vehicle_id','id');
    // }
    // public function assets(){
    //     return $this->hasMany(Asset::class,'vehicle_id','id');
    // }
    // public function maintenances(){
    //     return $this->hasMany(Maintenance::class,'vehicle_id','id');
    // }
    // public function accidents(){
    //     return $this->hasMany(Accident::class,'vehicle_id','id');
    // }

}
