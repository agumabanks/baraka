<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\TranslationRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    protected TranslationRepositoryInterface $translationRepo;
    protected array $supportedLanguages;

    public function __construct(TranslationRepositoryInterface $translationRepo)
    {
        $this->translationRepo = $translationRepo;
        $this->supportedLanguages = translation_supported_languages();
    }

    /**
     * Switch language for the current session.
     */
    public function switch(Request $request)
    {
        $languageCode = $request->get('language_code', 'en');

        if (!in_array($languageCode, $this->supportedLanguages)) {
            Toastr::error('Invalid language code.', 'Error');
            return redirect()->back();
        }

        Session::put('locale', $languageCode);
        app()->setLocale($languageCode);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => trans_db('settings.language_switched'),
                'locale' => $languageCode,
            ]);
        }

        Toastr::success('Language changed successfully.', 'Success');
        return redirect()->back();
    }

    /**
     * Set default language for the application.
     */
    public function setDefault(Request $request)
    {
        if (env('DEMO')) {
            Toastr::error('Update operation is disable for the demo mode.', 'Error');
            return redirect()->back();
        }

        $languageCode = $request->get('default_language', 'en');

        if (!in_array($languageCode, $this->supportedLanguages)) {
            Toastr::error('Invalid language code.', 'Error');
            return redirect()->back();
        }

        // Update the GeneralSettings
        $settingsRepo = app(\App\Repositories\GeneralSettings\GeneralSettingsInterface::class);
        $settingsRepo->update(['default_language' => $languageCode]);

        // Clear settings cache
        Cache::forget('settings');
        
        // Also update session locale
        Session::put('locale', $languageCode);
        app()->setLocale($languageCode);

        Toastr::success('Default language updated successfully.', 'Success');
        return redirect()->back();
    }

    /**
     * Get supported languages with display names.
     */
    public function getSupportedLanguages()
    {
        $languageMeta = [
            'en' => ['name' => 'English', 'flag' => 'us'],
            'fr' => ['name' => 'Français', 'flag' => 'fr'],
            'sw' => ['name' => 'Kiswahili', 'flag' => 'tz'],
        ];

        $data = [];
        foreach ($this->supportedLanguages as $code) {
            if (isset($languageMeta[$code])) {
                $data[$code] = $languageMeta[$code];
            }
        }

        return response()->json([
            'success' => true,
            'languages' => $data,
        ]);
    }

    /**
     * Get current language information.
     */
    public function getCurrentLanguage()
    {
        $currentLanguage = app()->getLocale();
        $defaultLanguage = config('app.locale', 'en');
        
        return response()->json([
            'current' => $currentLanguage,
            'default' => $defaultLanguage,
            'supported' => $this->supportedLanguages,
        ]);
    }

    /**
     * Sync translations from language files to database.
     */
    public function syncFromFiles(Request $request)
    {
        if (env('DEMO')) {
            Toastr::error('Sync operation is disable for the demo mode.', 'Error');
            return redirect()->back();
        }

        $languageCode = $request->get('language_code', 'en');
        
        if (!in_array($languageCode, $this->supportedLanguages)) {
            Toastr::error('Invalid language code.', 'Error');
            return redirect()->back();
        }

        try {
            $path = lang_path($languageCode);
            $files = glob($path . '/*.php');
            $syncedCount = 0;

            foreach ($files as $file) {
                $filename = basename($file, '.php');
                $translationsArray = include $file;
                
                foreach ($translationsArray as $key => $value) {
                    if (is_string($value)) {
                        $fullKey = $filename . '.' . $key;
                        
                        $this->translationRepo->updateOrCreate(
                            ['key' => $fullKey, 'language_code' => $languageCode],
                            ['value' => $value]
                        );
                        
                        $syncedCount++;
                    } elseif (is_array($value)) {
                        // Handle nested arrays
                        foreach ($value as $nestedKey => $nestedValue) {
                            if (is_string($nestedValue)) {
                                $fullKey = $filename . '.' . $key . '.' . $nestedKey;
                                
                                $this->translationRepo->updateOrCreate(
                                    ['key' => $fullKey, 'language_code' => $languageCode],
                                    ['value' => $nestedValue]
                                );
                                
                                $syncedCount++;
                            }
                        }
                    }
                }
            }

            clear_translation_cache($languageCode);

            Toastr::success("Successfully synced {$syncedCount} translations from language files.", 'Success');
        } catch (\Exception $e) {
            Toastr::error('Failed to sync translations: ' . $e->getMessage(), 'Error');
        }

        return redirect()->back();
    }
}
