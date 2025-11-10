<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\TranslationRepository;
use App\Repositories\TranslationRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    protected TranslationRepository $repo;
    protected array $supportedLanguages = ['en', 'fr', 'sw'];

    public function __construct(TranslationRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Display a listing of translations.
     */
    public function index(Request $request)
    {
        $languageCode = $request->get('language_code', 'en');
        $key = $request->get('key', '');
        
        $filters = [
            'language_code' => $languageCode !== 'all' ? $languageCode : null,
            'key' => $key ?: null,
        ];
        
        $translations = $this->repo->all($filters);
        $languages = $this->repo->getLanguageCodes();
        $keys = array_slice($this->repo->getKeys(), 0, 100); // Limit for performance
        
        return view('backend.translations.index', compact(
            'translations',
            'languages',
            'keys',
            'languageCode',
            'key'
        ));
    }

    /**
     * Show the form for creating a new translation.
     */
    public function create()
    {
        $languages = $this->supportedLanguages;
        $keys = array_slice($this->repo->getKeys(), 0, 50); // Show some examples
        
        return view('backend.translations.create', compact('languages', 'keys'));
    }

    /**
     * Store a newly created translation.
     */
    public function store(Request $request)
    {
        if (env('DEMO')) {
            Toastr::error('Create operation is disable for the demo mode.', 'Error');
            return redirect()->back();
        }

        $request->validate([
            'key' => 'required|string|max:255',
            'language_code' => 'required|string|in:' . implode(',', $this->supportedLanguages),
            'value' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $existing = $this->repo->findByKeyAndLanguage(
            $request->key,
            $request->language_code
        );

        if ($existing) {
            Toastr::error('Translation already exists for this key and language.', 'Error');
            return redirect()->back()->withInput();
        }

        $translation = $this->repo->updateOrCreate(
            [
                'key' => $request->key,
                'language_code' => $request->language_code,
            ],
            [
                'value' => $request->value,
                'description' => $request->description,
                'metadata' => $request->metadata ?? null,
            ]
        );

        clear_translation_cache($request->language_code);

        Toastr::success('Translation created successfully.', 'Success');
        return redirect()->route('translations.index');
    }

    /**
     * Show the form for editing the specified translation.
     */
    public function edit(int $id)
    {
        $translation = $this->repo->find($id);
        
        if (!$translation) {
            Toastr::error('Translation not found.', 'Error');
            return redirect()->route('translations.index');
        }

        $languages = $this->supportedLanguages;
        $keys = $this->repo->getKeys();
        
        return view('backend.translations.edit', compact('translation', 'languages', 'keys'));
    }

    /**
     * Update the specified translation.
     */
    public function update(Request $request, int $id)
    {
        if (env('DEMO')) {
            Toastr::error('Update operation is disable for the demo mode.', 'Error');
            return redirect()->back();
        }

        $translation = $this->repo->find($id);
        
        if (!$translation) {
            Toastr::error('Translation not found.', 'Error');
            return redirect()->route('translations.index');
        }

        $request->validate([
            'key' => 'required|string|max:255',
            'language_code' => 'required|string|in:' . implode(',', $this->supportedLanguages),
            'value' => 'required|string',
            'description' => 'nullable|string',
        ]);

        // Check for duplicates (excluding current record)
        $existing = $this->repo->findByKeyAndLanguage(
            $request->key,
            $request->language_code
        );

        if ($existing && $existing->id !== $id) {
            Toastr::error('Translation already exists for this key and language.', 'Error');
            return redirect()->back()->withInput();
        }

        $updated = $this->repo->update($id, [
            'key' => $request->key,
            'language_code' => $request->language_code,
            'value' => $request->value,
            'description' => $request->description,
            'metadata' => $request->metadata ?? null,
        ]);

        if ($updated) {
            clear_translation_cache($translation->language_code);
            if ($translation->language_code !== $request->language_code) {
                clear_translation_cache($request->language_code);
            }
            Toastr::success('Translation updated successfully.', 'Success');
        } else {
            Toastr::error('Failed to update translation.', 'Error');
        }

        return redirect()->route('translations.index');
    }

    /**
     * Remove the specified translation.
     */
    public function destroy(int $id)
    {
        if (env('DEMO')) {
            Toastr::error('Delete operation is disable for the demo mode.', 'Error');
            return redirect()->back();
        }

        $translation = $this->repo->find($id);
        
        if (!$translation) {
            Toastr::error('Translation not found.', 'Error');
            return redirect()->route('translations.index');
        }

        $deleted = $this->repo->delete($id);

        if ($deleted) {
            clear_translation_cache($translation->language_code);
            Toastr::success('Translation deleted successfully.', 'Success');
        } else {
            Toastr::error('Failed to delete translation.', 'Error');
        }

        return redirect()->route('translations.index');
    }

    /**
     * Bulk import translations.
     */
    public function import(Request $request)
    {
        if (env('DEMO')) {
            Toastr::error('Import operation is disable for the demo mode.', 'Error');
            return redirect()->back();
        }

        $request->validate([
            'translations' => 'required|array',
            'translations.*.key' => 'required|string|max:255',
            'translations.*.language_code' => 'required|string|in:' . implode(',', $this->supportedLanguages),
            'translations.*.value' => 'required|string',
            'translations.*.description' => 'nullable|string',
        ]);

        try {
            $count = $this->repo->bulkImport($request->translations);
            $locales = collect($request->translations)->pluck('language_code')->unique()->all();
            foreach ($locales as $locale) {
                clear_translation_cache($locale);
            }
            Toastr::success("Successfully imported {$count} translations.", 'Success');
        } catch (\Exception $e) {
            Toastr::error('Failed to import translations: ' . $e->getMessage(), 'Error');
        }

        return redirect()->route('translations.index');
    }

    /**
     * Export translations for a specific language.
     */
    public function export(Request $request)
    {
        $languageCode = $request->get('language_code', 'en');
        
        if (!in_array($languageCode, $this->supportedLanguages)) {
            Toastr::error('Invalid language code.', 'Error');
            return redirect()->back();
        }

        $translations = $this->repo->getTranslationsForLanguage($languageCode);
        
        $filename = "translations_{$languageCode}_" . date('Y-m-d_His') . '.json';
        
        return response()->json($translations)
            ->header('Content-Disposition', "attachment; filename={$filename}")
            ->header('Content-Type', 'application/json');
    }

    /**
     * Get all translations for a specific language (AJAX endpoint).
     */
    public function getByLanguage(Request $request)
    {
        $languageCode = $request->get('language_code', 'en');
        
        if (!in_array($languageCode, $this->supportedLanguages)) {
            return response()->json(['error' => 'Invalid language code'], 400);
        }

        $translations = $this->repo->getTranslationsForLanguage($languageCode);
        
        return response()->json($translations);
    }
}
