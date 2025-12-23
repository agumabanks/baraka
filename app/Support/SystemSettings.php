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
                    'logo_image' => $settings->logo_image ?? null,
                    'light_logo_image' => $settings->light_logo_image ?? null,
                    'favicon_image' => $settings->favicon_image ?? null,
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

    /**
     * Locale selection behavior.
     * - per_user: each authenticated user/customer can choose their locale (cookie/session fallback for guests)
     * - global: force defaultLocale() for everyone (admins included)
     */
    public static function localizationMode(): string
    {
        $mode = (string) self::get('localization.mode', 'per_user');
        return in_array($mode, ['per_user', 'global'], true) ? $mode : 'per_user';
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
    // Pricing - Service Level Multipliers
    // =========================================

    /**
     * Get service level multipliers
     * Defaults per POS hardening plan:
     * - economy: 0.8x, standard: 1.0x, express: 1.5x, priority: 2.0x
     */
    public static function serviceLevelMultipliers(): array
    {
        return (array) self::get('pricing.service_multipliers', [
            'economy' => 0.80,
            'standard' => 1.00,
            'express' => 1.50,
            'priority' => 2.00,
        ]);
    }

    public static function serviceLevelMultiplier(string $level): float
    {
        $multipliers = self::serviceLevelMultipliers();
        return (float) ($multipliers[$level] ?? 1.0);
    }

    // =========================================
    // Pricing - Weight Band Adjustments
    // =========================================

    /**
     * Get weight band adjustments
     * Defaults per POS hardening plan:
     * - 0-5kg: +20% (small parcel premium)
     * - 5-20kg: base rate (0%)
     * - 20-100kg: -5% (volume discount)
     * - 100kg+: -10% (bulk discount)
     */
    public static function weightBandAdjustments(): array
    {
        return (array) self::get('pricing.weight_bands', [
            ['min' => 0, 'max' => 5, 'adjustment' => 0.20, 'label' => 'Small parcel premium'],
            ['min' => 5, 'max' => 20, 'adjustment' => 0.00, 'label' => 'Base rate'],
            ['min' => 20, 'max' => 100, 'adjustment' => -0.05, 'label' => 'Volume discount'],
            ['min' => 100, 'max' => null, 'adjustment' => -0.10, 'label' => 'Bulk discount'],
        ]);
    }

    /**
     * Get weight band adjustment for a specific weight
     */
    public static function weightBandAdjustment(float $weight): float
    {
        $bands = self::weightBandAdjustments();
        foreach ($bands as $band) {
            $min = $band['min'] ?? 0;
            $max = $band['max'] ?? PHP_FLOAT_MAX;
            if ($weight > $min && ($max === null || $weight <= $max)) {
                return (float) ($band['adjustment'] ?? 0);
            }
        }
        return 0.0;
    }

    // =========================================
    // Service Level Constraints
    // =========================================

    /**
     * Get service level constraints (max weight, transit days)
     */
    public static function serviceLevelConstraints(): array
    {
        return (array) self::get('pricing.service_constraints', [
            'economy' => ['max_weight' => 1000, 'transit_days' => '7-10 days'],
            'standard' => ['max_weight' => 500, 'transit_days' => '5-7 days'],
            'express' => ['max_weight' => 100, 'transit_days' => '3-5 days'],
            'priority' => ['max_weight' => 50, 'transit_days' => '1-3 days'],
        ]);
    }

    public static function serviceMaxWeight(string $level): float
    {
        $constraints = self::serviceLevelConstraints();
        return (float) ($constraints[$level]['max_weight'] ?? 1000);
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

    public static function logo(): string
    {
        $logo = self::get('branding.logo_url');
        if (is_string($logo) && $logo !== '') {
            return $logo;
        }

        $legacy = data_get(self::snapshot(), 'logo_image');
        if (is_string($legacy) && $legacy !== '') {
            return $legacy;
        }

        return '/images/default/logo1.png';
    }

    public static function favicon(): string
    {
        $favicon = self::get('branding.favicon_url');
        if (is_string($favicon) && $favicon !== '') {
            return $favicon;
        }

        $legacy = data_get(self::snapshot(), 'favicon_image');
        if (is_string($legacy) && $legacy !== '') {
            return $legacy;
        }

        return '/images/default/favicon.png';
    }

    public static function adminLogo(): string
    {
        $logo = self::get('branding.logos.admin');
        if (is_string($logo) && $logo !== '') {
            return $logo;
        }

        $main = self::get('branding.logo_url');
        if (is_string($main) && $main !== '') {
            return $main;
        }

        $legacy = data_get(self::snapshot(), 'light_logo_image');
        if (is_string($legacy) && $legacy !== '') {
            return $legacy;
        }

        return '/images/default/light-logo1.png';
    }

    public static function branchLogo(): string
    {
        $logo = self::get('branding.logos.branch');
        if (is_string($logo) && $logo !== '') {
            return $logo;
        }

        $main = self::get('branding.logo_url');
        if (is_string($main) && $main !== '') {
            return $main;
        }

        $legacy = data_get(self::snapshot(), 'light_logo_image');
        if (is_string($legacy) && $legacy !== '') {
            return $legacy;
        }

        return '/images/default/light-logo1.png';
    }

    public static function clientPortalLogo(): string
    {
        $logo = self::get('branding.logos.client');
        if (is_string($logo) && $logo !== '') {
            return $logo;
        }

        $main = self::get('branding.logo_url');
        if (is_string($main) && $main !== '') {
            return $main;
        }

        $legacy = data_get(self::snapshot(), 'light_logo_image');
        if (is_string($legacy) && $legacy !== '') {
            return $legacy;
        }

        return '/images/default/light-logo1.png';
    }

    public static function landingLogo(): string
    {
        $logo = self::get('branding.logos.landing');
        if (is_string($logo) && $logo !== '') {
            return $logo;
        }

        return self::logo();
    }

    public static function printLogo(): string
    {
        $logo = self::get('branding.logos.print');
        if (is_string($logo) && $logo !== '') {
            return $logo;
        }

        return self::logo();
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
     * Get website settings with defaults
     */
    public static function website(): array
    {
        $snapshot = self::snapshot();
        $saved = data_get($snapshot, 'details.website', []);
        
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
        
        return array_merge($defaults, $saved);
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
