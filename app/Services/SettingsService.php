<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    /**
     * Get a setting value with optional branch override
     */
    public function get(string $key, $default = null, ?int $branchId = null)
    {
        $cacheKey = $branchId ? "settings.{$branchId}.{$key}" : "settings.global.{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default, $branchId) {
            // Check for branch override first
            if ($branchId) {
                $override = DB::table('branch_setting_overrides')
                    ->where('branch_id', $branchId)
                    ->where('key', $key)
                    ->first();

                if ($override) {
                    return $this->castValue($override->value, $this->getSettingType($key));
                }
            }

            // Fall back to system setting
            $setting = DB::table('system_settings')
                ->where('key', $key)
                ->first();

            if ($setting) {
                return $this->castValue($setting->value, $setting->type);
            }

            return $default;
        });
    }

    /**
     * Set a system-wide setting (admin only)
     */
    public function set(string $key, $value): void
    {
        $type = $this->detectType($value);

        DB::table('system_settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $this->stringifyValue($value),
                'type' => $type,
                'updated_at' => now(),
            ]
        );

        // Clear cache
        Cache::forget("settings.global.{$key}");
    }

    /**
     * Set a branch-specific override
     */
    public function setBranchOverride(int $branchId, string $key, $value, int $userId): void
    {
        // Check if setting allows overrides
        $setting = DB::table('system_settings')->where('key', $key)->first();

        if (!$setting || !$setting->is_public) {
            throw new \Exception("Setting '{$key}' cannot be overridden by branches.");
        }

        DB::table('branch_setting_overrides')->updateOrInsert(
            ['branch_id' => $branchId, 'key' => $key],
            [
                'value' => $this->stringifyValue($value),
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]
        );

        // Clear cache
        Cache::forget("settings.{$branchId}.{$key}");
    }

    /**
     * Remove a branch override (revert to system default)
     */
    public function removeBranchOverride(int $branchId, string $key): void
    {
        DB::table('branch_setting_overrides')
            ->where('branch_id', $branchId)
            ->where('key', $key)
            ->delete();

        Cache::forget("settings.{$branchId}.{$key}");
    }

    /**
     * Get all settings for a category
     */
    public function getCategory(string $category, ?int $branchId = null): array
    {
        $settings = DB::table('system_settings')
            ->where('category', $category)
            ->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = [
                'value' => $this->get($setting->key, null, $branchId),
                'default' => $this->castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
                'has_override' => $branchId ? $this->hasOverride($branchId, $setting->key) : false,
            ];
        }

        return $result;
    }

    /**
     * Get all branch overrides
     */
    public function getBranchOverrides(int $branchId): array
    {
        return DB::table('branch_setting_overrides')
            ->where('branch_id', $branchId)
            ->get()
            ->keyBy('key')
            ->toArray();
    }

    /**
     * Check if branch has override for a setting
     */
    public function hasOverride(int $branchId, string $key): bool
    {
        return DB::table('branch_setting_overrides')
            ->where('branch_id', $branchId)
            ->where('key', $key)
            ->exists();
    }

    /**
     * Clear all settings cache
     */
    public function clearCache(?int $branchId = null): void
    {
        if ($branchId) {
            Cache::flush(); // Simpler approach, clear all
        } else {
            Cache::flush();
        }
    }

    /**
     * Get setting type from system settings
     */
    protected function getSettingType(string $key): string
    {
        $setting = DB::table('system_settings')
            ->where('key', $key)
            ->first();

        return $setting->type ?? 'string';
    }

    /**
     * Cast value to proper type
     */
    protected function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => (string) $value,
        };
    }

    /**
     * Convert value to string for storage
     */
    protected function stringifyValue($value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Detect value type
     */
    protected function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'decimal';
        }

        if (is_array($value) || is_object($value)) {
            return 'json';
        }

        return 'string';
    }
}
