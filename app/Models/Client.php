<?php

namespace App\Models;

use App\Models\Backend\Branch;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'primary_branch_id',
        'business_name',
        'status',
        'kyc_data',
    ];

    protected $casts = [
        'kyc_data' => 'array',
    ];

    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'client_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'client_id');
    }
}
