<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'effective_date',
        'source',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'date',
    ];

    /**
     * Get current rate for currency pair
     */
    public static function getRate(string $from, string $to, ?\Carbon\Carbon $date = null): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ?? now();

        $rate = self::where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->first();

        if ($rate) {
            return (float) $rate->rate;
        }

        // Try inverse
        $inverseRate = self::where('base_currency', $to)
            ->where('target_currency', $from)
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->first();

        if ($inverseRate && $inverseRate->rate > 0) {
            return 1 / (float) $inverseRate->rate;
        }

        return null;
    }

    /**
     * Convert amount between currencies
     */
    public static function convert(float $amount, string $from, string $to, ?\Carbon\Carbon $date = null): ?float
    {
        $rate = self::getRate($from, $to, $date);

        if ($rate === null) {
            return null;
        }

        return round($amount * $rate, 2);
    }

    /**
     * Set rate for currency pair
     */
    public static function setRate(string $from, string $to, float $rate, ?\Carbon\Carbon $date = null, string $source = 'manual'): self
    {
        $date = $date ?? now();

        return self::updateOrCreate(
            [
                'base_currency' => $from,
                'target_currency' => $to,
                'effective_date' => $date->toDateString(),
            ],
            [
                'rate' => $rate,
                'source' => $source,
            ]
        );
    }
}
