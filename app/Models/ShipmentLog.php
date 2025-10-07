<?php

namespace App\Models;

<<<<<<< ours
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
>>>>>>> theirs
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentLog extends Model
{
<<<<<<< ours
    protected $fillable = [
        'shipment_id',
        'branch_id',
        'user_id',
        'status',
        'description',
        'location',
        'latitude',
        'longitude',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
=======
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'status',
        'description',
        'location',
        'created_by',
        'metadata',
        'logged_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'logged_at' => 'datetime',
>>>>>>> theirs
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

<<<<<<< ours
    public function branch(): BelongsTo
    {
        return $this->belongsTo(UnifiedBranch::class, 'branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
=======
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
>>>>>>> theirs
    }
}
