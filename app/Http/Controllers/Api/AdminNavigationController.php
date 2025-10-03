<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Nav;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminNavigationController extends Controller
{
    public function __invoke(Request $request)
    {
        $buckets = config('admin_nav.buckets', []);
        $navigation = [];

        foreach ($buckets as $bucketKey => $bucket) {
            $items = $bucket['children'] ?? [];
            $transformedItems = $this->transformItems($items);

            if (empty($transformedItems)) {
                continue;
            }

            $navigation[] = [
                'id' => (string) $bucketKey,
                'label' => __($bucket['label_trans_key'] ?? Str::title(str_replace('_', ' ', $bucketKey))),
                'items' => $transformedItems,
                'visible' => true,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'logoUrl' => optional(settings())->logo_image ?? asset('images/default/logo1.png'),
                'appName' => config('app.name', 'Baraka'),
                'buckets' => $navigation,
            ],
        ]);
    }

    private function transformItems(array $items): array
    {
        $output = [];

        foreach ($items as $item) {
            $children = $item['children'] ?? [];
            $childItems = [];
            if (!empty($children) && is_array($children)) {
                $childItems = $this->transformItems($children);
            }

            $visible = Nav::canShowBySignature($item['permission_check'] ?? null);
            if (!$visible && empty($childItems)) {
                continue;
            }

            $label = __($item['label_trans_key'] ?? ($item['label'] ?? Str::title(str_replace('_', ' ', $item['key'] ?? 'item'))));
            $id = (string) ($item['key'] ?? Str::slug($label));

            $url = null;
            if (!empty($item['route'])) {
                try {
                    $url = route($item['route'], [], false);
                } catch (\Throwable $e) {
                    $url = null;
                }
            } elseif (!empty($item['url'])) {
                $url = $item['url'];
            }

            $entry = [
                'id' => $id,
                'label' => $label,
                'icon' => $item['icon'] ?? 'fas fa-circle',
                'path' => $this->resolveSpaPath($id, $url, !empty($childItems)),
                'url' => $url,
                'visible' => $visible,
            ];

            // Add expanded property if it exists in config
            if (array_key_exists('expanded', $item)) {
                $entry['expanded'] = (bool) $item['expanded'];
            }

            if (!empty($childItems)) {
                $entry['children'] = $childItems;
            }

            $output[] = $entry;
        }

        return $output;
    }

    private function resolveSpaPath(string $id, ?string $url, bool $hasChildren): ?string
    {
        if ($hasChildren) {
            return null;
        }

        if (empty($url)) {
            return '/' . ltrim($id, '/');
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return '/' . ltrim($id, '/');
        }

        $normalized = trim($path, '/');
        if (Str::startsWith($normalized, 'admin/')) {
            $normalized = substr($normalized, strlen('admin/'));
        }

        if ($normalized === '') {
            return '/';
        }

        return '/' . $normalized;
    }
}
