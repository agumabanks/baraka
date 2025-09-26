<?php

namespace App\Services;

use App\Models\AwbStock;
use Illuminate\Support\Facades\DB;

class AwbStockService
{
    public function allocate(AwbStock $stock): ?int
    {
        return DB::transaction(function () use ($stock) {
            if ($stock->used_count + $stock->voided_count >= (($stock->range_end - $stock->range_start) + 1)) {
                return null;
            }
            $next = (int)$stock->range_start + (int)$stock->used_count + (int)$stock->voided_count;
            $stock->increment('used_count');
            return $next;
        });
    }

    public function void(AwbStock $stock, int $count = 1): void
    {
        $stock->increment('voided_count', $count);
    }
}

