<?php

use App\Repositories\TranslationRepositoryInterface;
use Illuminate\Support\Facades\Cache;

if (!function_exists('trans_db')) {
    /**
     * Resolve a translation from the database-backed cache, falling back to Laravel files.
     */
    function trans_db(string $key, array $replace = [], ?string $locale = null, ?string $default = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $translations = get_translation_cache($locale);

        $value = $translations[$key] ?? null;
        
        if ($value === null) {
            $value = trans($key, $replace, $locale);
        }

        if ($value === $key && $default) {
            $value = $default;
        }

        foreach ($replace as $search => $replacement) {
            $value = str_replace(':' . $search, $replacement, $value);
        }

        return $value;
    }
}

if (!function_exists('__db')) {
    function __db($key = null, $replace = [], $locale = null): string
    {
        if (is_null($key)) {
            return $key;
        }
        
        return trans_db($key, $replace, $locale);
    }
}

if (!function_exists('trans_choice_db')) {
    function trans_choice_db($key, $number, array $replace = [], $locale = null): string
    {
        return trans_choice($key, $number, $replace, $locale);
    }
}

if (!function_exists('get_translation_cache')) {
    /**
     * Get all translations for a language as an array (with caching)
     */
    function get_translation_cache($locale = null): array
    {
        static $inMemory = [];

        $locale = $locale ?? app()->getLocale();
        if (isset($inMemory[$locale])) {
            return $inMemory[$locale];
        }
        $cacheKey = "translations_array_{$locale}";

        $translations = Cache::remember(
            $cacheKey,
            config('translations.cache_ttl', 10_800),
            function () use ($locale) {
                /** @var TranslationRepositoryInterface $repository */
                $repository = app(TranslationRepositoryInterface::class);

                return $repository->getTranslationsForLanguage($locale);
            }
        );

        $inMemory[$locale] = is_array($translations) ? $translations : [];
        return $inMemory[$locale];
    }
}

if (!function_exists('clear_translation_cache')) {
    /**
     * Clear translation cache for a specific language or all languages.
     */
    function clear_translation_cache($locale = null): void
    {
        $locales = $locale ? [$locale] : translation_supported_languages();

        foreach ($locales as $lang) {
            Cache::forget("translations_array_{$lang}");
            Cache::forget("api_translations_{$lang}");
            Cache::increment(translation_cache_version_key($lang));
        }
    }
}

if (!function_exists('translation_supported_languages')) {
    function translation_supported_languages(): array
    {
        return config('translations.supported', ['en']);
    }
}

if (!function_exists('translation_cache_version_key')) {
    function translation_cache_version_key(string $locale): string
    {
        $key = "translation_cache_version_{$locale}";

        if (! Cache::has($key)) {
            Cache::forever($key, 1);
        }

        return $key;
    }
}
