<?php

namespace App\Models\Backend;

use App\Enums\Status;
use App\Models\Backend\Branch;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_no',
        'chasis_number',
        'model',
        'year',
        'brand',
        'color',
        'description',
        'branch_id',
        'type',
        'capacity_kg',
        'capacity_volume',
        'ownership',
        'status',
    ];

    protected $casts = [
        'capacity_kg' => 'float',
        'capacity_volume' => 'float',
    ];

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

    public function currentDriver(): HasOne
    {
        return $this->hasOne(Driver::class, 'vehicle_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function getMyStatusAttribute()
    {
        $statusValue = is_numeric($this->status) ? (int) $this->status : strtoupper((string) $this->status);

        if ($statusValue === Status::ACTIVE || $statusValue === 'ACTIVE') {
            return '<span class="badge badge-pill badge-success">'.trans('status.'.Status::ACTIVE).'</span>';
        }

        return '<span class="badge badge-pill badge-danger">'.trans('status.'.Status::INACTIVE).'</span>';
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
