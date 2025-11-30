<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class BranchCache
{
    public static function key(string $suffix, int $branchId): string
    {
        return "branch:{$branchId}:{$suffix}";
    }

    public static function rememberStats(int $branchId, Closure $callback)
    {
        return Cache::remember(self::key('dashboard:stats', $branchId), now()->addMinutes(5), $callback);
    }

    public static function rememberBoard(int $branchId, string $channel, Closure $callback)
    {
        return Cache::remember(self::key("board:{$channel}", $branchId), now()->addMinutes(3), $callback);
    }

    public static function flushForBranch(?int $branchId): void
    {
        if (! $branchId) {
            return;
        }

        $keys = [
            self::key('dashboard:stats', $branchId),
            self::key('board:operations', $branchId),
            self::key('board:finance', $branchId),
            self::key('board:warehouse', $branchId),
            self::key('board:fleet', $branchId),
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public static function flushForBranches(array $branchIds): void
    {
        foreach (array_unique(array_filter($branchIds)) as $id) {
            self::flushForBranch((int) $id);
        }
    }
}
