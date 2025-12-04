<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportTranslations extends Command
{
    protected $signature = 'translations:import 
                            {--langs=en,fr,sw : Comma-separated list of languages to import}
                            {--fresh : Clear existing translations before import}
                            {--dry-run : Show what would be imported without making changes}';

    protected $description = 'Import translations from lang files into the database';

    protected int $imported = 0;
    protected int $skipped = 0;
    protected int $updated = 0;

    public function handle(): int
    {
        $languages = explode(',', $this->option('langs'));
        $fresh = $this->option('fresh');
        $dryRun = $this->option('dry-run');

        $this->info('🌐 Translation Import');
        $this->line('Languages: ' . implode(', ', $languages));
        
        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made');
        }

        if ($fresh && !$dryRun) {
            if ($this->confirm('This will delete ALL existing translations. Continue?')) {
                Translation::query()->delete();
                $this->info('Cleared existing translations');
            } else {
                return self::FAILURE;
            }
        }

        $langPath = base_path('lang');
        
        foreach ($languages as $lang) {
            $lang = trim($lang);
            $this->newLine();
            $this->info("📁 Processing: {$lang}");
            
            // Import PHP files
            $phpPath = "{$langPath}/{$lang}";
            if (File::isDirectory($phpPath)) {
                $this->importPhpFiles($phpPath, $lang, $dryRun);
            }
            
            // Import JSON file
            $jsonPath = "{$langPath}/{$lang}.json";
            if (File::exists($jsonPath)) {
                $this->importJsonFile($jsonPath, $lang, $dryRun);
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Imported (new)', $this->imported],
                ['Updated (existing)', $this->updated],
                ['Skipped (empty)', $this->skipped],
            ]
        );

        if (!$dryRun) {
            // Clear cache
            foreach ($languages as $lang) {
                if (function_exists('clear_translation_cache')) {
                    clear_translation_cache(trim($lang));
                }
            }
            $this->info('✅ Cache cleared');
        }

        return self::SUCCESS;
    }

    protected function importPhpFiles(string $path, string $lang, bool $dryRun): void
    {
        $files = File::files($path);
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group = $file->getFilenameWithoutExtension();
            
            try {
                $translations = include $file->getPathname();
            } catch (\Throwable $e) {
                $this->warn("    Skipped (error loading): {$e->getMessage()}");
                continue;
            }

            if (!is_array($translations)) {
                continue;
            }

            $this->line("  📄 {$group}.php");
            $this->processArray($translations, $group, $lang, $dryRun);
        }
    }

    protected function importJsonFile(string $path, string $lang, bool $dryRun): void
    {
        $content = File::get($path);
        $translations = json_decode($content, true);

        if (!is_array($translations)) {
            $this->error("  Invalid JSON in {$path}");
            return;
        }

        $this->line("  📄 {$lang}.json");
        
        foreach ($translations as $key => $value) {
            if (is_string($value) && trim($value) !== '') {
                $this->saveTranslation($key, $value, $lang, $dryRun);
            }
        }
    }

    protected function processArray(array $items, string $prefix, string $lang, bool $dryRun, string $path = ''): void
    {
        foreach ($items as $key => $value) {
            $fullKey = $path ? "{$prefix}.{$path}.{$key}" : "{$prefix}.{$key}";

            if (is_array($value)) {
                $newPath = $path ? "{$path}.{$key}" : $key;
                $this->processArray($value, $prefix, $lang, $dryRun, $newPath);
            } elseif (is_string($value) && trim($value) !== '') {
                $this->saveTranslation($fullKey, $value, $lang, $dryRun);
            } else {
                $this->skipped++;
            }
        }
    }

    protected function saveTranslation(string $key, string $value, string $lang, bool $dryRun): void
    {
        if ($dryRun) {
            $this->imported++;
            return;
        }

        $existing = Translation::where('key', $key)
            ->where('language_code', $lang)
            ->first();

        if ($existing) {
            if ($existing->value !== $value) {
                $existing->update(['value' => $value]);
                $this->updated++;
            } else {
                $this->skipped++;
            }
        } else {
            Translation::create([
                'key' => $key,
                'language_code' => $lang,
                'value' => $value,
            ]);
            $this->imported++;
        }
    }
}
