<?php

namespace App\Models\Backend;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Asset extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'author',
        'name',
        'assetcategory_id',
        'hub_id',
        'supplyer_name',
        'quantity',
        'warranty',
        'invoice_no',
        'amount',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [
            'user.name',
            'name',
            'assetcategorys.title',
            'hubs.name',
            'supplyer_name',
            'quantity',
            'warranty',
            'invoice_no',
            'amount',
            'description',
        ];

        return LogOptions::defaults()
            ->useLogName('Asset')
            ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}");
    }

    public function assetcategorys()
    {
        return $this->belongsTo(Assetcategory::class, 'assetcategory_id', 'id');
    }

    public function hubs()
    {
        return $this->belongsTo(Hub::class, 'hub_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'author', 'id');
    }

    public function getMyInsuranceStatusAttribute()
    {
        if ($this->insurance_status == 1) {
            return __('asset.yes');
        } elseif ($this->insurance_status == 2) {
            return __('asset.no');
        }
    }

    public function upload_registration()
    {
        return $this->belongsTo(Upload::class, 'registration_documents', 'id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function upload_insurance()
    {
        return $this->belongsTo(Upload::class, 'insurance_documents', 'id');
    }

    public function getMyRegistrationDocumentsAttribute()
    {
        if (! empty($this->upload_registration->original['original']) && file_exists(public_path($this->upload_registration->original['original']))) {
            return static_asset($this->upload_registration->original['original']);
        }

        return '';
    }

    public function getMyInsuranceDocumentsAttribute()
    {
        if (! empty($this->upload_insurance->original['original']) && file_exists(public_path($this->upload_insurance->original['original']))) {
            return static_asset($this->upload_insurance->original['original']);
        }

        return '';
    }

    public function getRenewInsuranceAttribute()
    {

        $start_date = Carbon::parse($this->insurance_registration)->startOfDay()->toDateTimeString();
        $end_date = Carbon::parse($this->insurance_expiry_date)->endOfDay()->toDateTimeString();
        $total_insurance_days = Carbon::parse($start_date)->diffInDays($end_date);
        $remaning_days = Carbon::now()->diffInDays($end_date);

        return '<span class="text-danger">'.$remaning_days.'</span> remaining'.' / '.$total_insurance_days.' ';

        return 'N/A';
    }

    public function getRenewRegistrationAttribute()
    {
        $start_date = Carbon::parse($this->registration_date)->startOfDay()->toDateTimeString();
        $end_date = Carbon::parse($this->registration_expiry_date)->endOfDay()->toDateTimeString();
        $total_registration_days = Carbon::parse($start_date)->diffInDays($end_date);
        $remaning_days = Carbon::now()->diffInDays($end_date);

        return '<span class="text-danger">'.$remaning_days.'</span> remaining'.' / '.$total_registration_days.' ';

        return 'N/A';
    }
}
