# Database-Driven Translation Integration Guide

## 🚀 System-Wide Integration Strategy

This guide shows how to integrate the database-driven translation system throughout your entire application.

## 1. 📱 Frontend Integration

### Blade Templates
```php
{{-- Replace standard translations --}}
{{-- Before --}}
{{ __('dashboard.title') }}

{{-- After --}}
{{ trans_db('dashboard.title') }}
{{ __db('dashboard.title') }} {{-- Shorthand --}}
{{ $trans['dashboard.title'] }} {{-- Using shared variable --}}
<x-translator key="dashboard.title" /> {{-- Component --}}
```

### JavaScript Integration
```html
<!-- Include the translation system -->
<script src="{{ asset('js/db-translations.js') }}"></script>

<!-- Use data-trans attributes for automatic translation -->
<button data-trans="common.save">Save</button>
<span data-trans-title="common.edit">Edit</span>
<input data-trans-placeholder="common.search" placeholder="Search">
```

```javascript
// Manual translation calls
const welcomeText = window.t('dashboard.title');
const greeting = window.trans('welcome.message', { name: 'John' });

// Event listeners for language changes
window.addEventListener('languageChanged', (e) => {
    console.log('Language switched to:', e.detail.locale);
    // Re-render translated content
    window.dbTranslations.applyTranslations();
});
```

### Language Switcher Component
```html
<div class="language-switcher">
    <select class="form-control" onchange="window.dbTranslations.switchLanguage(this.value)">
        <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>English</option>
        <option value="fr" {{ app()->getLocale() == 'fr' ? 'selected' : '' }}>Français</option>
        <option value="sw" {{ app()->getLocale() == 'sw' ? 'selected' : '' }}>Kiswahili</option>
    </select>
</div>
```

## 2. ⚛️ React Dashboard Integration

### Hook Usage
```tsx
// In your React components
import { useDbTranslations } from '../hooks/useDbTranslations';

function Dashboard() {
  const { translate, currentLocale, switchLanguage, Translate } = useDbTranslations();

  return (
    <div>
      <h1>{translate('dashboard.title')}</h1>
      <p>{translate('dashboard.total', { count: 42 })}</p>
      
      <button onClick={() => switchLanguage('fr')}>
        Switch to French
      </button>
      
      {/* Component version */}
      <Translate key="common.save">Save</Translate>
    </div>
  );
}
```

### Global Provider Setup
```tsx
// App.tsx or root component
import { TranslationProvider } from './contexts/TranslationContext';

function App() {
  return (
    <TranslationProvider>
      <Router>
        {/* Your routes */}
      </Router>
    </TranslationProvider>
  );
}
```

## 3. 📡 API Integration

### Frontend JavaScript
```javascript
class TranslationAPI {
  static async loadTranslations(locale) {
    const response = await fetch(`/api/v1/translations/${locale}`);
    const data = await response.json();
    return data.translations;
  }
  
  static async getStatistics() {
    const response = await fetch('/api/v1/translations/statistics');
    return await response.json();
  }
  
  static searchTranslations(query, language) {
    return fetch(`/api/v1/translations/search?query=${query}&language=${language}`);
  }
}
```

### Vue.js Integration
```javascript
// main.js
import { createApp } from 'vue';
import translations from './plugins/translations';

const app = createApp(App);
app.use(translations);

// plugins/translations.js
import { definePlugin } from 'vue';

export default definePlugin({
  install(app) {
    app.provide('translations', new TranslationManager());
    
    app.config.globalProperties.$t = function(key, replacements = {}) {
      return this.$translations.translate(key, replacements);
    };
  }
});
```

## 4. 🔧 Service Layer Integration

### In Controllers
```php
<?php

namespace App\Http\Controllers\Backend;

class DashboardController extends Controller
{
    public function index()
    {
        $translations = \App\Models\Translation::getAllForLanguage(app()->getLocale());
        
        return view('backend.dashboard', [
            'pageTitle' => trans_db('dashboard.title'),
            'translations' => $translations
        ]);
    }
    
    public function reports()
    {
        return response()->json([
            'success' => true,
            'title' => trans_db('reports.title'),
            'message' => trans_db('messages.loaded_successfully'),
            'data' => $this->getReportData()
        ]);
    }
}
```

