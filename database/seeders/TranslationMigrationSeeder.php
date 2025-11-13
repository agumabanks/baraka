<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranslationMigrationSeeder extends Seeder
{
    protected array $supportedLanguages = ['en', 'fr', 'sw'];
    protected array $migratedKeys = [];
    
    /**
     * Run the database seeds to migrate existing translation files to database
     */
    public function run(): void
    {
        $this->command->info('Starting translation migration from files to database...');
        
        $englishKeys = $this->extractAllKeysFromLanguageFiles('en');
        $this->command->info("Found " . count($englishKeys) . " translation keys in English files");
        
        foreach ($this->supportedLanguages as $language) {
            $this->migrateLanguageToDatabase($language, $englishKeys);
        }
        
        $this->command->info('Translation migration completed!');
    }
    
    /**
     * Extract all translation keys from language files
     */
    protected function extractAllKeysFromLanguageFiles(string $language): array
    {
        $keys = [];
        $langPath = base_path("lang/{$language}");
        
        if (!File::exists($langPath)) {
            return $keys;
        }
        
        $files = File::allFiles($langPath);
        
        foreach ($files as $file) {
            $namespace = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $content = require $file->getRealPath();
            
            if (is_array($content)) {
                $this->extractNestedKeys($content, $namespace . '.', $keys);
            }
        }
        
        return array_unique($keys);
    }
    
    /**
     * Extract nested keys recursively
     */
    protected function extractNestedKeys(array $array, string $prefix, array &$keys): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix . $key;
            
            if (is_array($value)) {
                $this->extractNestedKeys($value, $fullKey . '.', $keys);
            } else {
                $keys[] = $fullKey;
            }
        }
    }
    
    /**
     * Migrate translations for a specific language to database
     */
    protected function migrateLanguageToDatabase(string $language, array $englishKeys): void
    {
        $this->command->info("Migrating {$language} translations...");
        
        $langPath = base_path("lang/{$language}");
        $translations = [];
        
        if (!File::exists($langPath)) {
            $this->command->warn("Language folder {$language} not found, skipping...");
            return;
        }
        
        $files = File::allFiles($langPath);
        
        foreach ($files as $file) {
            $namespace = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $content = require $file->getRealPath();
            
            if (is_array($content)) {
                $this->flattenTranslations($content, $namespace . '.', $translations);
            }
        }
        
        $this->insertTranslationsToDatabase($language, $translations, $englishKeys);
    }
    
    /**
     * Flatten nested translation arrays
     */
    protected function flattenTranslations(array $array, string $prefix, array &$translations): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix . $key;
            
            if (is_array($value)) {
                $this->flattenTranslations($value, $fullKey . '.', $translations);
            } else {
                $translations[$fullKey] = $value;
            }
        }
    }
    
    /**
     * Insert translations into database with proper conflict handling
     */
    protected function insertTranslationsToDatabase(string $language, array $translations, array $englishKeys): void
    {
        $batchSize = 100;
        $insertData = [];
        $migratedCount = 0;
        $duplicateCount = 0;
        
        foreach ($translations as $key => $value) {
            // Skip if already exists to avoid duplicates
            $exists = DB::table('translations')
                ->where('key', $key)
                ->where('language_code', $language)
                ->exists();
            
            if (!$exists) {
                $insertData[] = [
                    'key' => $key,
                    'language_code' => $language,
                    'value' => $value,
                    'description' => $this->generateDescription($key, $value),
                    'metadata' => json_encode([
                        'file_migrated' => true,
                        'migrated_at' => now()->toISOString(),
                        'source' => 'language_file'
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                if (count($insertData) >= $batchSize) {
                    try {
                        DB::table('translations')->insert($insertData);
                        $migratedCount += count($insertData);
                    } catch (\Exception $e) {
                        $duplicateCount += count($insertData);
                        // Log duplicate issue but continue
                        $this->command->warn("Found duplicates in batch for {$language}, skipping...");
                    }
                    $insertData = [];
                }
            } else {
                $duplicateCount++;
            }
        }
        
        // Insert remaining data
        if (!empty($insertData)) {
            try {
                DB::table('translations')->insert($insertData);
                $migratedCount += count($insertData);
            } catch (\Exception $e) {
                $duplicateCount += count($insertData);
                $this->command->warn("Found duplicates in final batch for {$language}, skipping...");
            }
        }
        
        // For English, ensure all keys have entries
        if ($language === 'en') {
            $this->ensureEnglishCompleteness($englishKeys);
        }
        
        $this->command->info("Migrated {$migratedCount} translations for {$language}" . ($duplicateCount > 0 ? " ({$duplicateCount} duplicates skipped)" : ""));
    }
    
    /**
     * Ensure English translations are complete
     */
    protected function ensureEnglishCompleteness(array $englishKeys): void
    {
        $existingKeys = DB::table('translations')
            ->where('language_code', 'en')
            ->pluck('key')
            ->toArray();
        
        $missingKeys = array_diff($englishKeys, $existingKeys);
        
        if (!empty($missingKeys)) {
            $insertData = [];
            $generatedCount = 0;
            
            foreach ($missingKeys as $key) {
                $insertData[] = [
                    'key' => $key,
                    'language_code' => 'en',
                    'value' => Str::title(str_replace(['.', '_'], ' ', Str::afterLast($key, '.'))),
                    'description' => $this->generateDescription($key, null),
                    'metadata' => json_encode([
                        'auto_generated' => true,
                        'generated_at' => now()->toISOString()
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            if (!empty($insertData)) {
                // Use insertOrIgnore to skip duplicates gracefully
                DB::table('translations')->insertOrIgnore($insertData);
                $this->command->info("Generated " . count($insertData) . " missing English translations");
            }
        }
    }
    
    /**
     * Generate description for translation key
     */
    protected function generateDescription(string $key, ?string $value): string
    {
        $parts = explode('.', $key);
        $category = $parts[0] ?? 'general';
        $description = "Translation for {$key}";
        
        if ($value && strlen($value) > 0) {
            $description .= " - Current value: " . Str::limit($value, 100);
        }
        
        return $description;
    }
}