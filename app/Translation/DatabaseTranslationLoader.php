<?php

namespace App\Translation;

use App\Models\Translation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\FileLoader;

class DatabaseTranslationLoader extends FileLoader
{
    public function __construct(Filesystem $files, $path)
    {
        parent::__construct($files, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function load($locale, $group, $namespace = null): array
    {
        $cacheKey = $this->cacheKey($locale, $group, $namespace);

        return Cache::remember(
            $cacheKey,
            config('translations.cache_ttl', 10_800),
            fn () => $this->loadFromDatabase($locale, $group, $namespace)
                ?: parent::load($locale, $group, $namespace)
        );
    }

    /**
     * Load translations from the database, grouped to match Laravel's loader expectations.
     */
    protected function loadFromDatabase(string $locale, string $group, ?string $namespace = null): array
    {
        if ($namespace && $namespace !== '*') {
            return [];
        }

        $query = Translation::forLanguage($locale);

        if ($group === '*' && $namespace === '*') {
            return $query->pluck('value', 'key')->toArray();
        }

        $prefix = $group === '*' ? '' : "{$group}.";

        $translations = $query
            ->when($prefix, fn ($q) => $q->where('key', 'like', "{$prefix}%"))
            ->get();

        if ($translations->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($translations as $translation) {
            $key = $prefix ? str_replace($prefix, '', $translation->key) : $translation->key;
            $result[$key] = $translation->value;
        }

        return $result;
    }

    protected function cacheKey(string $locale, string $group, ?string $namespace): string
    {
        $namespaceSegment = $namespace ?? '*';
        $version = $this->cacheVersion($locale);

        return sprintf('translations_v%s_%s_%s_%s', $version, $locale, $namespaceSegment, $group);
    }

    protected function cacheVersion(string $locale): int
    {
        $key = $this->cacheVersionKey($locale);

        if (! Cache::has($key)) {
            Cache::forever($key, 1);
        }

        return (int) Cache::get($key, 1);
    }

    protected function cacheVersionKey(string $locale): string
    {
        return "translation_cache_version_{$locale}";
    }
}
