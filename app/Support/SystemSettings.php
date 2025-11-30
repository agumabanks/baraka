<?php

namespace App\Support;

use App\Repositories\GeneralSettings\GeneralSettingsRepository;
use Illuminate\Support\Facades\Cache;

class SystemSettings
{
    private const CACHE_KEY = 'system:settings';
    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Get all settings as a snapshot array
     */
    public static function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            try {
                $repo = app(GeneralSettingsRepository::class);
                $settings = $repo->all();
                $details = is_array($settings->details ?? []) ? $settings->details : [];

                return [
                    'name' => $settings->name ?? config('app.name'),
                    'currency' => $settings->currency ?? 'UGX',
                    'par_track_prefix' => $settings->par_track_prefix ?? 'BRK',
                    'invoice_prefix' => $settings->invoice_prefix ?? 'INV',
                    'details' => $details,
                ];
            } catch (\Throwable $e) {
                report($e);
                return self::defaults();
            }
        });
    }

    /**
     * Get a specific setting value using dot notation
     * Example: SystemSettings::get('operations.sla_express_hours', 4)
     */
    public static function get(string $key, $default = null)
    {
        $snapshot = self::snapshot();
        
        // Check in details first
        $value = data_get($snapshot, "details.{$key}");
        if ($value !== null) {
            return $value;
        }
        
        // Check in root level
        $value = data_get($snapshot, $key);
        if ($value !== null) {
            return $value;
        }
        
        return $default;
    }

    /**
     * Get an entire category of settings
     * Example: SystemSettings::category('operations')
     */
    public static function category(string $category): array
    {
        $snapshot = self::snapshot();
        return data_get($snapshot, "details.{$category}", []);
    }

    /**
     * Check if a boolean setting is enabled
     */
    public static function enabled(string $key): bool
    {
        return filter_var(self::get($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    // =========================================
    // Convenience accessors for common settings
    // =========================================

    public static function defaultCurrency(): string
    {
        return (string) self::get('finance.primary_currency', 
            self::get('finance.default_currency', 'UGX'));
    }

    public static function defaultLocale(): string
    {
        return (string) self::get('localization.default_locale', config('app.locale', 'en'));
    }

    public static function trackingPrefix(): string
    {
        return (string) self::get('operations.tracking_prefix', 
            data_get(self::snapshot(), 'par_track_prefix', 'BRK'));
    }

    public static function invoicePrefix(): string
    {
        return (string) self::get('finance.invoice_prefix',
            data_get(self::snapshot(), 'invoice_prefix', 'INV'));
    }

    public static function vatRate(): float
    {
        return (float) self::get('finance.vat_rate', 18);
    }

    public static function taxEnabled(): bool
    {
        return self::enabled('finance.tax_enabled');
    }

    public static function slaHours(string $level = 'standard'): int
    {
        return (int) self::get("operations.sla_{$level}_hours", match($level) {
            'express' => 4,
            'economy' => 72,
            default => 24,
        });
    }

    public static function codEnabled(): bool
    {
        return self::enabled('operations.cod_enabled');
    }

    public static function codMaxAmount(): float
    {
        return (float) self::get('operations.cod_max_amount', 5000000);
    }

    public static function codFeePercent(): float
    {
        return (float) self::get('operations.cod_fee_percent', 2.5);
    }

    public static function volumetricDivisor(): int
    {
        return (int) self::get('operations.volumetric_divisor', 5000);
    }

    public static function maxDeliveryAttempts(): int
    {
        return (int) self::get('operations.max_delivery_attempts', 3);
    }

    public static function requirePod(): bool
    {
        return self::enabled('operations.require_pod');
    }

    public static function autoGenerateInvoices(): bool
    {
        return self::enabled('operations.auto_generate_invoices');
    }

    public static function sessionTimeout(): int
    {
        return (int) self::get('system.session_timeout', 120);
    }

    public static function maxLoginAttempts(): int
    {
        return (int) self::get('system.max_login_attempts', 5);
    }

    public static function apiRateLimit(): int
    {
        return (int) self::get('system.api_rate_limit', 60);
    }

    public static function debugMode(): bool
    {
        return self::enabled('system.debug_mode') || config('app.debug');
    }

    // =========================================
    // Finance accessors
    // =========================================

    public static function fuelSurcharge(): float
    {
        return (float) self::get('finance.fuel_surcharge', 8);
    }

    public static function insuranceRate(): float
    {
        return (float) self::get('finance.insurance_rate', 1.5);
    }

    public static function minCharge(): float
    {
        return (float) self::get('finance.min_charge', 5000);
    }

    public static function paymentTerms(): int
    {
        return (int) self::get('finance.payment_terms', 30);
    }

    public static function creditEnabled(): bool
    {
        return self::enabled('finance.credit_enabled');
    }

    public static function defaultCreditLimit(): float
    {
        return (float) self::get('finance.default_credit_limit', 1000000);
    }

    // =========================================
    // Currency formatting accessors
    // =========================================

    public static function currencyPosition(): string
    {
        return (string) self::get('finance.currency_position', 'before');
    }

    public static function decimalPlaces(): int
    {
        return (int) self::get('finance.decimal_places', 0);
    }

    public static function thousandSeparator(): string
    {
        return (string) self::get('finance.thousand_separator', ',');
    }

    public static function decimalSeparator(): string
    {
        return self::thousandSeparator() === ',' ? '.' : ',';
    }

    /**
     * Format a number as currency using system settings
     */
    public static function formatCurrency(float $amount, ?string $currency = null): string
    {
        $currency = $currency ?? self::defaultCurrency();
        $decimals = self::decimalPlaces();
        $thousandSep = self::thousandSeparator();
        $decimalSep = self::decimalSeparator();
        $position = self::currencyPosition();

        $formatted = number_format($amount, $decimals, $decimalSep, $thousandSep);

        $symbols = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£',
            'UGX' => 'UGX ', 'KES' => 'KES ', 'TZS' => 'TZS ',
            'RWF' => 'RWF ', 'CDF' => 'CDF ',
        ];
        $symbol = $symbols[$currency] ?? $currency . ' ';

        return $position === 'before' 
            ? $symbol . $formatted 
            : $formatted . ' ' . trim($symbol);
    }

    // =========================================
    // Payment methods accessor
    // =========================================

    /**
     * Get enabled payment methods
     */
    public static function paymentMethods(): array
    {
        $methods = [];
        $available = [
            'cash' => ['icon' => 'cash-coin', 'label' => 'Cash', 'color' => 'green'],
            'mobile_money' => ['icon' => 'phone', 'label' => 'Mobile Money', 'color' => 'yellow'],
            'bank_transfer' => ['icon' => 'bank', 'label' => 'Bank Transfer', 'color' => 'blue'],
            'credit' => ['icon' => 'credit-card', 'label' => 'Credit Account', 'color' => 'purple'],
            'card' => ['icon' => 'credit-card-2-front', 'label' => 'Card', 'color' => 'indigo'],
            'cheque' => ['icon' => 'file-text', 'label' => 'Cheque', 'color' => 'gray'],
        ];

        foreach ($available as $key => $config) {
            if (self::get("finance.payment_{$key}", in_array($key, ['cash', 'mobile_money', 'credit']))) {
                $methods[$key] = $config;
            }
        }

        return $methods;
    }

    /**
     * Check if a specific payment method is enabled
     */
    public static function paymentMethodEnabled(string $method): bool
    {
        return (bool) self::get("finance.payment_{$method}", false);
    }

    // =========================================
    // Notification accessors  
    // =========================================

    public static function emailNotifications(): bool
    {
        return self::enabled('notifications.email_notifications');
    }

    public static function smsNotifications(): bool
    {
        return self::enabled('notifications.sms_notifications');
    }

    public static function pushNotifications(): bool
    {
        return self::enabled('notifications.push_notifications');
    }

    // =========================================
    // Branding accessors
    // =========================================

    public static function companyName(): string
    {
        return (string) self::get('branding.company_name', 
            data_get(self::snapshot(), 'name', config('app.name')));
    }

    public static function primaryColor(): string
    {
        return (string) self::get('branding.primary_color', '#3b82f6');
    }

    public static function theme(): string
    {
        return (string) self::get('branding.theme', 'auto');
    }

    /**
     * Resolve currency for a specific branch (with fallback to system default)
     */
    public static function resolveCurrency(?int $branchId = null): string
    {
        if ($branchId && \Illuminate\Support\Facades\Schema::hasColumn('branches', 'settings')) {
            $settings = \App\Models\Backend\Branch::where('id', $branchId)->value('settings');
            if (is_array($settings) && isset($settings['currency']) && $settings['currency']) {
                return $settings['currency'];
            }
            if (is_string($settings)) {
                $decoded = json_decode($settings, true);
                if (is_array($decoded) && isset($decoded['currency']) && $decoded['currency']) {
                    return $decoded['currency'];
                }
            }
        }

        return self::defaultCurrency();
    }

    /**
     * Clear the settings cache
     */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Default settings structure (fallback when DB unavailable)
     */
    private static function defaults(): array
    {
        return [
            'name' => config('app.name', 'Baraka'),
            'currency' => 'UGX',
            'par_track_prefix' => 'BRK',
            'invoice_prefix' => 'INV',
            'details' => [
                'general' => [
                    'app_name' => config('app.name'),
                    'app_url' => config('app.url'),
                    'support_email' => '',
                    'timezone' => 'Africa/Kampala',
                ],
                'localization' => [
                    'default_locale' => 'en',
                    'date_format' => 'd/m/Y',
                ],
                'operations' => [
                    'auto_tracking_ids' => true,
                    'tracking_prefix' => 'BRK',
                    'require_pod' => true,
                    'sla_express_hours' => 4,
                    'sla_standard_hours' => 24,
                    'sla_economy_hours' => 72,
                    'volumetric_divisor' => 5000,
                    'cod_enabled' => true,
                    'cod_max_amount' => 5000000,
                    'cod_fee_percent' => 2.5,
                    'max_delivery_attempts' => 3,
                    'auto_generate_invoices' => true,
                ],
                'finance' => [
                    'primary_currency' => 'UGX',
                    'tax_enabled' => true,
                    'vat_rate' => 18,
                    'invoice_prefix' => 'INV',
                    'payment_terms' => 30,
                ],
                'system' => [
                    'session_timeout' => 120,
                    'max_login_attempts' => 5,
                    'api_rate_limit' => 60,
                    'debug_mode' => false,
                ],
                'notifications' => [
                    'email' => true,
                    'sms' => false,
                    'push' => true,
                ],
            ],
        ];
    }
}
