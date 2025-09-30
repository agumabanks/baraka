<?php

namespace App\Services;

use App\Models\SurchargeRule;
use Illuminate\Support\Carbon;

class RatingService
{
    public function dimWeightKg(int $volumeCm3, int $dimFactor): float
    {
        return round($volumeCm3 / max(1, $dimFactor), 3);
    }

    public function priceWithSurcharges(float $base, float $billableWeight, $date = null): array
    {
        $date = $date ? Carbon::parse($date) : now();
        $applied = [];
        $total = $base;

        $rules = SurchargeRule::query()
            ->where('active', true)
            ->whereDate('active_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('active_to')->orWhereDate('active_to', '>=', $date);
            })->get();

        foreach ($rules as $rule) {
            $amount = $rule->rate_type === 'percent'
                ? round($base * ($rule->amount / 100), 2)
                : (float) $rule->amount;
            $total += $amount;
            $applied[] = [
                'code' => $rule->code,
                'name' => $rule->name,
                'amount' => $amount,
            ];
        }

        return [
            'base' => $base,
            'billable_weight' => $billableWeight,
            'total' => round($total, 2),
            'applied' => $applied,
        ];
    }
}