### Service Classes
```php
<?php

namespace App\Services;

class NotificationService
{
    public function sendWelcomeEmail($user)
    {
        $subject = trans_db('email.welcome_subject', ['name' => $user->name]);
        $body = trans_db('email.welcome_body', [
            'name' => $user->name,
            'company' => settings()->name
        ]);
        
        return $this->sendEmail($user->email, $subject, $body);
    }
    
    public function getSuccessMessage($action)
    {
        return trans_db("messages.{$action}_success");
    }
}
```

### Validation Messages
```php
<?php

class CreateShipmentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'customer_name' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0.1'
        ];
    }
    
    public function messages()
    {
        return [
            'customer_name.required' => trans_db('validation.customer_name_required'),
            'weight.required' => trans_db('validation.weight_required'),
            'weight.min' => trans_db('validation.weight_min', ['min' => 0.1])
        ];
    }
}
```

## 5. 🗄️ Database Integration

### Model Methods
```php
<?php

namespace App\Models;

class Shipment extends Model
{
    public function getStatusLabel()
    {
        return trans_db("shipment_status.{$this->status}");
    }
    
    public function getFormattedTrackingId()
    {
        return trans_db('shipment.tracking_format', ['id' => $this->tracking_id]);
    }
    
    protected function casts(): array
    {
        return [
            'status_label' => 'string',
            'formatted_tracking' => 'string',
        ];
    }
    
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'status_label' => $this->getStatusLabel(),
            'formatted_tracking' => $this->getFormattedTrackingId(),
        ]);
    }
}
```

### Factory Classes
```php
<?php

class ShipmentFactory extends Factory
{
    public function definition()
    {
        return [
            'customer_name' => $this->faker->name(),
            'status' => $this->faker->randomElement(['pending', 'shipped', 'delivered']),
            'notes' => trans_db('shipment.auto_generated_note'),
            'created_at' => now(),
        ];
    }
}
```

## 6. 🔄 Migration Strategy

### Phase 1: Critical Pages (Week 1)
```bash
# Replace translations in high-traffic pages
# Dashboard, Settings, Auth pages
```

```php
// Priority order
$priorityPages = [
    'dashboard.blade.php',
    'general_settings/index.blade.php',
    'auth/login.blade.php',
    'partials/*.blade.php'
];
```

### Phase 2: Core Features (Week 2-3)
```bash
# Business logic pages
# Shipments, Reports, Users Management
```

### Phase 3: Remaining Pages (Week 4)
```bash
# All remaining pages
# Email templates
# API responses
```

## 7. 📊 Monitoring & Maintenance

### Automated Health Check
```php
class TranslationHealthCheck
{
    public function checkCompletions()
    {
        $service = app(TranslationIntegrationService::class);
        $stats = $service->getCompletionStats();
        
        if ($stats['overall']['average_completion'] < 80) {
            Log::warning('Translation completion below 80%', $stats);
        }
        
        return $stats;
    }
    
    public function findCriticalMissingTranslations()
    {
        $service = app(TranslationIntegrationService::class);
        $missing = [];
        
        foreach (['en', 'fr', 'sw'] as $lang) {
            $missing[$lang] = $service->getMissingTranslations($lang);
        }
        
        return $missing;
    }
}
```

### Caching Strategy
```php
// config/cache.php
return [
    'stores' => [
        'translations' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => 'translations_',
        ],
    ]
];
```

### Performance Monitoring
```php
class TranslationMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        if ($duration > 100) { // 100ms threshold
            Log::warning('Slow translation loading', [
                'duration' => $duration,
                'locale' => app()->getLocale(),
                'url' => $request->url()
            ]);
        }
        
        return $response;
    }
}
```

## 8. 🛡️ Security & Permissions

### Role-Based Access
```php
// Add these permissions to your role management system
$translationPermissions = [
    'translation_read' => 'View translations',
    'translation_create' => 'Create translations', 
    'translation_edit' => 'Edit translations',
    'translation_delete' => 'Delete translations',
    'translation_export' => 'Export translations',
    'language_switch' => 'Switch language',
    'language_set_default' => 'Set default language',
    'language_sync' => 'Sync from files'
];
```

