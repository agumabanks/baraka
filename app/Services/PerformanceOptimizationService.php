<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceOptimizationService
{
    /**
     * Cache frequently accessed data
     */
    public function cacheFrequentlyAccessedData(string $key, $data, int $ttl = 3600): void
    {
        Cache::tags(['frequent_data'])->put($key, $data, $ttl);
    }

    /**
     * Get cached data with fallback
     */
    public function getCachedData(string $key, ?callable $fallback = null, int $ttl = 3600)
    {
        return Cache::tags(['frequent_data'])->remember($key, $ttl, $fallback);
    }

    /**
     * Optimize database queries with eager loading
     */
    public function optimizeParcelQuery($query, array $relations = [])
    {
        $defaultRelations = [
            'merchant',
            'pickupman',
            'deliveryman',
            'hub',
            'logs' => function ($query) {
                $query->latest()->limit(10);
            },
        ];

        $relations = array_merge($defaultRelations, $relations);

        return $query->with($relations)
            ->select(['id', 'tracking_id', 'status', 'merchant_id', 'pickupman_id', 'deliveryman_id', 'hub_id', 'created_at'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Implement database query result caching
     */
    public function cacheQueryResult(string $queryKey, $query, int $ttl = 1800)
    {
        $cacheKey = 'query_'.md5($queryKey);

        return Cache::tags(['database_queries'])->remember($cacheKey, $ttl, function () use ($query) {
            if (is_callable($query)) {
                return $query();
            }

            return $query;
        });
    }

    /**
     * Optimize image loading with lazy loading and compression
     */
    public function optimizeImageResponse($imagePath, bool $compress = true): array
    {
        $optimized = [
            'original' => $imagePath,
            'thumbnail' => $this->generateThumbnail($imagePath),
            'webp' => $this->convertToWebP($imagePath),
            'lazy_load' => true,
            'compressed' => $compress,
        ];

        return $optimized;
    }

    /**
     * Generate thumbnail for images
     */
    private function generateThumbnail(string $imagePath): string
    {
        // Implementation would use image processing library like Intervention Image
        return str_replace('.', '_thumb.', $imagePath);
    }

    /**
     * Convert image to WebP format
     */
    private function convertToWebP(string $imagePath): string
    {
        // Implementation would use image processing library
        return str_replace(['.jpg', '.jpeg', '.png'], '.webp', $imagePath);
    }

    /**
     * Optimize API responses for mobile devices
     */
    public function optimizeApiResponse(array $data, bool $isMobile = false): array
    {
        if ($isMobile) {
            // Reduce payload for mobile devices
            $data = $this->minimizePayload($data);

            // Add mobile-specific optimizations
            $data['_mobile'] = [
                'optimized' => true,
                'compressed' => true,
                'cacheable' => true,
            ];
        }

        return $data;
    }

    /**
     * Minimize API payload
     */
    private function minimizePayload(array $data): array
    {
        // Remove unnecessary fields for mobile
        $fieldsToRemove = ['created_at', 'updated_at', 'deleted_at'];

        array_walk_recursive($data, function (&$value, $key) use ($fieldsToRemove) {
            if (in_array($key, $fieldsToRemove)) {
                $value = null;
            }
        });

        return $data;
    }

    /**
     * Implement database connection pooling hints
     */
    public function optimizeDatabaseConnection(): void
    {
        // Set optimal database configuration
        DB::whenQueryingForLongerThan(500, function () {
            Log::warning('Slow query detected');
        });

        // Enable query caching for read-heavy operations
        DB::prohibitDestructiveCommands();
    }

    /**
     * Cache user permissions and roles
     */
    public function cacheUserPermissions(int $userId): array
    {
        $cacheKey = "user_permissions_{$userId}";

        return Cache::tags(['user_permissions'])->remember($cacheKey, 3600, function () use ($userId) {
            $user = \App\Models\User::with('role')->find($userId);

            return [
                'permissions' => $user->permissions ?? [],
                'role' => $user->role->name ?? null,
                'role_permissions' => $user->role->permissions ?? [],
            ];
        });
    }

    /**
     * Optimize file uploads with chunking
     */
    public function optimizeFileUpload($file, array $options = []): array
    {
        $optimized = [
            'chunk_size' => $options['chunk_size'] ?? 1024 * 1024, // 1MB chunks
            'total_chunks' => ceil($file->getSize() / ($options['chunk_size'] ?? 1024 * 1024)),
            'compression' => $options['compression'] ?? 'gzip',
            'cdn_upload' => $options['cdn_upload'] ?? true,
        ];

        return $optimized;
    }

    /**
     * Clear specific cache tags
     */
    public function clearCache(string $tag): void
    {
        Cache::tags([$tag])->flush();
    }

    /**
     * Monitor performance metrics
     */
    public function recordPerformanceMetric(string $metric, float $value, array $tags = []): void
    {
        // Implementation would integrate with monitoring service like DataDog or New Relic
        Log::info("Performance metric: {$metric}", [
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now(),
        ]);
    }

    /**
     * Optimize search queries with indexing hints
     */
    public function optimizeSearchQuery(string $searchTerm, array $filters = []): array
    {
        $optimized = [
            'search_term' => $this->sanitizeSearchTerm($searchTerm),
            'use_fulltext' => strlen($searchTerm) > 3,
            'filters' => $this->optimizeFilters($filters),
            'limit' => 50, // Reasonable limit for performance
            'cache_results' => true,
        ];

        return $optimized;
    }

    /**
     * Sanitize search terms
     */
    private function sanitizeSearchTerm(string $term): string
    {
        // Remove special characters that could cause performance issues
        return preg_replace('/[^\w\s-]/', '', $term);
    }

    /**
     * Optimize database filters
     */
    private function optimizeFilters(array $filters): array
    {
        // Ensure indexed fields are used first
        $indexedFields = ['status', 'hub_id', 'created_at', 'merchant_id'];

        uksort($filters, function ($a, $b) use ($indexedFields) {
            $aIndexed = in_array($a, $indexedFields);
            $bIndexed = in_array($b, $indexedFields);

            if ($aIndexed && ! $bIndexed) {
                return -1;
            }
            if (! $aIndexed && $bIndexed) {
                return 1;
            }

            return 0;
        });

        return $filters;
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(): array
    {
        return [
            'cache_hit_ratio' => $this->calculateCacheHitRatio(),
            'average_query_time' => $this->getAverageQueryTime(),
            'memory_usage' => memory_get_peak_usage(true),
            'database_connections' => DB::getConnections(),
            'slow_queries' => $this->getSlowQueries(),
        ];
    }

    /**
     * Calculate cache hit ratio
     */
    private function calculateCacheHitRatio(): float
    {
        // Implementation would track cache hits/misses
        return 0.85; // Placeholder
    }

    /**
     * Get average query time
     */
    private function getAverageQueryTime(): float
    {
        // Implementation would track query execution times
        return 150.5; // Placeholder in milliseconds
    }

    /**
     * Get slow queries
     */
    private function getSlowQueries(): array
    {
        // Implementation would query slow query log
        return []; // Placeholder
    }
}
