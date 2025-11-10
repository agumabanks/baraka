<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class TranslationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
            'role_id' => 1, // Admin role
        ]);
    }

    /** @test */
    public function it_can_create_a_translation()
    {
        $translationData = [
            'key' => 'test.new_key',
            'language_code' => 'en',
            'value' => 'Test Value',
            'description' => 'Test description',
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('translations.store'), $translationData);

        $response->assertRedirect(route('translations.index'));
        
        $this->assertDatabaseHas('translations', $translationData);
    }

    /** @test */
    public function it_cannot_create_duplicate_translation()
    {
        $existingTranslation = Translation::factory()->create([
            'key' => 'test.existing_key',
            'language_code' => 'en',
            'value' => 'Existing Value',
        ]);

        $translationData = [
            'key' => 'test.existing_key',
            'language_code' => 'en',
            'value' => 'New Value',
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('translations.store'), $translationData);

        $response->assertSessionHasErrors();
        $this->assertEquals(1, Translation::where('key', 'test.existing_key')
            ->where('language_code', 'en')->count());
    }

    /** @test */
    public function it_can_update_a_translation()
    {
        $translation = Translation::factory()->create([
            'key' => 'test.update_key',
            'language_code' => 'en',
            'value' => 'Original Value',
        ]);

        $updateData = [
            'key' => 'test.updated_key',
            'language_code' => 'fr',
            'value' => 'Updated Value',
            'description' => 'Updated description',
        ];

        $response = $this
            ->actingAs($this->user)
            ->put(route('translations.update', $translation->id), $updateData);

        $response->assertRedirect(route('translations.index'));
        
        $this->assertDatabaseHas('translations', array_merge(['id' => $translation->id], $updateData));
    }

    /** @test */
    public function it_can_delete_a_translation()
    {
        $translation = Translation::factory()->create();

        $response = $this
            ->actingAs($this->user)
            ->delete(route('translations.destroy', $translation->id));

        $response->assertRedirect(route('translations.index'));
        
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    /** @test */
    public function it_can_get_translations_by_language()
    {
        Translation::factory()->create([
            'key' => 'test.key1',
            'language_code' => 'en',
            'value' => 'English Value 1',
        ]);
        
        Translation::factory()->create([
            'key' => 'test.key2',
            'language_code' => 'en',
            'value' => 'English Value 2',
        ]);
        
        Translation::factory()->create([
            'key' => 'test.key3',
            'language_code' => 'fr',
            'value' => 'French Value',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('translations.getByLanguage', ['language_code' => 'en']));

        $response->assertStatus(200);
        $response->assertJson([
            'test.key1' => 'English Value 1',
            'test.key2' => 'English Value 2',
        ]);
        $response->assertJsonMissing(['test.key3']);
    }

    /** @test */
    public function it_can_switch_language()
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('language.switch'), [
                'language_code' => 'fr',
            ]);

        $response->assertRedirect();
        $this->assertEquals('fr', Session::get('locale'));
    }

    /** @test */
    public function it_respects_language_permissions()
    {
        $regularUser = User::factory()->create(['role_id' => 2]); // Regular user role

        // Try to access translations without permission
        Translation::factory()->create();

        $response = $this
            ->actingAs($regularUser)
            ->get(route('translations.index'));

        // This should likely be forbidden or not found due to middleware
        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_export_translations()
    {
        Translation::factory()->create([
            'key' => 'test.export_key',
            'language_code' => 'en',
            'value' => 'Export Value',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('translations.export', ['language_code' => 'en']));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $response->assertJson(['test.export_key' => 'Export Value']);
    }

    /** @test */
    public function translation_helper_function_works()
    {
        Translation::factory()->create([
            'key' => 'test.helper_key',
            'language_code' => 'en',
            'value' => 'Helper Value',
        ]);

        // Test the helper function
        $this->app->setLocale('en');
        $value = trans_db('test.helper_key');
        $this->assertEquals('Helper Value', $value);
        
        // Test fallback to default
        $fallbackValue = trans_db('test.nonexistent_key', [], 'en', 'Default Value');
        $this->assertEquals('Default Value', $fallbackValue);
    }

    /** @test */
    public function it_can_handle_replacements_in_translations()
    {
        Translation::factory()->create([
            'key' => 'test.replacement_key',
            'language_code' => 'en',
            'value' => 'Hello :name, welcome!',
        ]);

        $value = trans_db('test.replacement_key', ['name' => 'John']);
        $this->assertEquals('Hello John, welcome!', $value);
    }

    /** @test */
    public function it_caches_translations_efficiently()
    {
        $translation = Translation::factory()->create([
            'key' => 'test.cache_key',
            'language_code' => 'en',
            'value' => 'Cached Value',
        ]);

        // First call should cache the result
        $this->assertFalse(Cache::has('translations_en_test'));
        
        $value1 = get_translation_cache('en');
        
        $this->assertTrue(Cache::has('translations_array_en'));
        $this->assertEquals('Cached Value', $value1['test.cache_key']);
        
        // Second call should use cache
        $value2 = get_translation_cache('en');
        $this->assertEquals($value1, $value2);
    }

    /** @test */
    public function language_selection_persists_in_session()
    {
        $this->actingAs($this->user);
        
        // Set language to French
        $response = $this->post(route('language.switch'), [
            'language_code' => 'fr',
        ]);
        
        // Check session contains the language setting
        $this->assertEquals('fr', Session::get('locale'));
        
        // Check the app locale is updated
        $this->assertEquals('fr', app()->getLocale());
        
        // Navigate to another page and verify language persists
        $this->get(route('dashboard.index'));
        $this->assertEquals('fr', Session::get('locale'));
        $this->assertEquals('fr', app()->getLocale());
    }

    /** @test */
    public function it_renders_correct_translations_for_each_language()
    {
        // Create translations in multiple languages
        Translation::factory()->create([
            'key' => 'dashboard.title',
            'language_code' => 'en',
            'value' => 'Dashboard',
        ]);
        
        Translation::factory()->create([
            'key' => 'dashboard.title',
            'language_code' => 'fr',
            'value' => 'Tableau de bord',
        ]);
        
        Translation::factory()->create([
            'key' => 'dashboard.title',
            'language_code' => 'sw',
            'value' => 'Bodi ya dashibodi',
        ]);

        // Test each language
        $languages = ['en', 'fr', 'sw'];
        $expectedValues = [
            'en' => 'Dashboard',
            'fr' => 'Tableau de bord',
            'sw' => 'Bodi ya dashibordi',
        ];

        foreach ($languages as $lang) {
            $this->app->setLocale($lang);
            $value = trans_db('dashboard.title');
            $this->assertEquals($expectedValues[$lang], $value, 
                "Translation for dashboard.title in {$lang} should be {$expectedValues[$lang]}");
        }
    }

    /** @test */
    public function it_falls_back_to_english_for_missing_translations()
    {
        // Create only English translation
        Translation::factory()->create([
            'key' => 'test.fallback_key',
            'language_code' => 'en',
            'value' => 'English Value',
        ]);

        // Try to get French translation (should fallback to English)
        $this->app->setLocale('fr');
        $value = trans_db('test.fallback_key');
        $this->assertEquals('English Value', $value);
    }

    /** @test */
    public function it_can_bulk_import_translations()
    {
        $translations = [
            [
                'key' => 'test.bulk1',
                'language_code' => 'en',
                'value' => 'Bulk Value 1',
                'description' => 'First bulk import',
            ],
            [
                'key' => 'test.bulk2',
                'language_code' => 'en',
                'value' => 'Bulk Value 2',
                'description' => 'Second bulk import',
            ],
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('translations.import'), ['translations' => $translations]);

        $response->assertRedirect(route('translations.index'));
        
        foreach ($translations as $translation) {
            $this->assertDatabaseHas('translations', [
                'key' => $translation['key'],
                'language_code' => $translation['language_code'],
                'value' => $translation['value'],
            ]);
        }
    }
}
