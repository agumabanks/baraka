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

    /**
     * Get all unique translation keys.
     */
    public static function getAllKeys(): array
    {
        return static::distinct()->pluck('key')->sort()->values()->toArray();
    }

    /**
     * Get translations for all languages grouped by key.
     */
    public static function getMultiLanguageTranslations(array $languageCodes, ?string $search = null)
    {
        $keys = static::distinct('key');
        
        if ($search) {
            $keys = $keys->where(function ($query) use ($search) {
                $query->where('key', 'like', "%{$search}%")
                      ->orWhere('value', 'like', "%{$search}%");
            });
        }
        
        $keys = $keys->pluck('key')->unique();
        
        $translations = [];
        foreach ($keys as $key) {
            $translations[$key] = [];
            foreach ($languageCodes as $lang) {
                $translation = static::forLanguage($lang)->forKey($key)->first();
                $translations[$key][$lang] = $translation ? $translation->value : null;
            }
        }
        
        return $translations;
    }

    /**
     * Get completion statistics for each language.
     */
    public static function getCompletionStats(array $languageCodes): array
    {
        $totalKeys = static::distinct()->count('key');
        $stats = [];
        
        foreach ($languageCodes as $lang) {
            $translatedCount = static::forLanguage($lang)
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->distinct('key')
                ->count('key');
            
            $stats[$lang] = [
                'translated' => $translatedCount,
                'total' => $totalKeys,
                'percentage' => $totalKeys > 0 ? round(($translatedCount / $totalKeys) * 100, 1) : 0,
            ];
        }
        
        return $stats;
    }

    /**
     * Scope to filter by completion status.
     */
    public function scopeByCompletionStatus($query, string $status, array $languageCodes)
    {
        $keys = static::distinct()->pluck('key');
        
        $filteredKeys = $keys->filter(function ($key) use ($status, $languageCodes) {
            $translationCount = 0;
            foreach ($languageCodes as $lang) {
                $translation = static::forLanguage($lang)->forKey($key)->first();
                if ($translation && !empty($translation->value)) {
                    $translationCount++;
                }
            }
            
            return match ($status) {
                'complete' => $translationCount === count($languageCodes),
                'incomplete' => $translationCount > 0 && $translationCount < count($languageCodes),
                'empty' => $translationCount === 0,
                default => true,
            };
        });
        
        return $query->whereIn('key', $filteredKeys);
    }

    /**
     * Bulk create/update translations.
     */
    public static function bulkUpdateTranslations(array $translations): int
    {
        $count = 0;
        foreach ($translations as $key => $languages) {
            foreach ($languages as $lang => $value) {
                if (!empty($value)) {
                    static::updateOrCreateTranslation($key, $lang, $value);
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Delete a translation key across all languages.
     */
    public static function deleteKey(string $key): int
    {
        return static::where('key', $key)->delete();
    }
}