### Content Security
```php
class TranslationPolicy
{
    public function update(User $user, Translation $translation)
    {
        // Only allow editors to modify language file translations
        if ($user->hasRole('editor')) {
            return true;
        }
        
        // Content creators can only modify custom translations
        return $translation->key !== trans_db('system.protected_key');
    }
    
    public function delete(User $user, Translation $translation)
    {
        // Prevent deletion of critical system translations
        $criticalKeys = ['dashboard.title', 'common.save', 'auth.failed'];
        
        return !in_array($translation->key, $criticalKeys) && 
               $user->hasPermission('translation_delete');
    }
}
```

## 9. 🌍 Multi-Language Deployment

### Environment Configuration
```bash
# .env
# Supported languages
APP_SUPPORTED_LOCALES=en,fr,sw

# Default locale
DEFAULT_LOCALE=en

# Translation cache
TRANSLATION_CACHE_TTL=3600

# Auto-sync from files on deployment
AUTO_SYNC_TRANSLATIONS=true
```

### Deployment Script
```bash
#!/bin/bash
# deploy.sh

echo "🌍 Deploying translation system..."

# Step 1: Run migrations
php artisan migrate --force

# Step 2: Seed translations
php artisan db:seed --class=TranslationsSeeder --force

# Step 3: Sync from language files
php artisan tinker --execute="
    app(\App\Services\TranslationIntegrationService::class)->migrateFromFiles();
"

# Step 4: Optimize cache
php artisan translations:optimize

# Step 5: Verify translations
php artisan translations:check

echo "✅ Translation system deployed successfully!"
```

## 10. 📱 Mobile App Integration

### Flutter Example
```dart
class TranslationService {
  static Future<Map<String, String>> loadTranslations(String locale) async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/api/v1/translations/$locale')
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return Map<String, String>.from(data['translations']);
    }
    
    return {};
  }
  
  static String translate(String key, Map<String, String> replacements) {
    String translation = _translations[key] ?? key;
    
    replacements.forEach((placeholder, value) {
      translation = translation.replaceAll(':$placeholder', value);
    });
    
    return translation;
  }
}
```

## 11. 🔍 Testing Integration

### Feature Tests
```php
class TranslationIntegrationTest extends TestCase
{
    /** @test */
    public function it_translates_all_ui_elements()
    {
        $this->actingAs($this->admin)
             ->get('/dashboard')
             ->assertSeeText('Tableau de bord', ['fr' => 'FR'])
             ->assertSeeText('Dashboard', ['en' => 'EN'])
             ->assertSeeText('Bodi ya dashibodi', ['sw' => 'SW']);
    }
    
    /** @test */
    public function api_returns_translations_in_correct_format()
    {
        $response = $this->get('/api/v1/translations/fr');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'locale', 
                    'translations' => [
                        'dashboard.title',
                        'common.save',
                    ]
                ]);
    }
}
```

## 12. 📈 Performance Optimizations

### Lazy Loading
```php
class LazyTranslationLoader
{
    protected $loadedTranslations = [];
    
    public function translate($key, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        if (!isset($this->loadedTranslations[$locale])) {
            $this->loadedTranslations[$locale] = Cache::remember(
                "lazy_translations_{$locale}",
                1800,
                fn() => Translation::forLanguage($locale)->pluck('value', 'key')->toArray()
            );
        }
        
        return $this->loadedTranslations[$locale][$key] ?? $key;
    }
}
```

### Database Optimization
```sql
-- Add composite indexes for performance
CREATE INDEX translations_lookup_idx ON translations(language_code, key, value);
CREATE INDEX translations_search_idx ON translations(language_code, value(100));

-- Set up regular cache invalidation
-- Run as part of translation updates
```

---

## 🎯 Integration Checklist

- [ ] **Frontend Templates**: Replace `__()` with `trans_db()` in all blade files
- [ ] **JavaScript**: Include `db-translations.js` and add `data-trans` attributes
- [ ] **React Dashboard**: Use `useDbTranslations` hook in all components
- [ ] **API Endpoints**: Add translation responses to all API calls
- [ ] **Service Classes**: Update all user-facing messages to use database translations
- [ ] **Validation Messages**: Convert validation messages to database-driven
- [ ] **Email Templates**: Update all email notifications
- [ ] **Testing**: Add translation coverage to all test suites
- [ ] **Caching**: Set up Redis or other cache backend for translations
- [ ] **Monitoring**: Implement translation completion tracking

This comprehensive integration strategy ensures your entire application benefits from the database-driven translation system, providing complete control over all user-facing text while maintaining excellent performance.
