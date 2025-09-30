<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class AuditSidebar extends Command
{
    protected $signature = 'audit:sidebar';

    protected $description = 'Audit admin_nav config for missing routes and policy mappings';

    public function handle(): int
    {
        $config = config('admin_nav.buckets', []);
        $flat = [];
        foreach ($config as $bucketKey => $bucket) {
            foreach (($bucket['children'] ?? []) as $item) {
                $this->flatten($item, $flat, $bucketKey);
            }
        }

        $lines = [];
        foreach ($flat as $row) {
            $route = $row['route'] ?? null;
            $model = $row['model'] ?? null;
            $problems = [];
            if ($route && ! Route::has($route)) {
                $problems[] = "missing-route:$route";
            }
            if ($model && Gate::getPolicyFor($model) === null) {
                $problems[] = 'missing-policy:'.$model;
            }
            if (! empty($problems)) {
                $lines[] = '['.$row['bucket'].'] '.$row['label'].' => '.implode(', ', $problems);
            }
        }

        $path = storage_path('logs/sidebar_audit.log');
        if (empty($lines)) {
            $lines[] = 'No problems found.';
        }
        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
        $this->info('Sidebar audit written to '.$path);

        return self::SUCCESS;
    }

    private function flatten(array $item, array &$flat, string $bucketKey): void
    {
        $label = $item['label_trans_key'] ?? $item['label'] ?? ($item['route'] ?? '');
        $flat[] = [
            'bucket' => $bucketKey,
            'label' => is_string($label) ? $label : json_encode($label),
            'route' => $item['route'] ?? null,
            'model' => $item['model'] ?? null,
        ];
        foreach (($item['children'] ?? []) as $child) {
            $this->flatten($child, $flat, $bucketKey);
        }
    }
}
