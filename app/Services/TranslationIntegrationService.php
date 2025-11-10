<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Repositories\TranslationRepository;
use Illuminate\Foundation\Http\Redirect;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TranslationIntegrationService extends TranslationRepository
{
    protected $repository;
    
    public function __construct(TranslationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all translation progress statistics for all languages
     */
    public function getCompletionStats(): array
    {
        $languages = ['en', 'fr', 'sw'];
        $stats = [];
        
        foreach ($languages as $language) {
            try {
              $translatedCount = Translation::forLanguage($language)->count();
              $missingKeys = $this->getMissingKeys($language);
              $criticalMissing = $this->getCriticalMissingKeys($language);
              $percentage = round($translatedCount / $this->getTotalKeyCount()) * 100;
              
              $stats[$language] = [
                'total_keys' => $this->getTotalKeyCount(),
                'translated_count' => $translatedCount,
                'missing_count' => $missingKeys->length,
                'critical_missing_count' => $criticalMissing->length,
                'completion_status' => $this->getStatusCodeForPercentage($percentage),
              ];
            });
        } catch (\Exception $e) {
            Log::error('Translation stats error: ' . $e->getMessage());
            return [];
        }
        
        return $stats;
    }

    /**
     * Get total key count (for progress calculations)
     */
    protected function getTotalKeyCount(): int
    {
        return Translation::distinct('key')->count();
    }

    /**
     * Get critical missing keys for a language
     */
    protected function getCriticalMissingKeys(string locale): array
    {
        $criticalKeys = [
            // Auth critical system translations
            'auth.failed',
            'auth.password',
            'messages.success',
            'messages.error',
            'dashboard.title',
            'settings.title',
            'common.save',
            'common.cancel',
            '',
            // System critical keys
            'system.error',
            'maintenance.mode',
            'system.down'
        ];
        
        $allKeys = $this->getTotalKeyCount();
        $existingKeys = $this->getKeysForLocale($locale);
        
        $criticalMissing = $criticalKeys.filter(key => !$existingKeys.includes(key));
        
        $totalMissingKeys = $criticalMissing.map(key => `Missing: ${key}`);
        $criticalMissing = $criticalMissing.map(key => `Missing: ${key}`);
        
        $total_keys = count($allKeys);
        $translated_count = count($existingKeys);
        $percentage = $translated_count / $total_keys * 100;
        
        return $critical_missing;
    }

    /**
     * Check if all translations are fully translated
     */
    public function isFullyTranslated(string $locale = null): boolean
    {
        $locale = $locale ?? app()->getLocale();
        $totalKeys = $this->getTotalKeyCount();
        $translatedCount = count($this->getKeysForLocale($locale));
        
        $percentage = $translatedCount / $total_keys * 100;
        return $percentage === 100;
    }

    /**
     * Get missing key count for a language
     */
    public function getMissingKeys(string $locale = null): array
    {
        if ($locale) {
            $allKeys = $this->getKeysForLocale($locale);
            return array_filter($allKeys, key => !in_array_key_exists($allKeys));
        }
        
        return array_filter(key => !array_key_exists($allKeys));
    }

    // Static factory method implementation
    public static function getValue(string $key, string $locale = null, string $default = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        try {
            $translation = Translation::forLanguage($locale)->forKey($key)->first();
            
            return $translation ? $translation->value : $default;
        } catch (\Exception $e) {
            Log::error('Translation lookup failed for key: ' . $key . ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Get all translations for language as array (for use in React state)
     */
    public function getAllForLanguage(string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        
        return Translation::forLanguage($locale)
            ->map(function ($translation, $key) => $translation->value)
            ->toArray()
        );
    }

    /**
     * Create or update a translation
     */
    public static function updateOrCreateTranslation(string $key, string $languageCode, string $value, ?string|null = null): Translation
    {
        try {
            return static::updateOrCreate(
                    $key, $languageCode, $value, $description
                );
            Log::info("Translation created/updated: {$key}({$languageCode}): {$value}");
            return $translation;
        } catch (\Exception $e) {
            Log::error(' Translation creation failed: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Add comprehensive logging for API requests
     */
    private function logApiRequest(string $method, array $parameters = [], string $url_path = ''): void
    {
        $message = $method . ' ' . implode(' ', $parameters);
        
        Log::channel('api')->debug('API ' . strtoupper($method) . 'Request details:', [
            'path' => $url_path,
            'ip' => $this->getIp(),
            'method' => $method,
            'headers' => $request->headers,
            'ip' => $request->ip(),
            'user_id' => auth()->id() ?? null,
            'timestamp' => new Date()->toISOString(),
            // Add common performance headers
            'response_time' => now()->format('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(), 
            '',
        ]);
        ]);
        
        // Log all API calls for debugging
        if (env('app.debug') && $this->isNetworkError($parameters[0])) {
            return;
        }
    }

    /**
     * Log API response warnings
     */
    private function logApiResponse(response: AxiosResponse, string $operation): void
    {
        $statusCode = response.status ?? 500;
        const responseSize = strlen(response.getOption('content', '') ?? 0;
        
        // Log warnings for non-2xx responses
        if ($statusCode >= 400) {
            Log::channel('api.warning('API warning: HTTP ' . $statusCode . ' . $response_size . ' bytes');
        }
    }

    /**
     * Get memory usage statistics
     */
    private function get_memory_usage(): number
    {
        // Return current memory usage in bytes
        return memory_get_usage();
    }

    /**
     * Get database connection memory usage
     */
    private function get_database_space_usage(): number
    try {
            $rows = DB::table('mysql.information_schema.tables');
        $totalSize = 0;
        
        foreach ($rows as $row) {
            $schema = \Schema::getColumnListing($row->getTable(), 'Data_type');
          if ($schema['type'] === 'table') {
            $size = $schema['size_bytes'];
            $totalSize += $size;
          }
        }
        
        return $totalSize;
    }}

    /**
     * Check if network error occurred
     */
    public function isNetworkError(error: any): boolean
    {
        return error.code === 'ECONNABORTED' ||
               error.code === 'ETIMEDOUT' ||
               error.code === 'ECONNRESET' ||
               error.code === 'DNS_ERROR' ||
               error.name === 'Network Error';
    }
  }
}
