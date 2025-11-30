<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use App\Models\Backend\GeneralSettings;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    protected GeneralSettingsInterface $generalSettings;
    protected CurrencyInterface $currency;

    public function __construct(GeneralSettingsInterface $generalSettings, CurrencyInterface $currency)
    {
        $this->generalSettings = $generalSettings;
        $this->currency = $currency;
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || (! $user->hasRole(['admin', 'super-admin']) && ! $user->hasPermission('settings_manage'))) {
                abort(403);
            }
            return $next($request);
        });
    }

    /**
     * Get the settings model and its details
     */
    private function getSettingsWithDetails(): array
    {
        $settings = $this->generalSettings->all();
        $details = $settings->details ?? [];
        return [$settings, $details];
    }

    /**
     * Save settings details to database
     */
    private function saveSettings(GeneralSettings $settings, array $details): void
    {
        $settings->details = $details;
        $settings->save();
        SystemSettings::flush();
        Cache::forget('settings');
    }

    /**
     * Display the main System Preferences page
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
        [$settings, $details] = $this->getSettingsWithDetails();
        
        $supportedLocales = config('translations.supported', ['en', 'fr', 'sw']);
        $localeLabels = ['en' => 'English', 'fr' => 'French', 'sw' => 'Swahili'];
        $locales = [];
        foreach ($supportedLocales as $code) {
            $locales[$code] = $localeLabels[$code] ?? strtoupper($code);
        }

        $settingsData = [
            'app_name' => $settings->name ?? config('app.name'),
            'app_url' => data_get($details, 'general.app_url', config('app.url')),
            'app_timezone' => data_get($details, 'general.timezone', config('app.timezone')),
            'app_locale' => data_get($details, 'localization.default_locale', config('app.locale')),
            'app_debug' => config('app.debug'),
            'maintenance_mode' => data_get($details, 'system.maintenance_mode', false),
            'app_environment' => config('app.env'),
        ];

        return view('settings.general', [
            'settings' => $settingsData,
            'locales' => $locales,
            'preferenceMatrix' => $details,
            'currencies' => $this->currency->getActive(),
            'defaultCurrency' => data_get($details, 'finance.primary_currency', $settings->currency ?? 'UGX'),
        ]);
    }

    /**
     * Update general settings (DATABASE-BACKED)
     */
    public function updateGeneral(Request $request): JsonResponse
    {
        try {
            [$settings, $details] = $this->getSettingsWithDetails();

            // Update general settings
            $details['general'] = array_merge($details['general'] ?? [], [
                'app_name' => $request->input('app_name', $settings->name),
                'app_url' => $request->input('app_url', config('app.url')),
                'support_email' => $request->input('support_email'),
                'timezone' => $request->input('app_timezone', 'UTC'),
                'date_format' => $request->input('date_format', 'd/m/Y'),
            ]);

            $details['localization'] = array_merge($details['localization'] ?? [], [
                'default_locale' => $request->input('app_locale', 'en'),
            ]);

            $details['finance'] = array_merge($details['finance'] ?? [], [
                'primary_currency' => strtoupper($request->input('default_currency', 'UGX')),
            ]);

            $details['system'] = array_merge($details['system'] ?? [], [
                'maintenance_mode' => (bool) $request->input('maintenance_mode'),
                'allow_registration' => (bool) $request->input('allow_registration'),
                'require_email_verification' => (bool) $request->input('require_email_verification'),
                'session_timeout' => (int) $request->input('session_timeout', 120),
            ]);

            // Update root-level settings
            $settings->name = $request->input('app_name', $settings->name);
            if ($request->input('default_currency')) {
                $settings->currency = strtoupper($request->input('default_currency'));
            }

            $this->saveSettings($settings, $details);

            // Handle maintenance mode
            if ($request->input('maintenance_mode')) {
                Artisan::call('down', ['--allow' => ['127.0.0.1']]);
            } else {
                Artisan::call('up');
            }

            Log::info('General settings updated', ['user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'General settings saved to database successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update general settings', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display operations settings page (DATABASE-BACKED)
     */
    public function operations()
    {
        [$settings, $details] = $this->getSettingsWithDetails();
        $ops = $details['operations'] ?? [];

        return view('settings.operations', [
            'settings' => $ops,
            'preferenceMatrix' => $details,
        ]);
    }

    /**
     * Update operations settings (DATABASE-BACKED)
     */
    public function updateOperations(Request $request): JsonResponse
    {
        try {
            [$settings, $details] = $this->getSettingsWithDetails();

            $details['operations'] = [
                // Shipment Configuration
                'auto_tracking_ids' => (bool) $request->input('auto_tracking_ids', true),
                'tracking_prefix' => strtoupper($request->input('tracking_prefix', 'BRK')),
                'awb_format' => $request->input('awb_format', 'date_prefix'),
                'default_status' => $request->input('default_status', 'booked'),
                'require_sender_signature' => (bool) $request->input('require_sender_signature'),
                'require_pod' => (bool) $request->input('require_pod', true),
                
                // SLAs
                'sla_express_hours' => (int) $request->input('sla_express_hours', 4),
                'sla_standard_hours' => (int) $request->input('sla_standard_hours', 24),
                'sla_economy_hours' => (int) $request->input('sla_economy_hours', 72),
                'sla_warning_percent' => (int) $request->input('sla_warning_percent', 75),
                'auto_escalate_overdue' => (bool) $request->input('auto_escalate_overdue', true),
                
                // Weight & Dimensions
                'weight_unit' => $request->input('weight_unit', 'kg'),
                'dimension_unit' => $request->input('dimension_unit', 'cm'),
                'volumetric_divisor' => (int) $request->input('volumetric_divisor', 5000),
                'max_parcel_weight' => (int) $request->input('max_parcel_weight', 70),
                'use_chargeable_weight' => (bool) $request->input('use_chargeable_weight', true),
                
                // Hub & Routing
                'auto_routing' => (bool) $request->input('auto_routing', true),
                'hub_processing_hours' => (int) $request->input('hub_processing_hours', 4),
                'enable_consolidation' => (bool) $request->input('enable_consolidation', true),
                'auto_close_manifests' => (bool) $request->input('auto_close_manifests'),
                'manifest_cutoff' => $request->input('manifest_cutoff', '18:00'),
                
                // COD
                'cod_enabled' => (bool) $request->input('cod_enabled', true),
                'cod_max_amount' => (float) $request->input('cod_max_amount', 5000000),
                'cod_fee_type' => $request->input('cod_fee_type', 'percentage'),
                'cod_fee_percent' => (float) $request->input('cod_fee_percent', 2.5),
                'cod_remittance_cycle' => $request->input('cod_remittance_cycle', 'daily'),
                
                // Returns
                'max_delivery_attempts' => (int) $request->input('max_delivery_attempts', 3),
                'auto_return_days' => (int) $request->input('auto_return_days', 7),
                'require_return_reason' => (bool) $request->input('require_return_reason', true),
                'charge_return_shipping' => (bool) $request->input('charge_return_shipping'),
                
                // Automation
                'auto_assign_drivers' => (bool) $request->input('auto_assign_drivers'),
                'auto_generate_invoices' => (bool) $request->input('auto_generate_invoices', true),
                'backup_frequency' => $request->input('backup_frequency', 'daily'),
                'backup_retention_days' => (int) $request->input('backup_retention_days', 30),
            ];

            // Update tracking prefix at root level too
            if ($request->input('tracking_prefix')) {
                $settings->par_track_prefix = strtoupper($request->input('tracking_prefix'));
            }

            $this->saveSettings($settings, $details);

            Log::info('Operations settings updated', ['user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Operations settings saved to database successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update operations settings', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display finance settings page (DATABASE-BACKED)
     */
    public function finance()
    {
        [$settings, $details] = $this->getSettingsWithDetails();
        $fin = $details['finance'] ?? [];

        return view('settings.finance', [
            'settings' => $fin,
            'preferenceMatrix' => $details,
            'rootSettings' => $settings,
        ]);
    }

    /**
     * Update finance settings (DATABASE-BACKED)
     */
    public function updateFinance(Request $request): JsonResponse
    {
        try {
            [$settings, $details] = $this->getSettingsWithDetails();

            $details['finance'] = [
                // Currency
                'primary_currency' => strtoupper($request->input('primary_currency', 'UGX')),
                'currency_position' => $request->input('currency_position', 'before'),
                'decimal_places' => (int) $request->input('decimal_places', 0),
                'thousand_separator' => $request->input('thousand_separator', ','),
                'multi_currency' => (bool) $request->input('multi_currency'),
                
                // Tax
                'tax_enabled' => (bool) $request->input('tax_enabled', true),
                'vat_rate' => (float) $request->input('vat_rate', 18),
                'tax_calculation' => $request->input('tax_calculation', 'exclusive'),
                'wht_rate' => (float) $request->input('wht_rate', 6),
                'tax_number' => $request->input('tax_number'),
                
                // Invoicing
                'invoice_prefix' => strtoupper($request->input('invoice_prefix', 'INV-')),
                'invoice_format' => $request->input('invoice_format', 'sequential'),
                'payment_terms' => (int) $request->input('payment_terms', 30),
                'auto_invoice' => (bool) $request->input('auto_invoice', true),
                'auto_email_invoice' => (bool) $request->input('auto_email_invoice'),
                'invoice_footer' => $request->input('invoice_footer'),
                
                // Pricing
                'pricing_mode' => $request->input('pricing_mode', 'zone_weight'),
                'fuel_surcharge' => (float) $request->input('fuel_surcharge', 8),
                'insurance_rate' => (float) $request->input('insurance_rate', 1.5),
                'min_charge' => (float) $request->input('min_charge', 5000),
                'dynamic_pricing' => (bool) $request->input('dynamic_pricing'),
                
                // Payment Methods
                'payment_cash' => (bool) $request->input('payment_cash', true),
                'payment_mobile_money' => (bool) $request->input('payment_mobile_money', true),
                'payment_bank_transfer' => (bool) $request->input('payment_bank_transfer', true),
                'payment_credit' => (bool) $request->input('payment_credit', true),
                'payment_card' => (bool) $request->input('payment_card'),
                'payment_cheque' => (bool) $request->input('payment_cheque'),
                
                // Credit Management
                'credit_enabled' => (bool) $request->input('credit_enabled', true),
                'default_credit_limit' => (float) $request->input('default_credit_limit', 1000000),
                'credit_check_booking' => (bool) $request->input('credit_check_booking', true),
                'block_over_credit' => (bool) $request->input('block_over_credit'),
                'late_fee' => (float) $request->input('late_fee', 2),
                
                // Settlements
                'merchant_settlement_cycle' => $request->input('merchant_settlement_cycle', 'weekly'),
                'driver_settlement_cycle' => $request->input('driver_settlement_cycle', 'daily'),
                'auto_settlements' => (bool) $request->input('auto_settlements', true),
                'settlement_approval' => (bool) $request->input('settlement_approval', true),
            ];

            // Update root level
            $settings->currency = strtoupper($request->input('primary_currency', $settings->currency));
            $settings->invoice_prefix = strtoupper($request->input('invoice_prefix', $settings->invoice_prefix));

            $this->saveSettings($settings, $details);

            Log::info('Finance settings updated', ['user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Finance settings saved to database successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update finance settings', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display system settings page (DATABASE-BACKED)
     */
    public function system()
    {
        [$settings, $details] = $this->getSettingsWithDetails();
        $sys = $details['system'] ?? [];

        return view('settings.system', [
            'settings' => $sys,
            'preferenceMatrix' => $details,
        ]);
    }

    /**
     * Update system settings (DATABASE-BACKED)
     */
    public function updateSystem(Request $request): JsonResponse
    {
        try {
            [$settings, $details] = $this->getSettingsWithDetails();

            $details['system'] = [
                // Authentication
                'allow_registration' => (bool) $request->input('allow_registration'),
                'require_email_verification' => (bool) $request->input('require_email_verification', true),
                '2fa_enforcement' => $request->input('2fa_enforcement', 'admin_required'),
                'session_timeout' => (int) $request->input('session_timeout', 120),
                'max_sessions' => (int) $request->input('max_sessions', 3),
                'strong_passwords' => (bool) $request->input('strong_passwords', true),
                'password_min_length' => (int) $request->input('password_min_length', 8),
                'password_expiry_days' => (int) $request->input('password_expiry_days', 90),
                
                // Brute Force
                'max_login_attempts' => (int) $request->input('max_login_attempts', 5),
                'lockout_duration' => (int) $request->input('lockout_duration', 15),
                'rate_limit_decay' => (int) $request->input('rate_limit_decay', 60),
                'ip_blocking' => (bool) $request->input('ip_blocking', true),
                'security_alerts' => (bool) $request->input('security_alerts', true),
                
                // API
                'api_enabled' => (bool) $request->input('api_enabled', true),
                'api_rate_limit' => (int) $request->input('api_rate_limit', 60),
                'api_key_expiry' => (int) $request->input('api_key_expiry', 365),
                'api_ip_whitelist' => (bool) $request->input('api_ip_whitelist'),
                'api_logging' => (bool) $request->input('api_logging', true),
                
                // Data & Privacy
                'log_retention_days' => (int) $request->input('log_retention_days', 365),
                'shipment_retention_years' => (int) $request->input('shipment_retention_years', 7),
                'data_encryption' => (bool) $request->input('data_encryption', true),
                'gdpr_mode' => (bool) $request->input('gdpr_mode'),
                'mask_pii' => (bool) $request->input('mask_pii', true),
                
                // Performance
                'response_caching' => (bool) $request->input('response_caching', true),
                'cache_ttl' => (int) $request->input('cache_ttl', 3600),
                'query_logging' => (bool) $request->input('query_logging'),
                'slow_query_ms' => (int) $request->input('slow_query_ms', 1000),
                
                // Debug
                'debug_mode' => (bool) $request->input('debug_mode'),
                'log_level' => $request->input('log_level', 'warning'),
                'log_channel' => $request->input('log_channel', 'daily'),
            ];

            $this->saveSettings($settings, $details);

            Log::info('System settings updated', ['user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'System settings saved to database successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update system settings', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display branding settings page
     */
    public function branding()
    {
        [$settings, $details] = $this->getSettingsWithDetails();
        $branding = $details['branding'] ?? [];

        return view('settings.branding', [
            'settings' => array_merge([
                'primary_color' => $settings->primary_color ?? '#3b82f6',
                'secondary_color' => data_get($branding, 'secondary_color', '#64748b'),
                'company_name' => $settings->name ?? config('app.name'),
                'tagline' => data_get($branding, 'tagline', ''),
                'logo_url' => $settings->logo_image ?? null,
                'favicon_url' => $settings->favicon_image ?? null,
            ], $branding),
        ]);
    }

    /**
     * Update branding settings (DATABASE-BACKED)
     */
    public function updateBranding(Request $request): JsonResponse
    {
        try {
            [$settings, $details] = $this->getSettingsWithDetails();

            $details['branding'] = array_merge($details['branding'] ?? [], [
                'theme' => $request->input('theme', 'auto'),
                'primary_color' => $request->input('primary_color', '#3b82f6'),
                'secondary_color' => $request->input('secondary_color', '#64748b'),
                'tagline' => $request->input('tagline'),
                'font_family' => $request->input('font_family', 'system'),
                'font_size' => $request->input('font_size', '16'),
            ]);

            // Update root level
            $settings->name = $request->input('company_name', $settings->name);
            $settings->primary_color = $request->input('primary_color', $settings->primary_color);

            $this->saveSettings($settings, $details);

            Log::info('Branding settings updated', ['user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Branding settings saved to database successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update branding settings', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display notifications settings page
     */
    public function notifications()
    {
        [$settings, $details] = $this->getSettingsWithDetails();
        $notif = $details['notifications'] ?? [];

        return view('settings.notifications', [
            'settings' => $notif,
        ]);
    }

    /**
     * Update notifications settings (DATABASE-BACKED)
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        try {
            [$settings, $details] = $this->getSettingsWithDetails();

            $details['notifications'] = [
                'show_notifications' => (bool) $request->input('show_notifications', true),
                'notification_sound' => (bool) $request->input('notification_sound', true),
                'badge_count' => (bool) $request->input('badge_count', true),
                
                'email_notifications' => (bool) $request->input('email_notifications', true),
                'sms_notifications' => (bool) $request->input('sms_notifications'),
                'push_notifications' => (bool) $request->input('push_notifications', true),
                'slack_notifications' => (bool) $request->input('slack_notifications'),
                'slack_webhook' => $request->input('slack_webhook'),
                
                'notify_new_shipment' => (bool) $request->input('notify_new_shipment', true),
                'notify_status_change' => (bool) $request->input('notify_status_change', true),
                'notify_delivery' => (bool) $request->input('notify_delivery', true),
                'notify_payment' => (bool) $request->input('notify_payment', true),
                'notify_system' => (bool) $request->input('notify_system', true),
                
                'quiet_hours_enabled' => (bool) $request->input('quiet_hours_enabled'),
                'quiet_start' => $request->input('quiet_start', '22:00'),
                'quiet_end' => $request->input('quiet_end', '07:00'),
                'allow_critical' => (bool) $request->input('allow_critical', true),
                
                'digest_enabled' => (bool) $request->input('digest_enabled'),
                'digest_frequency' => $request->input('digest_frequency', 'daily'),
            ];

            $this->saveSettings($settings, $details);

            Log::info('Notification settings updated', ['user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Notification settings saved to database successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update notification settings', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display integrations settings page
     */
    public function integrations()
    {
        [$settings, $details] = $this->getSettingsWithDetails();
        return view('settings.integrations', [
            'integrations' => $details['integrations'] ?? [],
        ]);
    }

    /**
     * Update integrations settings (DATABASE-BACKED)
     */
    public function updateIntegrations(Request $request): JsonResponse
    {
        try {
            [$settings, $details] = $this->getSettingsWithDetails();

            $details['integrations'] = array_merge($details['integrations'] ?? [], $request->all());

            $this->saveSettings($settings, $details);

            return response()->json([
                'success' => true,
                'message' => 'Integration settings saved to database successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display language settings page
     */
    public function language(Request $request)
    {
        $supportedLocales = ['en', 'fr', 'sw'];
        $localeLabels = ['en' => 'English', 'fr' => 'Français', 'sw' => 'Kiswahili'];

        $search = trim((string) $request->get('q', ''));
        $statusFilter = $request->get('status');

        $keysQuery = Translation::distinct();
        if ($search !== '') {
            $keysQuery->where(function ($query) use ($search) {
                $query->where('key', 'like', "%{$search}%")
                      ->orWhere('value', 'like', "%{$search}%");
            });
        }

        $allKeys = $keysQuery->pluck('key')->unique()->sort()->values();

        if ($statusFilter && in_array($statusFilter, ['complete', 'incomplete', 'empty'])) {
            $allKeys = $allKeys->filter(function ($key) use ($statusFilter, $supportedLocales) {
                $translationCount = 0;
                foreach ($supportedLocales as $lang) {
                    $translation = Translation::forLanguage($lang)->forKey($key)->first();
                    if ($translation && !empty($translation->value)) {
                        $translationCount++;
                    }
                }
                return match ($statusFilter) {
                    'complete' => $translationCount === count($supportedLocales),
                    'incomplete' => $translationCount > 0 && $translationCount < count($supportedLocales),
                    'empty' => $translationCount === 0,
                    default => true,
                };
            })->values();
        }

        $perPage = 25;
        $currentPage = $request->get('page', 1);
        $paginatedKeys = $allKeys->forPage($currentPage, $perPage);

        $translations = [];
        foreach ($paginatedKeys as $key) {
            $translations[$key] = [];
            foreach ($supportedLocales as $lang) {
                $translation = Translation::forLanguage($lang)->forKey($key)->first();
                $translations[$key][$lang] = [
                    'value' => $translation ? $translation->value : '',
                    'description' => $translation ? $translation->description : '',
                ];
            }
        }

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $translations,
            $allKeys->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $stats = Translation::getCompletionStats($supportedLocales);
        $defaultLocale = config('app.locale', 'en');

        return view('settings.language', [
            'locales' => $localeLabels,
            'supportedLocales' => $supportedLocales,
            'defaultLocale' => $defaultLocale,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'translations' => $paginator,
            'stats' => $stats,
            'totalCount' => $allKeys->count(),
        ]);
    }

    /**
     * Update language settings
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        $supportedLocales = ['en', 'fr', 'sw'];

        try {
            $translations = $request->input('translations', []);
            $updatedCount = 0;

            foreach ($translations as $key => $languages) {
                $key = trim((string) $key);
                if ($key === '') continue;

                foreach ($languages as $lang => $value) {
                    if (!in_array($lang, $supportedLocales)) continue;
                    $value = is_string($value) ? trim($value) : '';

                    Translation::updateOrCreate(
                        ['key' => $key, 'language_code' => $lang],
                        ['value' => $value]
                    );
                    if (!empty($value)) $updatedCount++;
                }
            }

            $newKey = trim((string) $request->input('new_translation.key', ''));
            if ($newKey !== '') {
                foreach ($supportedLocales as $lang) {
                    $newValue = trim((string) $request->input("new_translation.{$lang}", ''));
                    if ($newValue !== '') {
                        Translation::updateOrCreate(
                            ['key' => $newKey, 'language_code' => $lang],
                            ['value' => $newValue]
                        );
                        $updatedCount++;
                    }
                }
            }

            if (function_exists('clear_translation_cache')) {
                foreach ($supportedLocales as $lang) {
                    clear_translation_cache($lang);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} translation(s)!",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a translation key
     */
    public function deleteTranslation(string $key): JsonResponse
    {
        try {
            $deleted = Translation::deleteKey($key);
            if (function_exists('clear_translation_cache')) {
                foreach (['en', 'fr', 'sw'] as $lang) {
                    clear_translation_cache($lang);
                }
            }
            return response()->json([
                'success' => true,
                'message' => "Translation key '{$key}' deleted ({$deleted} records).",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display website settings page
     */
    public function website()
    {
        [$settings, $details] = $this->getSettingsWithDetails();
        $websiteData = $details['website'] ?? [];
        
        // Provide comprehensive defaults - DHL-Grade Courier for Africa, Europe & Global
        $defaults = [
            // SEO & Meta
            'site_title' => config('app.name', 'Baraka Logistics'),
            'site_tagline' => 'Africa\'s Premier International Courier & Logistics Partner',
            'site_description' => 'Baraka Logistics delivers world-class express courier, freight forwarding, and supply chain solutions connecting Africa to Europe, Asia, Americas and 220+ destinations worldwide. Same-day, next-day, and international express shipping.',
            'site_keywords' => 'international courier Africa, express shipping Uganda, freight forwarding East Africa, DHL alternative Africa, logistics Europe Africa, air freight, sea freight, customs clearance, e-commerce fulfillment, B2B logistics, cross-border shipping',
            'og_image' => '/images/baraka-og-image.jpg',
            
            // Hero Section
            'hero_title' => 'Connecting Africa to the World',
            'hero_subtitle' => 'DHL-grade express courier and logistics solutions serving 220+ destinations across Africa, Europe, Asia, and the Americas. Experience reliability, speed, and precision with every shipment.',
            'hero_background' => '/images/hero-logistics-bg.jpg',
            'hero_cta_primary_text' => 'Get Instant Quote',
            'hero_cta_primary_url' => '/quote',
            'hero_cta_secondary_text' => 'Track Your Shipment',
            'hero_cta_secondary_url' => '/tracking',
            'hero_show_tracking_widget' => true,
            
            // Features Section
            'features_enabled' => true,
            'features_title' => 'Why Leading Businesses Choose Baraka',
            'features_subtitle' => 'Enterprise-grade logistics infrastructure with the personal touch of a dedicated partner',
            'features' => [
                ['icon' => 'globe-2', 'title' => 'Global Network', 'description' => '220+ countries & territories covered with strategic hubs in Kampala, Nairobi, Dubai, Amsterdam, and London'],
                ['icon' => 'clock', 'title' => 'Express Delivery', 'description' => 'Same-day delivery within East Africa, 24-48hr to Europe, 48-72hr worldwide with guaranteed SLAs'],
                ['icon' => 'shield-check', 'title' => 'Fully Insured', 'description' => 'Comprehensive cargo insurance up to $100,000 with real-time proof of delivery and chain of custody'],
                ['icon' => 'smartphone', 'title' => 'Live Tracking', 'description' => 'GPS-enabled tracking with SMS/email notifications at every milestone - from pickup to doorstep'],
                ['icon' => 'file-text', 'title' => 'Customs Expertise', 'description' => 'Licensed customs brokerage with 99.8% clearance success rate across all African ports of entry'],
                ['icon' => 'headphones', 'title' => '24/7 Support', 'description' => 'Dedicated account managers and round-the-clock customer support in English, French & Swahili'],
            ],
            
            // Services Section
            'services_enabled' => true,
            'services_title' => 'Comprehensive Logistics Solutions',
            'services_subtitle' => 'From documents to freight - we move what matters most to your business',
            'services' => [
                ['icon' => 'zap', 'title' => 'Express Courier', 'description' => 'Time-critical document and parcel delivery with same-day, next-day, and priority options across Africa and worldwide', 'price' => 'From $12'],
                ['icon' => 'plane', 'title' => 'Air Freight', 'description' => 'Scheduled and charter air cargo services to Europe, Asia, Middle East and Americas with customs-cleared delivery', 'price' => 'From $4.50/kg'],
                ['icon' => 'ship', 'title' => 'Sea Freight', 'description' => 'FCL and LCL ocean freight with port-to-door service, container tracking, and competitive rates for bulk shipments', 'price' => 'From $800/CBM'],
                ['icon' => 'truck', 'title' => 'Road Freight', 'description' => 'Cross-border trucking across East, Central and Southern Africa with bonded transit and real-time fleet tracking', 'price' => 'Custom Quote'],
                ['icon' => 'package', 'title' => 'E-Commerce Fulfillment', 'description' => 'End-to-end fulfillment for online sellers: warehousing, pick-pack, last-mile delivery and returns management', 'price' => 'From $2/order'],
                ['icon' => 'building', 'title' => 'Contract Logistics', 'description' => 'Dedicated supply chain solutions for enterprises: 3PL, inventory management, distribution and reverse logistics', 'price' => 'Custom Quote'],
            ],
            
            // Statistics Section
            'stats_enabled' => true,
            'stats_background' => '/images/stats-bg.jpg',
            'stats' => [
                ['value' => '2M+', 'label' => 'Shipments Delivered'],
                ['value' => '220+', 'label' => 'Countries Served'],
                ['value' => '99.2%', 'label' => 'On-Time Delivery'],
                ['value' => '50+', 'label' => 'Branch Locations'],
                ['value' => '15K+', 'label' => 'Business Clients'],
                ['value' => '24/7', 'label' => 'Operations Center'],
            ],
            
            // About Section
            'about_enabled' => true,
            'about_title' => 'Africa\'s Fastest-Growing Logistics Company',
            'about_content' => 'Founded in Kampala, Baraka Logistics has grown from a local courier service to East Africa\'s leading international logistics provider. We combine global reach with deep local expertise, offering DHL-grade service quality at competitive African pricing.

Our state-of-the-art operations center processes over 10,000 shipments daily, with automated sorting, real-time tracking, and AI-powered route optimization. We\'ve built strategic partnerships with major airlines, shipping lines, and customs authorities to ensure seamless cross-border movement.

Whether you\'re an e-commerce entrepreneur shipping to Europe, a manufacturer importing machinery from China, or a corporation managing complex supply chains across Africa - Baraka delivers with precision, transparency, and care.',
            'about_image' => '/images/about-baraka-hub.jpg',
            'about_points' => [
                'ISO 9001:2015 & AEO Certified Operations',
                'Strategic hubs in 5 continents for fastest routing',
                'Integrated customs brokerage in 25 African countries',
                'Dedicated key account management for enterprise clients',
                'Carbon-neutral shipping options available',
                'Multi-currency billing (USD, EUR, GBP, UGX, KES)',
            ],
            
            // Testimonials Section
            'testimonials_enabled' => true,
            'testimonials_title' => 'Trusted by Africa\'s Leading Businesses',
            'testimonials' => [
                ['name' => 'Sarah Nakamya', 'company' => 'Jumia Uganda', 'content' => 'Baraka handles over 5,000 of our deliveries monthly with a 99.5% success rate. Their real-time tracking integration with our platform has transformed our customer experience.', 'rating' => 5, 'avatar' => ''],
                ['name' => 'Jean-Pierre Habimana', 'company' => 'Rwanda Trading Co.', 'content' => 'We\'ve been shipping coffee exports to Europe through Baraka for 3 years. Their customs expertise and air freight reliability are unmatched in the region.', 'rating' => 5, 'avatar' => ''],
                ['name' => 'Mohammed Al-Hassan', 'company' => 'Dubai Imports Ltd', 'content' => 'Baraka\'s door-to-door service from Dubai to Kampala is faster and more reliable than any carrier we\'ve used. The tracking is exceptional.', 'rating' => 5, 'avatar' => ''],
                ['name' => 'Emma Okonkwo', 'company' => 'Lagos Fashion House', 'content' => 'As an e-commerce business shipping to customers across Africa, Baraka\'s fulfillment service has been a game-changer. Returns are handled seamlessly.', 'rating' => 5, 'avatar' => ''],
            ],
            
            // Contact Section
            'contact_enabled' => true,
            'contact_title' => 'Get Started Today',
            'contact_subtitle' => 'Request a quote, schedule a pickup, or speak with our logistics experts',
            'contact_email' => 'info@baraka.co',
            'contact_phone' => '+256 312 000 000',
            'contact_whatsapp' => '+256 700 000 000',
            'contact_address' => 'Baraka Logistics Hub, Plot 45 Jinja Road, Industrial Area, Kampala, Uganda',
            'contact_map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7574744620366!2d32.6155!3d0.3136!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMMKwMTgnNDkuMCJOIDMywrAzNic1NS44IkU!5e0!3m2!1sen!2sug!4v1234567890',
            'contact_hours' => 'Operations: 24/7 | Customer Service: Mon-Sat 7AM-9PM EAT',
            
            // Social Links
            'social_facebook' => 'https://facebook.com/barakalogistics',
            'social_twitter' => 'https://twitter.com/baraboralogistics',
            'social_instagram' => 'https://instagram.com/barakalogistics',
            'social_linkedin' => 'https://linkedin.com/company/baraka-logistics',
            'social_youtube' => 'https://youtube.com/@barakalogistics',
            'social_tiktok' => '',
            
            // Footer
            'footer_about' => 'Baraka Logistics is East Africa\'s leading international courier and freight company, connecting businesses to 220+ destinations worldwide. Licensed customs broker, AEO certified, ISO 9001:2015 compliant.',
            'footer_copyright' => '© ' . date('Y') . ' Baraka Logistics Ltd. All rights reserved. Licensed by Uganda Revenue Authority & East African Community.',
            'footer_links' => [
                ['title' => 'Privacy Policy', 'url' => '/privacy'],
                ['title' => 'Terms of Service', 'url' => '/terms'],
                ['title' => 'Shipping Policy', 'url' => '/shipping-policy'],
                ['title' => 'Prohibited Items', 'url' => '/prohibited-items'],
                ['title' => 'Claims & Insurance', 'url' => '/claims'],
                ['title' => 'Careers', 'url' => '/careers'],
                ['title' => 'API Documentation', 'url' => '/developers'],
            ],
            
            // Analytics & Tracking
            'google_analytics_id' => '',
            'google_tag_manager_id' => '',
            'facebook_pixel_id' => '',
            'hotjar_id' => '',
            
            // Advanced
            'custom_css' => '',
            'custom_js_head' => '',
            'custom_js_body' => '',
            'robots_txt' => "User-agent: *\nAllow: /\n\nSitemap: https://baraka.co/sitemap.xml",
            'maintenance_mode' => false,
            'maintenance_message' => 'We are performing scheduled system maintenance to improve our services. Tracking remains available at track.baraka.co. We apologize for any inconvenience.',
        ];
        
        // Merge saved settings with defaults
        $mergedSettings = array_merge($defaults, $websiteData);
        
        return view('settings.website', [
            'settings' => $mergedSettings,
        ]);
    }

    /**
     * Update website settings (DATABASE-BACKED)
     */
    public function updateWebsite(Request $request): JsonResponse
    {
        try {
            [$settings, $details] = $this->getSettingsWithDetails();

            // All website settings keys to save
            $websiteKeys = [
                // SEO
                'site_title', 'site_tagline', 'site_description', 'site_keywords', 'og_image',
                // Hero
                'hero_title', 'hero_subtitle', 'hero_background', 
                'hero_cta_primary_text', 'hero_cta_primary_url',
                'hero_cta_secondary_text', 'hero_cta_secondary_url', 'hero_show_tracking_widget',
                // Features
                'features_enabled', 'features_title', 'features_subtitle', 'features',
                // Services
                'services_enabled', 'services_title', 'services_subtitle', 'services',
                // Stats
                'stats_enabled', 'stats_background', 'stats',
                // About
                'about_enabled', 'about_title', 'about_content', 'about_image', 'about_points',
                // Testimonials
                'testimonials_enabled', 'testimonials_title', 'testimonials',
                // Contact
                'contact_enabled', 'contact_title', 'contact_subtitle',
                'contact_email', 'contact_phone', 'contact_whatsapp', 'contact_address',
                'contact_map_embed', 'contact_hours',
                // Social
                'social_facebook', 'social_twitter', 'social_instagram', 
                'social_linkedin', 'social_youtube', 'social_tiktok',
                // Footer
                'footer_about', 'footer_copyright', 'footer_links',
                // Analytics
                'google_analytics_id', 'google_tag_manager_id', 'facebook_pixel_id', 'hotjar_id',
                // Advanced
                'custom_css', 'custom_js_head', 'custom_js_body', 
                'robots_txt', 'maintenance_mode', 'maintenance_message',
            ];

            $websiteData = $details['website'] ?? [];
            
            foreach ($websiteKeys as $key) {
                if ($request->has($key)) {
                    $value = $request->input($key);
                    // Handle boolean values
                    if (in_array($key, ['features_enabled', 'services_enabled', 'stats_enabled', 'about_enabled', 'testimonials_enabled', 'contact_enabled', 'hero_show_tracking_widget', 'maintenance_mode'])) {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    }
                    $websiteData[$key] = $value;
                }
            }
            
            $details['website'] = $websiteData;
            $this->saveSettings($settings, $details);

            // Clear cache
            Cache::forget('website_settings');
            SystemSettings::flush();

            return response()->json([
                'success' => true,
                'message' => 'Website settings saved successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update website settings', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Clear application cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            SystemSettings::flush();

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Export settings as JSON
     */
    public function export()
    {
        try {
            [$settings, $details] = $this->getSettingsWithDetails();
            
            $export = [
                'exported_at' => now()->toIso8601String(),
                'app_name' => $settings->name,
                'currency' => $settings->currency,
                'settings' => $details,
            ];

            return response()->json($export)
                ->header('Content-Disposition', 'attachment; filename=settings_' . date('Y-m-d') . '.json');
        } catch (\Exception $e) {
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}
