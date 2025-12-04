<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class UserSetting extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn($setting) => static::clearCache($setting->user_id));
        static::deleted(fn($setting) => static::clearCache($setting->user_id));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a setting value for a user
     */
    public static function getValue(int $userId, string $key, mixed $default = null): mixed
    {
        $settings = static::getAllForUser($userId);
        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value for a user
     */
    public static function setValue(int $userId, string $key, mixed $value): static
    {
        return static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );
    }

    /**
     * Get all settings for a user (cached)
     */
    public static function getAllForUser(int $userId): array
    {
        return Cache::remember(
            static::cacheKey($userId),
            now()->addHours(24),
            fn() => static::where('user_id', $userId)
                ->pluck('value', 'key')
                ->toArray()
        );
    }

    /**
     * Get user's preferred locale
     */
    public static function getLocale(int $userId): string
    {
        $supported = config('translations.supported', ['en', 'fr', 'sw']);
        $locale = static::getValue($userId, 'locale', config('app.locale', 'en'));
        
        return in_array($locale, $supported) ? $locale : 'en';
    }

    /**
     * Set user's preferred locale
     */
    public static function setLocale(int $userId, string $locale): static
    {
        $supported = config('translations.supported', ['en', 'fr', 'sw']);
        if (!in_array($locale, $supported)) {
            $locale = 'en';
        }
        
        return static::setValue($userId, 'locale', $locale);
    }

    /**
     * Get user's theme preference
     */
    public static function getTheme(int $userId): string
    {
        return static::getValue($userId, 'theme', 'auto');
    }

    /**
     * Get user's timezone
     */
    public static function getTimezone(int $userId): string
    {
        return static::getValue($userId, 'timezone', config('app.timezone', 'Africa/Kampala'));
    }

    /**
     * Get user's date format preference
     */
    public static function getUserDateFormat(int $userId): string
    {
        return static::getValue($userId, 'date_format', 'd/m/Y');
    }

    /**
     * Bulk set multiple settings
     */
    public static function setMany(int $userId, array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::setValue($userId, $key, $value);
        }
    }

    /**
     * Delete a setting
     */
    public static function deleteSetting(int $userId, string $key): bool
    {
        return static::where('user_id', $userId)
            ->where('key', $key)
            ->delete() > 0;
    }

    /**
     * Clear user settings cache
     */
    public static function clearCache(int $userId): void
    {
        Cache::forget(static::cacheKey($userId));
    }

    protected static function cacheKey(int $userId): string
    {
        return "user_settings_{$userId}";
    }
}
