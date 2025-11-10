<?php

namespace App\Repositories;

interface TranslationRepositoryInterface
{
    public function all(array $filters = []);
    public function groupedByLanguage();
    public function getLanguageCodes(): array;
    public function getKeys(): array;
    public function find(int $id): ?\App\Models\Translation;
    public function findByKeyAndLanguage(string $key, string $languageCode): ?\App\Models\Translation;
    public function updateOrCreate(array $attributes, array $values = []): \App\Models\Translation;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function deleteByLanguage(string $languageCode): int;
    public function bulkImport(array $translations): int;
    public function getTranslationsForLanguage(string $languageCode): array;
}
