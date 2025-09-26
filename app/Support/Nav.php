<?php

namespace App\Support;

class Nav
{
    /**
     * Returns 'active' if current route matches any pattern.
     */
    public static function active($patterns): string
    {
        $patterns = is_array($patterns) ? $patterns : [$patterns];
        foreach ($patterns as $p) {
            if (request()->routeIs($p)) {
                return 'active';
            }
        }
        return '';
    }

    public static function show($patterns): string
    {
        $patterns = is_array($patterns) ? $patterns : [$patterns];
        foreach ($patterns as $p) {
            if (request()->routeIs($p)) {
                return 'show';
            }
        }
        return '';
    }

    public static function expanded($patterns): string
    {
        return self::show($patterns) === 'show' ? 'true' : 'false';
    }

    /**
     * Evaluate permission visibility defined as a compact string.
     * Supported formats:
     *  - null (always visible)
     *  - 'hasPermission:key' (custom hasPermission helper)
     *  - 'hasPermission:key|hasPermission:other' (OR-combined)
     *  - 'can:ability,Class\\Name' (policy check)
     *  - 'env:KEY,value' (compare env(KEY) === value)
     */
    public static function canShowBySignature(?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return true;
        }

        // OR-split: allow multiple pipes
        $parts = explode('|', $signature);
        foreach ($parts as $expr) {
            $expr = trim($expr);
            if ($expr === '') continue;

            if (str_starts_with($expr, 'hasPermission:')) {
                $key = substr($expr, strlen('hasPermission:'));
                if (function_exists('hasPermission') && hasPermission($key) === true) {
                    return true;
                }
            } elseif (str_starts_with($expr, 'can:')) {
                $payload = substr($expr, strlen('can:'));
                [$ability, $class] = array_pad(array_map('trim', explode(',', $payload, 2)), 2, null);
                if ($ability && $class && \Illuminate\Support\Facades\Gate::allows($ability, $class)) {
                    return true;
                }
            } elseif (str_starts_with($expr, 'env:')) {
                $payload = substr($expr, strlen('env:'));
                [$envKey, $expected] = array_pad(array_map('trim', explode(',', $payload, 2)), 2, null);
                if ($envKey !== null) {
                    $val = env($envKey);
                    if ((string)$val === (string)$expected) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Determine if any child of a bucket is visible given permission signatures.
     */
    public static function anyVisible(array $items): bool
    {
        foreach ($items as $item) {
            $hasChildren = isset($item['children']) && is_array($item['children']) && count($item['children']) > 0;
            $ok = isset($item['permission_check']) ? self::canShowBySignature($item['permission_check']) : true;
            if ($hasChildren) {
                if (self::anyVisible($item['children'])) {
                    return true;
                }
            } else {
                if ($ok) return true;
            }
        }
        return false;
    }
}
