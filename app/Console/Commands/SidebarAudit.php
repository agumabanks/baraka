<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SidebarAudit extends Command
{
    protected $signature = 'sidebar:audit';
    protected $description = 'Audit sidebar items and scaffold checklist for routes/controllers/models/policies/views/lang/tests';

    public function handle(): int
    {
        $sidebar = base_path('resources/views/backend/partials/sidebar.blade.php');
        if (!File::exists($sidebar)) {
            $this->error('Sidebar not found: '.$sidebar);
            return self::FAILURE;
        }
        $html = File::get($sidebar);

        // Extract route names from route('name') occurrences
        preg_match_all("/route\(\'([^\']+)\'\)/", $html, $m);
        $routeNames = array_values(array_unique($m[1] ?? []));

        // Load route list once
        $routeJson = $this->getRoutes();
        $routesByName = [];
        foreach ($routeJson as $r) {
            $routesByName[$r['name'] ?? ''] = $r;
        }

        // Policies map
        $policies = $this->getPolicies();

        $rows = [];
        foreach ($routeNames as $name) {
            $action = $routesByName[$name]['action'] ?? '';
            $controllerClass = '';
            if ($action && is_string($action)) {
                // e.g. App\Http\Controllers\Admin\FooController@index
                if (str_contains($action, '@')) {
                    [$controllerClass] = explode('@', $action, 2);
                }
            }
            $controllerExists = $this->classToFileExists($controllerClass);

            // Guess model from Admin\\FooController => App\\Models\\Foo
            $modelClass = '';
            if ($controllerClass && preg_match('/\\\\([A-Za-z0-9_]+)Controller$/', $controllerClass, $mm)) {
                $modelClass = 'App\\Models\\' . $mm[1];
            }
            $modelExists = $this->classToFileExists($modelClass);
            $policyExists = isset($policies[ltrim(($modelClass ?? ''), '\\')]);

            // Check basic views by searching for a directory containing the model name
            $viewOk = $this->viewsExistFor($modelClass);

            // Lang: a simple presence check for en/menus.php
            $langOk = File::exists(base_path('lang/en/menus.php'));

            // Tests: search tests dir for model or controller keyword
            $testsOk = $this->testsExistFor([$modelClass, $controllerClass]);

            // Overall status considers navigability (route + controller) as success
            $status = ($routesByName[$name] ?? null) && ($controllerExists || $controllerClass === '') ? '✅' : '⬜';

            $rows[] = [
                'route' => $name,
                'controller' => $controllerClass ?: '-',
                'model' => $modelClass ?: '-',
                'policy' => $policyExists ? '✅' : '⬜',
                'views' => $viewOk ? '✅' : '⬜',
                'lang' => $langOk ? '✅' : '⬜',
                'tests' => $testsOk ? '✅' : '⬜',
                'status' => $status,
            ];
        }

        // Build Markdown
        $out = [];
        $out[] = '| Route | Model | Policy | Controller | Views | Lang | Tests | Status |';
        $out[] = '|---|---|---|---|---|---|---|---|';
        foreach ($rows as $r) {
            $out[] = '|' . implode('|', [
                $r['route'],
                $r['model'],
                $r['policy'],
                $r['controller'],
                $r['views'],
                $r['lang'],
                $r['tests'],
                $r['status'],
            ]) . '|';
        }
        $md = implode("\n", $out) . "\n";

        // Write to storage/logs
        $path = storage_path('logs/sidebar_implementation_report.md');
        File::put($path, $md);
        $this->line($md);
        $this->info('Wrote report: ' . $path);
        return self::SUCCESS;
    }

    protected function getRoutes(): array
    {
        try {
            Artisan::call('route:list', ['--json' => true]);
            $json = Artisan::output();
            return json_decode($json, true) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
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

    protected function classToFileExists(?string $class): bool
    {
        if (!$class) return false;
        $class = ltrim($class, '\\');
        $relative = base_path(str_replace('\\', '/', $class) . '.php');
        return File::exists($relative);
    }

    protected function viewsExistFor(?string $modelClass): bool
    {
        if (!$modelClass) return false;
        if (!preg_match('/\\\\([A-Za-z0-9_]+)$/', $modelClass, $m)) return false;
        $name = strtolower($m[1]);
        $dir = resource_path('views');
        if (!File::exists($dir)) return false;
        foreach (File::allFiles($dir) as $f) {
            $path = $f->getPathname();
            if (preg_match('/backend\/(admin\/)?' . preg_quote($name, '/') . '\//', str_replace('\\','/',$path))) {
                return true;
            }
        }
        return false;
    }

    protected function testsExistFor(array $needles): bool
    {
        $dir = base_path('tests');
        if (!File::exists($dir)) return false;
        foreach (File::allFiles($dir) as $f) {
            $c = File::get($f->getPathname());
            foreach ($needles as $n) {
                if ($n && str_contains($c, trim($n, '\\'))) return true;
            }
        }
        return false;
    }
}
