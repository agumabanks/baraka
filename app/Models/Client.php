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
        'credit_limit',
        'risk_score',
        'kyc_status',
        'contacts',
        'addresses',
        'pipeline_stage',
    ];

    protected $casts = [
        'kyc_data' => 'array',
        'contacts' => 'array',
        'addresses' => 'array',
        'credit_limit' => 'decimal:2',
        'risk_score' => 'integer',
    ];

    protected $appends = [
        'is_over_limit',
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

    public function getIsOverLimitAttribute(): bool
    {
        if (is_null($this->credit_limit)) {
            return false;
        }

        $balance = Invoice::query()
            ->where('customer_id', $this->id)
            ->whereNotIn('status', ['PAID', 'CANCELLED'])
            ->sum('total_amount');

        return $balance > $this->credit_limit;
    }
}
