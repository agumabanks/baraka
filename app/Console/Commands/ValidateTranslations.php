<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;

class ValidateTranslations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'translations:validate {--language= : Specific language to validate}';

    /**
     * The console command description.
     */
    protected $description = 'Validate translation quality and consistency across languages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting translation validation...');
        
        $language = $this->option('language');
        
        if ($language) {
            $this->validateLanguage($language);
        } else {
            $this->validateAllLanguages();
        }
        
        $this->info('✅ Translation validation completed!');
    }

    /**
     * Validate all languages
     */
    protected function validateAllLanguages()
    {
        $languages = $this->getLanguages();

        foreach ($languages as $language) {
            $this->validateLanguage($language);
        }
        
        $this->validateCrossLanguageConsistency();
    }

    /**
     * Validate a specific language
     */
    protected function validateLanguage($language)
    {
        $this->info("Validating language: {$language}");
        
        // Check translation completeness
        $this->checkTranslationCompleteness($language);
        
        // Check for missing translations
        $this->checkMissingTranslations($language);
        
        // Validate character encoding
        $this->checkCharacterEncoding($language);
        
        // Check for overly long translations
        $this->checkTranslationLengths($language);
        
        // Validate special characters
        $this->checkSpecialCharacters($language);
        
        // Check placeholders consistency
        $this->checkPlaceholdersConsistency($language);
    }

    /**
     * Check translation completeness
     */
    protected function checkTranslationCompleteness($language)
    {
        $totalTranslations = Translation::where('language_code', 'en')->count();
        $languageTranslations = Translation::where('language_code', $language)->count();
        $completedTranslations = Translation::where('language_code', $language)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->count();
        
        $completenessPercentage = $totalTranslations > 0 
            ? round(($completedTranslations / $totalTranslations) * 100, 2) 
            : 0;
        
        $this->line("  📊 Completeness: {$completenessPercentage}% ({$completedTranslations}/{$totalTranslations})");
        
        if ($completenessPercentage < 50) {
            $this->warn("  ⚠️  Low completion rate for {$language}");
        } elseif ($completenessPercentage >= 90) {
            $this->info("  ✅ Excellent completion rate for {$language}");
        }
    }

    /**
     * Check for missing translations
     */
    protected function checkMissingTranslations($language)
    {
        $englishKeys = Translation::where('language_code', 'en')->pluck('key')->toArray();
        $languageKeys = Translation::where('language_code', $language)->pluck('key')->toArray();
        
        $missingKeys = array_diff($englishKeys, $languageKeys);
        $extraKeys = array_diff($languageKeys, $englishKeys);
        
        if (!empty($missingKeys)) {
            $this->warn("  🚫 Missing translations: " . count($missingKeys));
            if (count($missingKeys) <= 10) {
                $this->line("     Missing keys: " . implode(', ', array_slice($missingKeys, 0, 10)));
            }
        }
        
        if (!empty($extraKeys)) {
            $this->warn("  ➕ Extra translations: " . count($extraKeys));
        }
    }

    /**
     * Check character encoding
     */
    protected function checkCharacterEncoding($language)
    {
        $invalidTranslations = Translation::where('language_code', $language)
            ->whereRaw("value REGEXP '[^[:print:]]'")
            ->get();
        
        if ($invalidTranslations->count() > 0) {
            $this->error("  ❌ Invalid character encoding: " . $invalidTranslations->count() . " translations");
        } else {
            $this->info("  ✅ Character encoding: Valid");
        }
    }

    /**
     * Check translation lengths
     */
    protected function checkTranslationLengths($language)
    {
        $tooLong = Translation::where('language_code', $language)
            ->whereRaw('LENGTH(value) > 255')
            ->get();
        
        $tooShort = Translation::where('language_code', $language)
            ->whereRaw('LENGTH(value) < 2')
            ->get();
        
        if ($tooLong->count() > 0) {
            $this->warn("  📏 Too long (>255 chars): " . $tooLong->count() . " translations");
        }
        
        if ($tooShort->count() > 0) {
            $this->warn("  📏 Too short (<2 chars): " . $tooShort->count() . " translations");
        }
        
        if ($tooLong->count() === 0 && $tooShort->count() === 0) {
            $this->info("  ✅ Translation lengths: Appropriate");
        }
    }

    /**
     * Check for special characters and formatting
     */
    protected function checkSpecialCharacters($language)
    {
        $translations = Translation::where('language_code', $language)->get();
        $issues = [];
        
        foreach ($translations as $translation) {
            $value = $translation->value;
            
            // Check for HTML tags
            if (preg_match('/<[^>]*>/', $value)) {
                $issues[] = $translation->key . ' (HTML tags)';
            }
            
            // Check for unbalanced quotes
            if (substr_count($value, '"') % 2 !== 0 || substr_count($value, "'") % 2 !== 0) {
                $issues[] = $translation->key . ' (Unbalanced quotes)';
            }
            
            // Check for excessive punctuation
            if (preg_match('/[!?]{3,}/', $value)) {
                $issues[] = $translation->key . ' (Excessive punctuation)';
            }
        }
        
        if (!empty($issues)) {
            $this->warn("  🔤 Special character issues: " . count($issues));
            if (count($issues) <= 5) {
                foreach (array_slice($issues, 0, 5) as $issue) {
                    $this->line("     - {$issue}");
                }
            }
        } else {
            $this->info("  ✅ Special characters: Clean");
        }
    }

    /**
     * Check placeholder consistency across languages
     */
    protected function checkPlaceholdersConsistency($language)
    {
        $englishTranslations = Translation::where('language_code', 'en')->get();
        $languageTranslations = Translation::where('language_code', $language)->get();
        
        $inconsistent = 0;
        
        foreach ($englishTranslations as $english) {
            $language = $languageTranslations->where('key', $english->key)->first();
            
            if ($language && $language->value) {
                // Extract placeholders from English
                $englishPlaceholders = $this->extractPlaceholders($english->value);
                $languagePlaceholders = $this->extractPlaceholders($language->value);
                
                if ($englishPlaceholders !== $languagePlaceholders) {
                    $inconsistent++;
                }
            }
        }
        
        if ($inconsistent > 0) {
            $this->warn("  🔄 Inconsistent placeholders: {$inconsistent} translations");
        } else {
            $this->info("  ✅ Placeholder consistency: Good");
        }
    }

    /**
     * Extract placeholders from translation text
     */
    protected function extractPlaceholders($text)
    {
        preg_match_all('/\{\{|\}\}|:[a-zA-Z_]+/', $text, $matches);
        return $matches[0];
    }

    /**
     * Check cross-language consistency
     */
    protected function validateCrossLanguageConsistency()
    {
        $this->info("\n🔄 Cross-language consistency check:");
        
        // Check for duplicate keys across different languages
        $languages = $this->getLanguages();

        $duplicates = DB::table('translations')
            ->select('key', DB::raw('GROUP_CONCAT(language_code) as languages'))
            ->whereIn('language_code', $languages)
            ->groupBy('key')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        
        if ($duplicates->count() > 0) {
            $this->error("  ❌ Duplicate keys across languages: " . $duplicates->count());
        } else {
            $this->info("  ✅ No duplicate keys found");
        }
        
        // Check translation coverage by key
        $languagesCount = count($languages);

        $keysWithAllLanguages = DB::table('translations')
            ->select('key')
            ->whereIn('language_code', $languages)
            ->groupBy('key')
            ->havingRaw('COUNT(DISTINCT language_code) = ?', [$languagesCount])
            ->count();
        
        $totalKeys = DB::table('translations')
            ->whereIn('language_code', $languages)
            ->distinct('key')
            ->count('key');
        
        $coveragePercentage = round(($keysWithAllLanguages / $totalKeys) * 100, 2);
        
        $this->line("  📈 Full coverage: {$coveragePercentage}% ({$keysWithAllLanguages}/{$totalKeys} keys)");
        
        if ($coveragePercentage >= 90) {
            $this->info("  ✅ Excellent translation coverage");
        } elseif ($coveragePercentage >= 70) {
            $this->line("  ℹ️  Good translation coverage");
        } else {
            $this->warn("  ⚠️  Low translation coverage");
        }
    }

    /**
     * Generate validation report
     */
    public function generateReport()
    {
        $this->info("\n📋 Generating validation report...");
        
        $languages = $this->getLanguages();
        $report = [];
        
        foreach ($languages as $language) {
            $stats = [
                'total' => Translation::where('language_code', $language)->count(),
                'completed' => Translation::where('language_code', $language)
                    ->whereNotNull('value')
                    ->where('value', '!=', '')
                    ->count(),
                'invalid_encoding' => Translation::where('language_code', $language)
                    ->whereRaw("value REGEXP '[^[:print:]]'")
                    ->count(),
                'too_long' => Translation::where('language_code', $language)
                    ->whereRaw('LENGTH(value) > 255')
                    ->count(),
                'too_short' => Translation::where('language_code', $language)
                    ->whereRaw('LENGTH(value) < 2')
                    ->count(),
            ];
            
            $stats['completion_percentage'] = $stats['total'] > 0 
                ? round(($stats['completed'] / $stats['total']) * 100, 2) 
                : 0;
            
            $report[$language] = $stats;
        }
        
        return $report;
    }

    private function getLanguages(): array
    {
        $languages = config('translations.supported', ['en', 'fr', 'sw']);
        array_unshift($languages, 'en');

        return array_values(array_unique($languages));
    }
}