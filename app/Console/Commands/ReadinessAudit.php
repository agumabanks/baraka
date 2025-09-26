<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ReadinessAudit extends Command
{
    protected $signature = 'readiness:audit';
    protected $description = 'Audit repository against DHL-style ERP checklist and emit readiness.json';

    public function handle(): int
    {
        $this->info('Running repository audit...');

        $routeJson = $this->getRoutes();
        $policies = $this->getPolicies();

        $checks = $this->checks();
        $results = [];

        foreach ($checks as $key => $artifacts) {
            $score = [
                'model'      => $this->hasModel($artifacts['model'] ?? null),
                'migration'  => $this->hasMigration($artifacts['migration'] ?? null),
                'controller' => $this->hasController($artifacts['controller'] ?? null),
                'route'      => $this->hasRoute($routeJson, $artifacts['route'] ?? null),
                'policy'     => $this->hasPolicy($policies, $artifacts['model'] ?? null),
                'view'       => $this->hasView($artifacts['view'] ?? null),
                'test'       => $this->hasTest($artifacts['test'] ?? null),
            ];

            // Determine status
            if ($score['model'] && $score['migration'] && $score['controller'] && $score['route'] && $score['policy'] && $score['test']) {
                $status = '✅';
            } elseif ($score['model'] || $score['migration'] || $score['controller'] || $score['route'] || $score['policy'] || $score['view']) {
                $status = '🚧';
            } else {
                $status = '⬜';
            }

            $results[$key] = $status;
        }

        // Persist readiness.json
        $path = storage_path('app/readiness.json');
        File::put($path, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Render Markdown table
        $this->line($this->renderMarkdown($results));

        $this->info("Wrote: {$path}");
        return self::SUCCESS;
    }

    protected function getRoutes(): array
    {
        try {
            $output = Artisan::call('route:list', ['--json' => true]);
            $json = Artisan::output();
            $routes = json_decode($json, true) ?: [];
        } catch (\Throwable $e) {
            $routes = [];
        }
        return $routes;
    }

    protected function getPolicies(): array
    {
        $provider = base_path('app/Providers/AuthServiceProvider.php');
        $content = File::exists($provider) ? File::get($provider) : '';
        $map = [];
        if ($content) {
            if (preg_match('/protected \$policies\s*=\s*\[(.*?)\];/s', $content, $m)) {
                $arr = $m[1];
                preg_match_all('/(\\\\?[A-Za-z0-9_\\\\\\\\]+)::class\s*=>\s*(\\\\?[A-Za-z0-9_\\\\\\\\]+)::class/', $arr, $pairs, PREG_SET_ORDER);
                foreach ($pairs as $p) {
                    $model = trim($p[1], '\\');
                    $policy = trim($p[2], '\\');
                    $map[$model] = $policy;
                }
            }
        }
        return $map;
    }

    protected function hasModel(?string $class): bool
    {
        if (!$class) return false;
        $path = base_path('app/Models/' . basename(str_replace('App\\Models\\', '', $class)) . '.php');
        return File::exists($path);
    }

    protected function hasMigration($needle): bool
    {
        if (!$needle) return false;
        $files = File::files(database_path('migrations'));
        foreach ($files as $f) {
            if (stripos($f->getFilename(), (string)$needle) !== false) return true;
        }
        return false;
    }

    protected function hasController(?string $class): bool
    {
        if (!$class) return false;
        $classOnly = basename(str_replace('App\\Http\\Controllers\\', '', $class));
        $paths = [
            base_path('app/Http/Controllers/' . $classOnly . '.php'),
            base_path('app/Http/Controllers/Admin/' . $classOnly . '.php'),
            base_path('app/Http/Controllers/Api/' . $classOnly . '.php'),
        ];
        foreach ($paths as $p) if (File::exists($p)) return true;
        return false;
    }

    protected function hasView($needle): bool
    {
        if (!$needle) return false;
        $dir = resource_path('views');
        $files = collect(File::allFiles($dir))->map->getPathname();
        foreach ($files as $f) if (stripos($f, (string)$needle) !== false) return true;
        return false;
    }

    protected function hasRoute(array $routes, ?string $needle): bool
    {
        if (!$needle) return false;
        foreach ($routes as $r) {
            if (isset($r['uri']) && stripos($r['uri'], $needle) !== false) return true;
            if (isset($r['name']) && stripos($r['name'] ?? '', $needle) !== false) return true;
        }
        return false;
    }

    protected function hasPolicy(array $policies, ?string $modelClass): bool
    {
        if (!$modelClass) return false;
        $key = ltrim($modelClass, '\\');
        return isset($policies[$key]);
    }

    protected function hasTest($needle): bool
    {
        if (!$needle) return false;
        $dir = base_path('tests');
        if (!File::exists($dir)) return false;
        foreach (File::allFiles($dir) as $f) {
            if (stripos($f->getFilename(), (string)$needle) !== false) return true;
            if (stripos($f->getPathname(), (string)$needle) !== false) return true;
        }
        return false;
    }

    protected function checks(): array
    {
        return [
            // 1) Foundation & Identity
            'Foundation.ZonesLanes' => [
                'model' => 'App\\Models\\Zone',
                'migration' => 'zones',
                'controller' => 'App\\Http\\Controllers\\Admin\\ZoneController',
                'route' => 'admin/zones',
                'view' => 'zones',
                'test' => 'Zone',
            ],
            'Foundation.CarrierService' => [
                'model' => 'App\\Models\\Carrier',
                'migration' => 'carriers',
                'controller' => 'App\\Http\\Controllers\\Admin\\CarrierController',
                'route' => 'admin/carriers',
                'view' => 'carriers',
                'test' => 'Carrier',
            ],

            // 2) Commercial (Sales & Customer)
            'Commercial.AddressBook' => [
                'model' => 'App\\Models\\AddressBook',
                'migration' => 'address_books',
                'controller' => 'App\\Http\\Controllers\\Admin\\AddressBookController',
                'route' => 'admin/address-book',
                'view' => 'backend/admin/address_book',
                'test' => 'AddressBook',
            ],
            'Commercial.Quotations' => [
                'model' => 'App\\Models\\Quotation',
                'migration' => 'quotations',
                'controller' => 'App\\Http\\Controllers\\Admin\\QuotationController',
                'route' => 'admin/quotations',
                'view' => 'backend/admin/quotations',
                'test' => 'QuotationFeatureTest',
            ],
            'Commercial.ContractsSLAs' => [
                'model' => 'App\\Models\\Contract',
                'migration' => 'contracts',
                'controller' => 'App\\Http\\Controllers\\Admin\\ContractController',
                'route' => 'admin/contracts',
                'view' => 'backend/admin/contracts',
                'test' => 'Contract',
            ],
            'Commercial.KycDps' => [
                'model' => 'App\\Models\\KycRecord',
                'migration' => 'kyc_records',
                'controller' => 'App\\Http\\Controllers\\Admin\\KycController',
                'route' => 'admin/kyc',
                'view' => 'backend/admin/kyc',
                'test' => 'Kyc',
            ],
            'Commercial.CustomerPortal' => [
                'model' => null,
                'migration' => null,
                'controller' => null,
                'route' => 'portal',
                'view' => 'portal',
                'test' => 'Portal',
            ],

            // 3) Booking & Pickup
            'Booking.BulkCsvBooking' => [
                'model' => null,
                'migration' => 'parcel',
                'controller' => 'App\\Http\\Controllers\\Backend\\ParcelController',
                'route' => 'parcel/file-import',
                'view' => 'parcel/import',
                'test' => 'ParcelImport',
            ],
            'Booking.PickupDispatchBoard' => [
                'model' => null,
                'migration' => null,
                'controller' => 'App\\Http\\Controllers\\Admin\\DispatchController',
                'route' => 'admin/dispatch',
                'view' => 'backend/admin/dispatch',
                'test' => 'Dispatch',
            ],

            // 4) Hub Ops
            'HubOps.SortationStations' => [
                'model' => 'App\\Models\\SortationBin',
                'migration' => 'sortation_bins',
                'controller' => 'App\\Http\\Controllers\\Admin\\SortationController',
                'route' => 'admin/sortation',
                'view' => 'backend/admin/sortation',
                'test' => 'Sortation',
            ],
            'HubOps.LightWms' => [
                'model' => 'App\\Models\\WhLocation',
                'migration' => 'wh_locations',
                'controller' => 'App\\Http\\Controllers\\Admin\\WarehouseController',
                'route' => 'admin/warehouse',
                'view' => 'backend/admin/warehouse',
                'test' => 'Warehouse',
            ],

            // 5) Linehaul
            'Linehaul.AwbStock' => [
                'model' => 'App\\Models\\AwbStock',
                'migration' => 'awb_stocks',
                'controller' => 'App\\Http\\Controllers\\Admin\\AwbStockController',
                'route' => 'admin/awb-stock',
                'view' => 'backend/admin/awb',
                'test' => 'Awb',
            ],
            'Linehaul.Manifests' => [
                'model' => 'App\\Models\\Manifest',
                'migration' => 'manifests',
                'controller' => 'App\\Http\\Controllers\\Admin\\ManifestController',
                'route' => 'admin/manifests',
                'view' => 'backend/admin/manifests',
                'test' => 'Manifest',
            ],
            'Linehaul.eCmr' => [
                'model' => 'App\\Models\\Ecmr',
                'migration' => 'ecmrs',
                'controller' => 'App\\Http\\Controllers\\Admin\\EcmrController',
                'route' => 'admin/ecmr',
                'view' => 'backend/admin/ecmr',
                'test' => 'Ecmr',
            ],
            'Linehaul.ICS2' => [
                'model' => 'App\\Models\\Ics2Filing',
                'migration' => 'ics2_filings',
                'controller' => 'App\\Http\\Controllers\\Admin\\Ics2MonitorController',
                'route' => 'admin/ics2',
                'view' => 'backend/admin/ics2',
                'test' => 'Ics2',
            ],

            // 6) Last-Mile
            'LastMile.ReturnsRto' => [
                'model' => 'App\\Models\\ReturnOrder',
                'migration' => 'return_orders',
                'controller' => 'App\\Http\\Controllers\\Admin\\ReturnController',
                'route' => 'admin/returns',
                'view' => 'backend/admin/returns',
                'test' => 'Return',
            ],
            'LastMile.Claims' => [
                'model' => 'App\\Models\\Claim',
                'migration' => 'claims',
                'controller' => 'App\\Http\\Controllers\\Admin\\ClaimController',
                'route' => 'admin/claims',
                'view' => 'backend/admin/claims',
                'test' => 'Claim',
            ],

            // 7) Customs & Compliance
            'Customs.DangerousGoods' => [
                'model' => 'App\\Models\\DangerousGood',
                'migration' => 'dangerous_goods',
                'controller' => 'App\\Http\\Controllers\\Admin\\DangerousGoodsController',
                'route' => 'admin/dg',
                'view' => 'backend/admin/dg',
                'test' => 'Dangerous',
            ],
            'Compliance.SanctionsStoredAuditable' => [
                'model' => 'App\\Models\\DpsScreening',
                'migration' => 'dps_screenings',
                'controller' => 'App\\Http\\Controllers\\Admin\\DeniedPartyController',
                'route' => 'admin/denied-party',
                'view' => 'backend/admin/denied-party',
                'test' => 'Dps',
            ],

            // 8) Billing & Finance
            'Billing.SurchargeEngine' => [
                'model' => 'App\\Models\\SurchargeRule',
                'migration' => 'surcharge_rules',
                'controller' => 'App\\Http\\Controllers\\Admin\\SurchargeRuleController',
                'route' => 'admin/surcharges',
                'view' => 'backend/admin/surcharges',
                'test' => 'RatingServiceTest',
            ],
            'Billing.DimensionalWeight' => [
                'model' => null,
                'migration' => null,
                'controller' => null,
                'route' => 'admin/lanes',
                'view' => 'backend/admin/lanes',
                'test' => 'RatingServiceTest',
            ],
            'Billing.CashOffice' => [
                'model' => 'App\\Models\\CashOffice',
                'migration' => 'cash_office_days',
                'controller' => 'App\\Http\\Controllers\\Admin\\CashOfficeController',
                'route' => 'admin/cash-office',
                'view' => 'backend/admin/cash_office',
                'test' => 'CashOffice',
            ],
            'Billing.MultiCurrencyFx' => [
                'model' => 'App\\Models\\FxRate',
                'migration' => 'fx_rates',
                'controller' => 'App\\Http\\Controllers\\Admin\\FxRateController',
                'route' => 'admin/fx',
                'view' => 'backend/admin/fx',
                'test' => 'FxRate',
            ],
            'Billing.GlExport' => [
                'model' => null,
                'migration' => null,
                'controller' => 'App\\Http\\Controllers\\Admin\\GlExportController',
                'route' => 'admin/gl-export',
                'view' => 'backend/admin/gl_export',
                'test' => 'GlExport',
            ],

            // 9) Notifications & CX
            'CX.WhatsAppTemplates' => [
                'model' => 'App\\Models\\WhatsappTemplate',
                'migration' => 'whatsapp_templates',
                'controller' => 'App\\Http\\Controllers\\Admin\\WhatsappTemplateController',
                'route' => 'admin/whatsapp-templates',
                'view' => 'backend/admin/whatsapp_templates',
                'test' => 'WhatsappTemplate',
            ],
            'CX.PublicTrackingWebhooks' => [
                'model' => 'App\\Models\\Webhook',
                'migration' => 'webhooks',
                'controller' => 'App\\Http\\Controllers\\Admin\\WebhookController',
                'route' => 'track',
                'view' => 'frontend/tracking',
                'test' => 'Tracking',
            ],

            // 10) Integrations & Tech Ops
            'Integrations.EDIConnectors' => [
                'model' => 'App\\Models\\EdiProvider',
                'migration' => 'edi_providers',
                'controller' => 'App\\Http\\Controllers\\Admin\\EdiController',
                'route' => 'admin/edi',
                'view' => 'backend/admin/edi',
                'test' => 'Edi',
            ],
            'TechOps.Observability' => [
                'model' => null,
                'migration' => null,
                'controller' => 'App\\Http\\Controllers\\Admin\\ObservabilityController',
                'route' => 'admin/observability',
                'view' => 'backend/admin/observability',
                'test' => 'Observability',
            ],

            // 11) Analytics & Control
            'Analytics.ExceptionTower' => [
                'model' => null,
                'migration' => null,
                'controller' => 'App\\Http\\Controllers\\Admin\\ExceptionTowerController',
                'route' => 'admin/exception-tower',
                'view' => 'backend/admin/exception_tower',
                'test' => 'ExceptionTower',
            ],
            'Analytics.CSATNPS' => [
                'model' => 'App\\Models\\Survey',
                'migration' => 'surveys',
                'controller' => 'App\\Http\\Controllers\\Admin\\SurveyController',
                'route' => 'admin/surveys',
                'view' => 'backend/admin/surveys',
                'test' => 'Survey',
            ],
        ];
    }

    protected function renderMarkdown(array $results): string
    {
        $lines = [];
        $lines[] = '| Module | Status |';
        $lines[] = '|---|---|';
        foreach ($results as $k => $v) {
            $lines[] = '|' . $k . '|' . $v . '|';
        }
        return implode("\n", $lines);
    }
}

