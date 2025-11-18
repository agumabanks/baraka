<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    protected GeneralSettingsInterface $generalSettings;
    protected CurrencyInterface $currency;

    public function __construct(GeneralSettingsInterface $generalSettings, CurrencyInterface $currency)
    {
        $this->generalSettings = $generalSettings;
        $this->currency = $currency;
    }

    /**
     * Display the main System Settings page (tabbed macOS-style view)
     * backed by the GeneralSettings repository.
     */
    public function index()
    {
        $settings = $this->generalSettings->all();
        $currencies = $this->currency->getActive();

        return view('settings.index', compact('settings', 'currencies'));
    }

    /**
     * Display general settings page.
     */
    public function general()
    {
        $supportedLocales = config('translations.supported', ['en']);

        $localeLabels = [
            'en' => 'English',
            'fr' => 'French',
            'sw' => 'Swahili',
        ];

        $locales = [];

        foreach ($supportedLocales as $code) {
            $locales[$code] = $localeLabels[$code] ?? strtoupper($code);
        }

        $preferenceStore = $this->generalSettings->all();
        $preferenceMatrix = $preferenceStore->details ?? [];

        $settings = [
            'app_name' => config('app.name', 'Baraka Sanaa'),
            'app_url' => config('app.url'),
            'app_timezone' => config('app.timezone'),
            'app_locale' => config('app.locale'),
            'app_debug' => config('app.debug'),
            'maintenance_mode' => config('maintenance.enabled', false),
            'app_environment' => config('app.env'),
        ];

        return view('settings.general', [
            'settings' => $settings,
            'locales' => $locales,
            'preferenceMatrix' => $preferenceMatrix,
        ]);
    }

    /**
     * Update general settings.
     */
    public function updateGeneral(Request $request): JsonResponse
    {
        $supportedLocales = config('translations.supported', ['en']);

        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'app_timezone' => 'required|string',
            'app_locale' => 'required|string|in:'.implode(',', $supportedLocales),
            'maintenance_mode' => 'boolean',
        ]);

        try {
            // Update .env file or settings store
            // This would typically update configuration or database settings
            
            // Example of updating config
            config(['app.name' => $request->app_name]);
            config(['app.url' => $request->app_url]);
            config(['app.timezone' => $request->app_timezone]);
            config(['app.locale' => $request->app_locale]);
            
            if ($request->maintenance_mode) {
                Artisan::call('down');
            } else {
                Artisan::call('up');
            }

            Log::info('General settings updated', [
                'user_id' => auth()->id(),
                'settings' => $request->only(['app_name', 'app_url', 'app_timezone', 'app_locale'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'General settings updated successfully!',
                'redirect' => route('settings.general')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update general settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display language and translation management page.
     */
    public function language(Request $request)
    {
        $supportedLocales = translation_supported_languages();

        $localeLabels = [
            'en' => 'English',
            'fr' => 'Français',
            'sw' => 'Kiswahili',
        ];

        $locales = [];

        foreach ($supportedLocales as $code) {
            $locales[$code] = $localeLabels[$code] ?? strtoupper($code);
        }

        $activeLocale = $request->get('language_code', app()->getLocale());
        if (! in_array($activeLocale, $supportedLocales, true)) {
            $activeLocale = $supportedLocales[0] ?? 'en';
        }

        $search = trim((string) $request->get('q', ''));

        $query = Translation::query()->where('language_code', $activeLocale);

        if ($search !== '') {
            $query->where('key', 'like', '%' . $search . '%');
        }

        $translations = $query
            ->orderBy('key')
            ->paginate(50)
            ->withQueryString();

        $totalCount = $translations->total();

        $defaultLocale = config('app.locale', 'en');

        return view('settings.language', [
            'locales' => $locales,
            'activeLocale' => $activeLocale,
            'defaultLocale' => $defaultLocale,
            'search' => $search,
            'translations' => $translations,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * Update language defaults and translations.
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        $supportedLocales = translation_supported_languages();

        $request->validate([
            'active_locale' => 'required|string|in:'.implode(',', $supportedLocales),
            'default_locale' => 'nullable|string|in:'.implode(',', $supportedLocales),
            'translations' => 'array',
        ]);

        $activeLocale = $request->input('active_locale');
        $defaultLocale = $request->input('default_locale');

        try {
            // Update individual translation values for the active locale
            $translations = $request->input('translations', []);

            foreach ($translations as $key => $value) {
                $key = trim((string) $key);
                $value = is_string($value) ? trim($value) : $value;

                if ($key === '') {
                    continue;
                }

                if ($value === null || $value === '') {
                    // Skip empty values for now to avoid accidental destructive deletes.
                    continue;
                }

                Translation::updateOrCreate(
                    [
                        'key' => $key,
                        'language_code' => $activeLocale,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }

            // Handle new translation row (optional)
            $newKey = trim((string) $request->input('new_translation.key', ''));
            $newValue = trim((string) $request->input('new_translation.value', ''));

            if ($newKey !== '' && $newValue !== '') {
                Translation::updateOrCreate(
                    [
                        'key' => $newKey,
                        'language_code' => $activeLocale,
                    ],
                    [
                        'value' => $newValue,
                    ]
                );
            }

            // Clear caches so new values are visible immediately
            if (function_exists('clear_translation_cache')) {
                clear_translation_cache($activeLocale);
            }

            // Optionally update default app locale for the current runtime/session
            if ($defaultLocale) {
                config(['app.locale' => $defaultLocale]);
                app()->setLocale($defaultLocale);
                Session::put('locale', $defaultLocale);
            }

            Log::info('Language settings updated', [
                'user_id' => auth()->id(),
                'active_locale' => $activeLocale,
                'default_locale' => $defaultLocale,
                'updated_keys' => array_keys($translations),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Language & translations updated successfully!',
                'redirect' => route('settings.language', ['language_code' => $activeLocale]),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update language settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update language settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display branding settings page.
     */
    public function branding()
    {
        $settings = [
            'primary_color' => config('branding.primary_color', '#0d6efd'),
            'secondary_color' => config('branding.secondary_color', '#6c757d'),
            'logo_url' => config('branding.logo_url'),
            'favicon_url' => config('branding.favicon_url'),
            'company_name' => config('branding.company_name'),
            'tagline' => config('branding.tagline'),
        ];

        return view('settings.branding', compact('settings'));
    }

    /**
     * Update branding settings.
     */
    public function updateBranding(Request $request): JsonResponse
    {
        $request->validate([
            'primary_color' => 'required|regex:/^#[0-9A-F]{6}$/i',
            'secondary_color' => 'required|regex:/^#[0-9A-F]{6}$/i',
            'company_name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png,jpeg,png,jpg,gif|max:512',
        ]);

        try {
            // Handle file uploads
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('branding', 'public');
                config(['branding.logo_url' => Storage::url($logoPath)]);
            }

            if ($request->hasFile('favicon')) {
                $faviconPath = $request->file('favicon')->store('branding', 'public');
                config(['branding.favicon_url' => Storage::url($faviconPath)]);
            }

            // Update branding configuration
            config(['branding.primary_color' => $request->primary_color]);
            config(['branding.secondary_color' => $request->secondary_color]);
            config(['branding.company_name' => $request->company_name]);
            config(['branding.tagline' => $request->tagline]);

            // Clear cache to apply changes
            Artisan::call('view:clear');
            Artisan::call('config:clear');

            Log::info('Branding settings updated', [
                'user_id' => auth()->id(),
                'settings' => $request->only(['primary_color', 'secondary_color', 'company_name', 'tagline'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Branding settings updated successfully!',
                'redirect' => route('settings.branding')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update branding settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update branding settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display operations settings page.
     */
    public function operations()
    {
        $settings = [
            'max_file_size' => config('operations.max_file_size', 10240),
            'allowed_file_types' => config('operations.allowed_file_types', ['jpg', 'jpeg', 'png', 'gif', 'pdf']),
            'auto_backup' => config('operations.auto_backup', true),
            'backup_frequency' => config('operations.backup_frequency', 'daily'),
            'maintenance_window' => config('operations.maintenance_window', '02:00'),
        ];

        return view('settings.operations', compact('settings'));
    }

    /**
     * Update operations settings.
     */
    public function updateOperations(Request $request): JsonResponse
    {
        $request->validate([
            'max_file_size' => 'required|integer|min:1024|max:102400',
            'allowed_file_types' => 'required|array|min:1',
            'allowed_file_types.*' => 'string|in:jpg,jpeg,png,gif,pdf,doc,docx,txt,csv',
            'auto_backup' => 'boolean',
            'backup_frequency' => 'required|in:hourly,daily,weekly,monthly',
            'maintenance_window' => 'required|date_format:H:i',
        ]);

        try {
            // Update operations configuration
            config(['operations.max_file_size' => $request->max_file_size]);
            config(['operations.allowed_file_types' => $request->allowed_file_types]);
            config(['operations.auto_backup' => $request->auto_backup]);
            config(['operations.backup_frequency' => $request->backup_frequency]);
            config(['operations.maintenance_window' => $request->maintenance_window]);

            Log::info('Operations settings updated', [
                'user_id' => auth()->id(),
                'settings' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Operations settings updated successfully!',
                'redirect' => route('settings.operations')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update operations settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update operations settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display finance settings page.
     */
    public function finance()
    {
        $settings = [
            'default_currency' => config('finance.default_currency', 'USD'),
            'currency_symbol' => config('finance.currency_symbol', '$'),
            'tax_rate' => config('finance.tax_rate', 0),
            'payment_methods' => config('finance.payment_methods', ['stripe', 'paypal']),
            'invoice_prefix' => config('finance.invoice_prefix', 'INV-'),
        ];

        return view('settings.finance', compact('settings'));
    }

    /**
     * Update finance settings.
     */
    public function updateFinance(Request $request): JsonResponse
    {
        $request->validate([
            'default_currency' => 'required|string|size:3',
            'currency_symbol' => 'required|string|size:1',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'payment_methods' => 'required|array|min:1',
            'payment_methods.*' => 'string|in:stripe,paypal,square,authorizenet',
            'invoice_prefix' => 'required|string|max:10',
        ]);

        try {
            // Update finance configuration
            config(['finance.default_currency' => $request->default_currency]);
            config(['finance.currency_symbol' => $request->currency_symbol]);
            config(['finance.tax_rate' => $request->tax_rate]);
            config(['finance.payment_methods' => $request->payment_methods]);
            config(['finance.invoice_prefix' => $request->invoice_prefix]);

            Log::info('Finance settings updated', [
                'user_id' => auth()->id(),
                'settings' => $request->only(['default_currency', 'tax_rate', 'payment_methods'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Finance settings updated successfully!',
                'redirect' => route('settings.finance')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update finance settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update finance settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display notifications settings page.
     */
    public function notifications()
    {
        $settings = [
            'email_notifications' => config('notifications.email', true),
            'sms_notifications' => config('notifications.sms', false),
            'push_notifications' => config('notifications.push', true),
            'slack_notifications' => config('notifications.slack', false),
            'slack_webhook' => config('notifications.slack_webhook'),
        ];

        return view('settings.notifications', compact('settings'));
    }

    /**
     * Update notifications settings.
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'slack_notifications' => 'boolean',
            'slack_webhook' => 'nullable|url',
        ]);

        try {
            // Update notifications configuration
            config(['notifications.email' => $request->email_notifications]);
            config(['notifications.sms' => $request->sms_notifications]);
            config(['notifications.push' => $request->push_notifications]);
            config(['notifications.slack' => $request->slack_notifications]);
            config(['notifications.slack_webhook' => $request->slack_webhook]);

            Log::info('Notification settings updated', [
                'user_id' => auth()->id(),
                'settings' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully!',
                'redirect' => route('settings.notifications')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update notification settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display integrations settings page.
     */
    public function integrations()
    {
        $integrations = [
            'stripe' => [
                'enabled' => config('integrations.stripe.enabled', false),
                'public_key' => config('integrations.stripe.public_key'),
                'webhook_secret' => config('integrations.stripe.webhook_secret'),
            ],
            'paypal' => [
                'enabled' => config('integrations.paypal.enabled', false),
                'client_id' => config('integrations.paypal.client_id'),
                'webhook_id' => config('integrations.paypal.webhook_id'),
            ],
            'google' => [
                'enabled' => config('integrations.google.enabled', false),
                'analytics_id' => config('integrations.google.analytics_id'),
                'maps_api_key' => config('integrations.google.maps_api_key'),
            ],
        ];

        return view('settings.integrations', compact('integrations'));
    }

    /**
     * Update integrations settings.
     */
    public function updateIntegrations(Request $request): JsonResponse
    {
        $request->validate([
            'stripe.public_key' => 'nullable|string',
            'stripe.secret_key' => 'nullable|string',
            'paypal.client_id' => 'nullable|string',
            'paypal.client_secret' => 'nullable|string',
            'google.analytics_id' => 'nullable|string',
            'google.maps_api_key' => 'nullable|string',
        ]);

        try {
            // Update integration settings (in production, encrypt sensitive keys)
            if ($request->stripe) {
                config(['integrations.stripe.public_key' => $request->stripe['public_key'] ?? null]);
                config(['integrations.stripe.secret_key' => $request->stripe['secret_key'] ?? null]);
            }

            if ($request->paypal) {
                config(['integrations.paypal.client_id' => $request->paypal['client_id'] ?? null]);
                config(['integrations.paypal.client_secret' => $request->paypal['client_secret'] ?? null]);
            }

            if ($request->google) {
                config(['integrations.google.analytics_id' => $request->google['analytics_id'] ?? null]);
                config(['integrations.google.maps_api_key' => $request->google['maps_api_key'] ?? null]);
            }

            Log::info('Integration settings updated', [
                'user_id' => auth()->id(),
                'integrations' => array_keys(array_filter($request->only(['stripe', 'paypal', 'google'])))
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integration settings updated successfully!',
                'redirect' => route('settings.integrations')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update integration settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update integration settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display system settings page.
     */
    public function system()
    {
        $system = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'timezone' => date_default_timezone_get(),
            'database_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_connection' => config('queue.default'),
        ];

        return view('settings.system', compact('system'));
    }

    /**
     * Update system settings.
     */
    public function updateSystem(Request $request): JsonResponse
    {
        try {
            // System settings update logic
            // This would typically update system configuration
            
            Log::info('System settings updated', [
                'user_id' => auth()->id(),
                'settings' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'System settings updated successfully!',
                'redirect' => route('settings.system')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update system settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update system settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display website settings page.
     */
    public function website()
    {
        $settings = [
            'site_title' => config('website.title', 'Baraka Sanaa'),
            'site_description' => config('website.description'),
            'site_keywords' => config('website.keywords'),
            'google_analytics_id' => config('website.ga_id'),
            'google_search_console' => config('website.search_console'),
            'robots_txt' => config('website.robots_txt'),
            'sitemap_enabled' => config('website.sitemap', true),
        ];

        return view('settings.website', compact('settings'));
    }

    /**
     * Update website settings.
     */
    public function updateWebsite(Request $request): JsonResponse
    {
        $request->validate([
            'site_title' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'site_keywords' => 'nullable|string',
            'google_analytics_id' => 'nullable|string',
            'google_search_console' => 'nullable|string',
            'robots_txt' => 'nullable|string',
        ]);

        try {
            // Update website configuration
            config(['website.title' => $request->site_title]);
            config(['website.description' => $request->site_description]);
            config(['website.keywords' => $request->site_keywords]);
            config(['website.ga_id' => $request->google_analytics_id]);
            config(['website.search_console' => $request->google_search_console]);
            config(['website.robots_txt' => $request->robots_txt]);

            Log::info('Website settings updated', [
                'user_id' => auth()->id(),
                'settings' => $request->only(['site_title', 'site_description', 'google_analytics_id'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Website settings updated successfully!',
                'redirect' => route('settings.website')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update website settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update website settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test external service connection.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $request->validate([
            'service' => 'required|string|in:stripe,paypal,google,slack',
            'credentials' => 'required|array',
        ]);

        try {
            $service = $request->service;
            $credentials = $request->credentials;

            $result = match($service) {
                'stripe' => $this->testStripeConnection($credentials),
                'paypal' => $this->testPaypalConnection($credentials),
                'google' => $this->testGoogleConnection($credentials),
                'slack' => $this->testSlackConnection($credentials),
                default => false
            };

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Connection test successful!' : 'Connection test failed!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export settings to file.
     */
    public function export()
    {
        try {
            // Export current settings
            $settings = config()->all();
            $filename = 'settings_backup_' . date('Y-m-d_H-i-s') . '.json';
            
            return response()->json($settings)
                ->header('Content-Disposition', "attachment; filename={$filename}");

        } catch (\Exception $e) {
            Log::error('Failed to export settings', ['error' => $e->getMessage()]);
            
            return back()->with('error', 'Failed to export settings: ' . $e->getMessage());
        }
    }

    /**
     * Clear application cache.
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            Log::info('Application cache cleared', ['user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Application cache cleared successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper methods

    private function getDatabaseSize(): string
    {
        try {
            $size = DB::table('information_schema.tables')
                ->where('table_schema', config('database.connections.mysql.database'))
                ->sum('data_length');
            
            return $size ? round($size / 1024 / 1024, 2) . ' MB' : 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getLastBackup(): ?string
    {
        try {
            $lastBackup = Cache::get('last_backup');
            return $lastBackup ? date('Y-m-d H:i:s', $lastBackup) : 'Never';
        } catch (\Exception $e) {
            return 'Never';
        }
    }

    private function getSystemHealth(): string
    {
        try {
            // Basic health check
            if (DB::connection()->getPdo()) {
                return 'healthy';
            }
            return 'unhealthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    private function getActiveIntegrations(): int
    {
        try {
            return count(array_filter([
                config('integrations.stripe.enabled', false),
                config('integrations.paypal.enabled', false),
                config('integrations.google.enabled', false),
            ]));
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getRecentActivity(): array
    {
        // This would typically fetch from activity logs
        return [
            [
                'action' => 'Settings updated',
                'details' => 'General settings modified',
                'timestamp' => now()->subHours(2),
                'user' => auth()->user()->name ?? 'System'
            ],
            [
                'action' => 'Backup completed',
                'details' => 'Automatic database backup',
                'timestamp' => now()->subHours(6),
                'user' => 'System'
            ],
            [
                'action' => 'Integration test',
                'details' => 'Stripe connection tested',
                'timestamp' => now()->subDay(),
                'user' => auth()->user()->name ?? 'System'
            ]
        ];
    }

    private function testStripeConnection(array $credentials): bool
    {
        try {
            // Mock Stripe connection test
            return !empty($credentials['secret_key']);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testPaypalConnection(array $credentials): bool
    {
        try {
            // Mock PayPal connection test
            return !empty($credentials['client_id']) && !empty($credentials['client_secret']);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testGoogleConnection(array $credentials): bool
    {
        try {
            // Mock Google connection test
            return !empty($credentials['api_key']);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testSlackConnection(array $credentials): bool
    {
        try {
            // Mock Slack connection test
            return !empty($credentials['webhook_url']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
