<?php

namespace App\Models\Backend;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'primary_branch_id',
        'business_name',
        'status',
        'kyc_data',
    ];

    protected $casts = [
        'kyc_data' => 'array',
        'status' => 'string',
    ];

    /**
     * Primary branch relationship
     */
    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    /**
     * All shipments for this client
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'client_id');
    }

    /**
     * Active shipments (not delivered or cancelled)
     */
    public function activeShipments(): HasMany
    {
        return $this->shipments()->whereNotIn('current_status', ['delivered', 'cancelled']);
    }
}
