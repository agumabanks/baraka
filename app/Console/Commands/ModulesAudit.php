<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ModulesAudit extends Command
{
    protected $signature = 'modules:audit';
    protected $description = 'Audit new admin modules from sidebar and create AUDIT.md';

    public function handle(): int
    {
        $targets = [
            'admin.quotations.', 'admin.contracts.', 'admin.address-book.',
            'admin.kyc.', 'admin.dg.', 'admin.ics2.',
            'admin.awb-stock.', 'admin.manifests.', 'admin.ecmr.', 'admin.linehaul-legs.',
            'admin.sortation.', 'admin.warehouse.',
            'admin.returns.', 'admin.claims.',
            'admin.surcharges.', 'admin.cash-office.', 'admin.fx.',
            'admin.zones.', 'admin.lanes.', 'admin.carriers.', 'admin.carrier-services.',
            'admin.dispatch.', 'admin.whatsapp-templates.', 'admin.edi.', 'admin.observability.', 'admin.exception-tower.', 'admin.gl-export.'
        ];

        // get all routes
        Artisan::call('route:list', ['--json' => true]);
        $routes = collect(json_decode(Artisan::output(), true) ?? []);

        // policies & files
        $policies = $this->getPolicies();

        $lines = [];
        $lines[] = '| Route name prefix | Found route? | Controller? | Policy? | Views? | Migration/Model? | Notes |';
        $lines[] = '|---|---|---|---|---|---|---|';

        foreach ($targets as $prefix) {
            $found = $routes->firstWhere('name', $prefix.'index');
            $controller = $found['action'] ?? '';
            if ($controller && str_contains($controller, '@')) [$controller] = explode('@',$controller,2);
            $controllerFile = $this->classToFile($controller);
            $controllerOk = $controller && File::exists($controllerFile);

            // Derive model from Controller name
            $model = '';
            if ($controller && preg_match('/\\\\([A-Za-z0-9_]+)Controller$/', $controller, $m)) {
                $model = 'App\\Models\\'.$m[1];
            }
            $modelOk = $model && File::exists($this->classToFile($model));
            $policyOk = $model && isset($policies[ltrim($model,'\\\\')]);

            $viewsOk = false;
            if ($model) {
                $name = strtolower($m[1]);
                $viewsOk = $this->searchViews($name);
            }

            $migOk = $this->searchMigrations($model);

            $lines[] = '|'.implode('|', [
                $prefix,
                $found ? '✅' : '⬜',
                $controllerOk ? '✅' : '⬜',
                $policyOk ? '✅' : '⬜',
                $viewsOk ? '✅' : '⬜',
                $migOk ? '✅' : '⬜',
                $found ? '' : 'Missing route(s)'
            ]).'|';
        }

        $md = implode("\n", $lines)."\n";
        File::put(base_path('AUDIT.md'), $md);
        $this->line($md);
        $this->info('Wrote AUDIT.md');
        return self::SUCCESS;
    }

    protected function classToFile(string $class): string
    { return base_path(str_replace('\\', '/', ltrim($class,'\\\\')).'.php'); }

    protected function getPolicies(): array
    {
        $provider = base_path('app/Providers/AuthServiceProvider.php');
        $content = File::exists($provider) ? File::get($provider) : '';
        $map = [];
        if ($content && preg_match('/protected \$policies\s*=\s*\[(.*?)\];/s', $content, $m)) {
            preg_match_all('/(\\\\?[A-Za-z0-9_\\\\\\\\]+)::class\s*=>\s*(\\\\?[A-Za-z0-9_\\\\\\\\]+)::class/', $m[1], $pairs, PREG_SET_ORDER);
            foreach ($pairs as $p) $map[ltrim($p[1],'\\')] = ltrim($p[2],'\\');
        }
        return $map;
    }

    protected function searchViews(string $name): bool
    {
        $dir = resource_path('views/backend/admin');
        if (!File::exists($dir)) return false;
        foreach (File::directories($dir) as $d) {
            if (str_contains(basename($d), $name)) return true;
        }
        return false;
    }

    protected function searchMigrations(?string $modelClass): bool
    {
        if (!$modelClass) return false;
        $name = strtolower(class_basename($modelClass));
        foreach (File::files(database_path('migrations')) as $f) {
            if (str_contains($f->getFilename(), $name) || str_contains($f->getFilename(), $name.'s')) return true;
        }
        return false;
    }
}
