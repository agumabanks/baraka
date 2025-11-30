<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Concerns\BranchScoped;

class Invoice extends Model
{
    use LogsActivity, BranchScoped;

    protected $fillable = [
        'invoice_id',
        'invoice_number',
        'shipment_id',
        'merchant_id',
        'customer_id',
        'branch_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'due_date',
        'paid_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'balance_due',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('invoice')
            ->logOnly(['invoice_number', 'shipment_id', 'total_amount', 'status'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} invoice");
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shipment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    public function chargeLines(): HasMany
    {
        return $this->hasMany(\App\Models\ChargeLine::class, 'shipment_id', 'shipment_id');
    }

    // Scopes
    public function scopeByStatus($query, InvoiceStatus|string $status)
    {
        $statusValue = $status instanceof InvoiceStatus ? $status->value : InvoiceStatus::fromString($status)?->value ?? $status;
        return $query->where('status', $statusValue);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', InvoiceStatus::PAID->value);
    }

    public function scopePayable($query)
    {
        return $query->whereIn('status', [
            InvoiceStatus::PENDING->value,
            InvoiceStatus::SENT->value,
            InvoiceStatus::OVERDUE->value,
        ]);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Business Logic
    public function markAsPaid(): void
    {
        $this->update([
            'status' => InvoiceStatus::PAID,
            'paid_at' => now(),
        ]);
    }

    public function markAsOverdue(): void
    {
        if ($this->due_date && $this->due_date->isPast() && $this->status !== InvoiceStatus::PAID) {
            $this->update(['status' => InvoiceStatus::OVERDUE]);
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof InvoiceStatus ? $this->status->label() : 'Unknown';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return $this->status instanceof InvoiceStatus ? $this->status->badgeColor() : 'secondary';
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->chargeLines()->sum('amount');
        $taxAmount = $subtotal * 0.1; // 10% tax example
        $totalAmount = $subtotal + $taxAmount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    public function getBalanceDueAttribute(): float
    {
        $paid = $this->payments()->sum('amount');
        return (float) max(0, ($this->total_amount ?? 0) - $paid);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class, 'invoice_id');
    }
}
