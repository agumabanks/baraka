<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'language_code',
        'value',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Scope to get translations by language code.
     */
    public function scopeForLanguage($query, $languageCode)
    {
        return $query->where('language_code', $languageCode);
    }

    /**
     * Scope to get translations by key.
     */
    public function scopeForKey($query, $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Get a specific translation value.
     */
    public static function getValue(string $key, ?string $languageCode = null, ?string $default = null): string
    {
        $languageCode = $languageCode ?? app()->getLocale();
        
        $translation = static::forLanguage($languageCode)
            ->forKey($key)
            ->first();

        if ($translation) {
            return $translation->value;
        }

        // Fallback to English if default is not provided
        if ($languageCode !== 'en') {
            $englishTranslation = static::forLanguage('en')
                ->forKey($key)
                ->first();
            
            if ($englishTranslation) {
                return $englishTranslation->value;
            }
        }

        return $default ?? $key;
    }

    /**
     * Get all translations for a specific language as an array.
     */
    public static function getAllForLanguage(string $languageCode): array
    {
        return static::forLanguage($languageCode)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Update or create a translation.
     */
    public static function updateOrCreateTranslation(string $key, string $languageCode, string $value, ?string $description = null): static
    {
        return static::updateOrCreate(
            ['key' => $key, 'language_code' => $languageCode],
            ['value' => $value, 'description' => $description]
        );
    }
}
