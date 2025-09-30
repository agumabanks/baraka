<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PodProof extends Model
{
    protected $fillable = [
        'shipment_id',
        'driver_id',
        'signature',
        'photo',
        'otp_code',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /**
     * Get the shipment that this POD belongs to.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the driver who submitted this POD.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'driver_id');
    }

    /**
     * Get the signature URL.
     */
    public function getSignatureUrlAttribute(): string
    {
        if ($this->signature && Storage::disk('public')->exists($this->signature)) {
            return Storage::disk('public')->url($this->signature);
        }

        return '';
    }

    /**
     * Get the photo URL.
     */
    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo && Storage::disk('public')->exists($this->photo)) {
            return Storage::disk('public')->url($this->photo);
        }

        return '';
    }

    /**
     * Verify the POD with OTP.
     */
    public function verify(string $otp): bool
    {
        if ($this->otp_code === $otp && !$this->verified_at) {
            $this->update(['verified_at' => now()]);
            return true;
        }

        return false;
    }
}