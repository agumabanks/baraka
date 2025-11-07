<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProcessing extends Model
{
    protected $fillable = [
        'shipment_key',
        'client_key',
        'payment_id',
        'payment_method',
        'gateway_id',
        'transaction_id',
        'payment_amount',
        'processing_fee',
        'net_amount',
        'payment_date',
        'settlement_date',
        'reconciliation_status',
        'reconciliation_date',
        'payment_status',
        'failure_reason',
        'retry_count',
        'settlement_batch_id',
        'settlement_reference',
        'currency_code',
        'exchange_rate',
        'dispute_amount',
        'dispute_reason',
        'chargeback_amount',
        'refund_amount',
        'notes'
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'settlement_date' => 'date',
        'reconciliation_date' => 'datetime',
        'retry_count' => 'integer',
        'exchange_rate' => 'decimal:6',
        'dispute_amount' => 'decimal:2',
        'chargeback_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2'
    ];

    // Payment method constants
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_DEBIT_CARD = 'debit_card';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CASH = 'cash';
    const METHOD_CHECK = 'check';
    const METHOD_DIGITAL_WALLET = 'digital_wallet';
    const METHOD_COD = 'cod';
    const METHOD_MOBILE_PAYMENT = 'mobile_payment';

    // Payment status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_CHARGEBACK = 'chargeback';

    // Reconciliation status constants
    const RECONCILIATION_PENDING = 'pending';
    const RECONCILIATION_RECONCILED = 'reconciled';
    const RECONCILIATION_DISCREPANCY = 'discrepancy';
    const RECONCILIATION_UNDER_REVIEW = 'under_review';

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\FactShipment::class, 'shipment_key', 'shipment_key');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionClient::class, 'client_key', 'client_key');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Backend\Payment::class, 'payment_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByReconciliationStatus($query, $status)
    {
        return $query->where('reconciliation_status', $status);
    }

    public function scopeReconciled($query)
    {
        return $query->where('reconciliation_status', self::RECONCILIATION_RECONCILED);
    }

    public function scopePendingReconciliation($query)
    {
        return $query->where('reconciliation_status', self::RECONCILIATION_PENDING);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    public function scopeSettled($query)
    {
        return $query->whereNotNull('settlement_date');
    }

    public function scopeDisputed($query)
    {
        return $query->where('payment_status', self::STATUS_DISPUTED)
                    ->orWhere('dispute_amount', '>', 0);
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->payment_status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->payment_status === self::STATUS_FAILED;
    }

    public function isDisputed(): bool
    {
        return $this->payment_status === self::STATUS_DISPUTED;
    }

    public function isReconciled(): bool
    {
        return $this->reconciliation_status === self::RECONCILIATION_RECONCILED;
    }

    public function isSettled(): bool
    {
        return !is_null($this->settlement_date);
    }

    public function calculateProcessingRate(): float
    {
        if ($this->payment_amount <= 0) {
            return 0;
        }

        return ($this->processing_fee / $this->payment_amount) * 100;
    }

    public function getNetAmount(): float
    {
        return $this->payment_amount - $this->processing_fee;
    }

    public function getSettlementDays(): ?int
    {
        if (!$this->settlement_date) {
            return null;
        }

        return $this->payment_date->diffInDays($this->settlement_date);
    }

    public function needsReconciliation(): bool
    {
        return $this->reconciliation_status === self::RECONCILIATION_PENDING 
               && $this->payment_status === self::STATUS_COMPLETED;
    }

    public function incrementRetryCount(): void
    {
        $this->increment('retry_count');
    }

    public function markAsReconciled(string $reference = null): void
    {
        $this->reconciliation_status = self::RECONCILIATION_RECONCILED;
        $this->reconciliation_date = now();
        
        if ($reference) {
            $this->settlement_reference = $reference;
        }
        
        $this->save();
    }

    public function markAsFailed(string $reason): void
    {
        $this->payment_status = self::STATUS_FAILED;
        $this->failure_reason = $reason;
        $this->save();
    }

    public function processDispute(float $amount, string $reason): void
    {
        $this->dispute_amount = $amount;
        $this->dispute_reason = $reason;
        $this->payment_status = self::STATUS_DISPUTED;
        $this->save();
    }

    public function processRefund(float $amount): void
    {
        $this->refund_amount = $amount;
        $this->payment_status = self::STATUS_REFUNDED;
        $this->save();
    }

    public function processChargeback(float $amount): void
    {
        $this->chargeback_amount = $amount;
        $this->payment_status = self::STATUS_CHARGEBACK;
        $this->save();
    }
}