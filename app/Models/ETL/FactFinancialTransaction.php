<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactFinancialTransaction extends Model
{
    protected $table = 'fact_financial_transactions';
    public $timestamps = false;
    protected $primaryKey = 'transaction_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'transaction_key',
        'shipment_key',
        'client_key',
        'transaction_date_key',
        'transaction_type',
        'amount',
        'currency_code',
        'exchange_rate',
        'base_amount_usd',
        'payment_method',
        'transaction_status',
        'processing_fee',
        'discount_amount',
        'tax_amount',
        'net_amount',
        'payment_gateway',
        'reference_number',
        'description',
        'category',
        'subcategory',
        'cost_center',
        'variance_amount'
    ];

    protected $casts = [
        'transaction_date_key' => 'integer',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'base_amount_usd' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2'
    ];

    // Relationships
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(FactShipment::class, 'shipment_key', 'shipment_key');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(DimensionClient::class, 'client_key', 'client_key');
    }

    public function transactionDate(): BelongsTo
    {
        return $this->belongsTo(DimensionDate::class, 'transaction_date_key', 'date_key');
    }

    // Scopes for reporting
    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('transaction_status', $status);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date_key', [$startDate, $endDate]);
    }

    public function scopeRevenue($query)
    {
        return $query->where('transaction_type', 'revenue');
    }

    public function scopeExpense($query)
    {
        return $query->where('transaction_type', 'expense');
    }

    // Helper methods
    public function isRevenue(): bool
    {
        return $this->transaction_type === 'revenue';
    }

    public function isExpense(): bool
    {
        return $this->transaction_type === 'expense';
    }

    public function getAmountInUSD(): float
    {
        return $this->base_amount_usd;
    }

    public function getNetAmountAfterFees(): float
    {
        return $this->net_amount;
    }
}