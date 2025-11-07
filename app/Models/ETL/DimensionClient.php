<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimensionClient extends Model
{
    protected $table = 'dim_client';
    public $timestamps = false;
    protected $primaryKey = 'client_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'client_key',
        'client_name',
        'client_type',
        'industry',
        'account_manager',
        'contract_start_date',
        'contract_end_date',
        'credit_limit',
        'payment_terms_days',
        'is_active'
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(FactShipment::class, 'client_key', 'client_key');
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FactFinancialTransaction::class, 'client_key', 'client_key');
    }
}