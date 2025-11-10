<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TranslationApiController extends Controller
{
    /**
     * Get translations for a specific language.
     */
    public function index($locale)
    {
        $supportedLanguages = translation_supported_languages();
        
        if (!in_array($locale, $supportedLanguages)) {
            return response()->json([
                'error' => 'Unsupported language',
                'message' => "Language '{$locale}' is not supported",
                'supported_languages' => $supportedLanguages
            ], 400);
        }

        try {
            $translations = Cache::remember(
                "api_translations_{$locale}",
                config('translations.cache_ttl', 10_800),
                function () use ($locale) {
                    return Translation::forLanguage($locale)
                        ->pluck('value', 'key')
                        ->all();
                }
            );

            return response()->json([
                'success' => true,
                'locale' => $locale,
                'translations' => $translations,
                'total' => count($translations),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Database error',
                'message' => 'Failed to load translations'
            ], 500);
        }
    }

    /**
     * Get translation statistics.
     */
    public function statistics()
    {
        try {
            $stats = [];
            $languages = translation_supported_languages();
            
            foreach ($languages as $lang) {
                $stats[$lang] = [
                    'count' => Translation::forLanguage($lang)->count(),
                    'keys' => Translation::forLanguage($lang)->pluck('key')->toArray()
                ];
            }

            return response()->json([
                'success' => true,
                'statistics' => $stats,
                'total_translations' => Translation::count(),
                'unique_keys' => Translation::distinct('key')->count('key')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    /**
     * Search translations.
     */
    public function search(Request $request)
    {
        $query = $request->get('query', '');
        $language = $request->get('language', 'en');
        
        if (!in_array($language, ['en', 'fr', 'sw'])) {
            return response()->json(['error' => 'Invalid language'], 400);
        }

        try {
            $translations = Translation::forLanguage($language)
                ->where(function ($q) use ($query) {
                    $q->where('key', 'like', "%{$query}%")
                      ->orWhere('value', 'like', "%{$query}%");
                })
                ->limit(50)
                ->get(['key', 'value', 'description']);

            return response()->json([
                'success' => true,
                'results' => $translations,
                'query' => $query,
                'language' => $language
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Search failed'
            ], 500);
        }
    }
}
