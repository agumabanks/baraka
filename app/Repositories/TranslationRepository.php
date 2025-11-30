<?php

namespace App\Repositories;

use App\Models\Translation;
use Illuminate\Support\Collection;

class TranslationRepository implements TranslationRepositoryInterface
{
    protected Translation $model;

    public function __construct(Translation $translation)
    {
        $this->model = $translation;
    }

    /**
     * Get all translations.
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        if (isset($filters['language_code'])) {
            $query->where('language_code', $filters['language_code']);
        }

        if (isset($filters['key'])) {
            $query->where('key', 'like', '%' . $filters['key'] . '%');
        }

        return $query->orderBy('language_code')->orderBy('key')->get();
    }

    /**
     * Get translations grouped by language.
     */
    public function groupedByLanguage(): Collection
    {
        return $this->model->newQuery()
            ->orderBy('language_code')
            ->orderBy('key')
            ->get()
            ->groupBy('language_code');
    }

    /**
     * Get all unique language codes.
     */
    public function getLanguageCodes(): array
    {
        return $this->model->newQuery()
            ->distinct()
            ->pluck('language_code')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get all unique keys.
     */
    public function getKeys(): array
    {
        return $this->model->newQuery()
            ->distinct()
            ->orderBy('key')
            ->pluck('key')
            ->values()
            ->toArray();
    }

    /**
     * Find a translation by ID.
     */
    public function find(int $id): ?Translation
    {
        return $this->model->find($id);
    }

    /**
     * Find translations by key and language.
     */
    public function findByKeyAndLanguage(string $key, string $languageCode): ?Translation
    {
        return $this->model
            ->where('key', $key)
            ->where('language_code', $languageCode)
            ->first();
    }

    /**
     * Create or update a translation.
     */
    public function updateOrCreate(array $attributes, array $values = []): Translation
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Update a translation.
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Delete a translation.
     */
    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Delete translations by language.
     */
    public function deleteByLanguage(string $languageCode): int
    {
        return $this->model->where('language_code', $languageCode)->delete();
    }

    /**
     * Bulk import translations.
     */
    public function bulkImport(array $translations): int
    {
        $count = 0;
        foreach ($translations as $translation) {
            $this->updateOrCreate(
                [
                    'key' => $translation['key'],
                    'language_code' => $translation['language_code']
                ],
                [
                    'value' => $translation['value'],
                    'description' => $translation['description'] ?? null,
                    'metadata' => $translation['metadata'] ?? null,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Get all translations for a specific language as fallback array.
     */
    public function getTranslationsForLanguage(string $languageCode): array
    {
        return $this->model->newQuery()
            ->where('language_code', $languageCode)
            ->pluck('value', 'key')
            ->toArray();
    }
}
