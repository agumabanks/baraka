<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsCacheService
{
    protected $defaultTtl = 3600; // 1 hour
    protected $prefix = 'analytics';
    protected $tagPattern = 'analytics:*';
    
    public function __construct()
    {
        // Set up custom cache tags
        Cache::tags(['analytics'])->rememberForever('cache_config', function() {
            return $this->getCacheConfig();
        });
    }
    
    /**
     * Get dashboard metrics with caching
     */
    public function getDashboardMetrics(int $branchId, ?array $dateRange = null): array
    {
        $cacheKey = $this->buildCacheKey('dashboard', [
            'branch' => $branchId,
            'date_range' => $this->hashDateRange($dateRange)
        ]);
        
        return Cache::tags(['dashboard', 'branch:' . $branchId])
            ->remember($cacheKey, $this->getTtl('dashboard_metrics'), function() use ($branchId, $dateRange) {
                return $this->calculateDashboardMetrics($branchId, $dateRange);
            });
    }
    
    /**
     * Get operational reports with caching
     */
    public function getOperationalReport(int $branchId, string $reportType, ?array $dateRange = null): array
    {
        $cacheKey = $this->buildCacheKey('operational', [
            'branch' => $branchId,
            'type' => $reportType,
            'date_range' => $this->hashDateRange($dateRange)
        ]);
        
        return Cache::tags(['operational', 'branch:' . $branchId])
            ->remember($cacheKey, $this->getTtl('operational_reports'), function() use ($branchId, $reportType, $dateRange) {
                return $this->calculateOperationalReport($branchId, $reportType, $dateRange);
            });
    }
    
    /**
     * Get financial reports with caching
     */
    public function getFinancialReport(int $clientId, ?array $dateRange = null): array
    {
        $cacheKey = $this->buildCacheKey('financial', [
            'client' => $clientId,
            'date_range' => $this->hashDateRange($dateRange)
        ]);
        
        return Cache::tags(['financial', 'client:' . $clientId])
            ->remember($cacheKey, $this->getTtl('financial_reports'), function() use ($clientId, $dateRange) {
                return $this->calculateFinancialReport($clientId, $dateRange);
            });
    }
    
    /**
     * Get customer analytics with caching
     */
    public function getCustomerAnalytics(int $customerId, ?array $dateRange = null): array
    {
        $cacheKey = $this->buildCacheKey('customer', [
            'customer' => $customerId,
            'date_range' => $this->hashDateRange($dateRange)
        ]);
        
        return Cache::tags(['customer', 'customer_analytics'])
            ->remember($cacheKey, $this->getTtl('customer_analytics'), function() use ($customerId, $dateRange) {
                return $this->calculateCustomerAnalytics($customerId, $dateRange);
            });
    }
    
    /**
     * Get performance metrics with caching
     */
    public function getPerformanceMetrics(int $branchId, ?array $dateRange = null): array
    {
        $cacheKey = $this->buildCacheKey('performance', [
            'branch' => $branchId,
            'date_range' => $this->hashDateRange($dateRange)
        ]);
        
        return Cache::tags(['performance', 'branch:' . $branchId])
            ->remember($cacheKey, $this->getTtl('performance_metrics'), function() use ($branchId, $dateRange) {
                return $this->calculatePerformanceMetrics($branchId, $dateRange);
            });
    }
    
    /**
     * Preload frequently accessed data
     */
    public function preloadCommonData(int $branchId): void
    {
        Log::info("Preloading cache data for branch", ['branch_id' => $branchId]);
        
        // Preload today's metrics
        $today = [now()->toDateString(), now()->toDateString()];
        $this->getDashboardMetrics($branchId, $today);
        
        // Preload weekly metrics
        $week = [now()->subWeek()->toDateString(), now()->toDateString()];
        $this->getDashboardMetrics($branchId, $week);
        
        // Preload monthly metrics
        $month = [now()->startOfMonth()->toDateString(), now()->toDateString()];
        $this->getDashboardMetrics($branchId, $month);
        
        // Preload operational reports
        $this->getOperationalReport($branchId, 'daily_summary', $today);
        $this->getOperationalReport($branchId, 'weekly_summary', $week);
        $this->getOperationalReport($branchId, 'monthly_summary', $month);
        
        // Preload performance metrics
        $this->getPerformanceMetrics($branchId, $today);
    }
    
    /**
     * Clear cache for specific patterns
     */
    public function invalidatePattern(string $pattern): void
    {
        $keys = $this->findKeysByPattern($pattern);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        Log::info("Cache invalidated for pattern", ['pattern' => $pattern, 'keys_removed' => count($keys)]);
    }
    
    /**
     * Clear cache by tags
     */
    public function invalidateByTags(array $tags): void
    {
        foreach ($tags as $tag) {
            Cache::tags([$tag])->flush();
        }
        
        Log::info("Cache invalidated by tags", ['tags' => $tags]);
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $driver = config('cache.default');
        $stats = [
            'driver' => $driver,
            'prefix' => $this->prefix,
            'ttl_defaults' => $this->getTtlDefaults(),
            'memory_usage' => $this->getMemoryUsage()
        ];
        
        if ($driver === 'redis') {
            $stats['redis_info'] = $this->getRedisStats();
        }
        
        return $stats;
    }
    
    /**
     * Warm up cache for a specific date range
     */
    public function warmUpCacheForDateRange(array $dateRange, array $branchIds = []): void
    {
        Log::info("Warming up cache for date range", ['date_range' => $dateRange, 'branches' => $branchIds]);
        
        $branches = $branchIds ?: $this->getActiveBranchIds();
        
        foreach ($branches as $branchId) {
            $this->preloadCommonData($branchId);
            $this->getPerformanceMetrics($branchId, $dateRange);
        }
        
        // Warm up financial data for top clients
        $topClients = $this->getTopClients();
        foreach ($topClients as $clientId) {
            $this->getFinancialReport($clientId, $dateRange);
        }
    }
    
    protected function calculateDashboardMetrics(int $branchId, ?array $dateRange): array
    {
        [$startDate, $endDate] = $this->parseDateRange($dateRange);
        
        return DB::select("
            SELECT 
                COUNT(*) as total_shipments,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_shipments,
                SUM(CASE WHEN status = 'exception' THEN 1 ELSE 0 END) as exception_shipments,
                AVG(CASE WHEN status = 'delivered' THEN delivery_duration_minutes END) as avg_delivery_time,
                SUM(revenue) as total_revenue,
                SUM(total_cost) as total_cost,
                SUM(margin) as total_margin,
                AVG(margin_percentage) as avg_margin_percentage
            FROM fact_shipments fs
            JOIN dim_branch db ON fs.origin_branch_key = db.branch_key
            WHERE db.branch_id = ? 
            AND pickup_date_key BETWEEN ? AND ?
        ", [$branchId, $this->formatDateKey($startDate), $this->formatDateKey($endDate)]);
    }
    
    protected function calculateOperationalReport(int $branchId, string $reportType, ?array $dateRange): array
    {
        [$startDate, $endDate] = $this->parseDateRange($dateRange);
        
        switch ($reportType) {
            case 'daily_summary':
                return $this->getDailySummary($branchId, $startDate, $endDate);
            case 'weekly_summary':
                return $this->getWeeklySummary($branchId, $startDate, $endDate);
            case 'monthly_summary':
                return $this->getMonthlySummary($branchId, $startDate, $endDate);
            default:
                return [];
        }
    }
    
    protected function calculateFinancialReport(int $clientId, ?array $dateRange): array
    {
        [$startDate, $endDate] = $this->parseDateRange($dateRange);
        
        return DB::select("
            SELECT 
                transaction_date_key,
                transaction_type,
                SUM(debit_amount) as total_debits,
                SUM(credit_amount) as total_credits,
                COUNT(*) as transaction_count
            FROM fact_financial_transactions
            WHERE client_key = ? 
            AND transaction_date_key BETWEEN ? AND ?
            GROUP BY transaction_date_key, transaction_type
            ORDER BY transaction_date_key
        ", [$clientId, $this->formatDateKey($startDate), $this->formatDateKey($endDate)]);
    }
    
    protected function calculateCustomerAnalytics(int $customerId, ?array $dateRange): array
    {
        [$startDate, $endDate] = $this->parseDateRange($dateRange);
        
        return DB::select("
            SELECT 
                COUNT(*) as shipments_count,
                SUM(revenue) as total_spend,
                AVG(revenue) as average_order_value,
                AVG(delivery_duration_minutes) as avg_delivery_time,
                MAX(created_at) as last_shipment_date
            FROM fact_shipments
            WHERE customer_key = ? 
            AND created_date_key BETWEEN ? AND ?
        ", [$customerId, $this->formatDateKey($startDate), $this->formatDateKey($endDate)]);
    }
    
    protected function calculatePerformanceMetrics(int $branchId, ?array $dateRange): array
    {
        [$startDate, $endDate] = $this->parseDateRange($dateRange);
        
        return DB::select("
            SELECT 
                date_key,
                total_shipments,
                delivered_shipments,
                returned_shipments,
                exception_shipments,
                on_time_delivery_rate,
                total_revenue,
                total_cost,
                total_margin,
                margin_percentage
            FROM fact_performance_metrics
            WHERE branch_key = ? 
            AND date_key BETWEEN ? AND ?
            ORDER BY date_key
        ", [$branchId, $this->formatDateKey($startDate), $this->formatDateKey($endDate)]);
    }
    
    protected function getDailySummary(int $branchId, string $startDate, string $endDate): array
    {
        return DB::select("
            SELECT 
                pickup_date_key as date,
                COUNT(*) as shipments,
                AVG(delivery_duration_minutes) as avg_delivery_time,
                SUM(revenue) as revenue
            FROM fact_shipments
            WHERE origin_branch_key = ? 
            AND pickup_date_key BETWEEN ? AND ?
            GROUP BY pickup_date_key
            ORDER BY pickup_date_key
        ", [$branchId, $this->formatDateKey($startDate), $this->formatDateKey($endDate)]);
    }
    
    protected function getWeeklySummary(int $branchId, string $startDate, string $endDate): array
    {
        return DB::select("
            SELECT 
                YEAR(DATE(pickup_date_key/10000 + 1/1 + (pickup_date_key % 10000)/10000)) as year,
                WEEK(DATE(pickup_date_key/10000 + 1/1 + (pickup_date_key % 10000)/10000)) as week,
                COUNT(*) as shipments,
                AVG(delivery_duration_minutes) as avg_delivery_time,
                SUM(revenue) as revenue
            FROM fact_shipments
            WHERE origin_branch_key = ? 
            AND pickup_date_key BETWEEN ? AND ?
            GROUP BY year, week
            ORDER BY year, week
        ", [$branchId, $this->formatDateKey($startDate), $this->formatDateKey($endDate)]);
    }
    
    protected function getMonthlySummary(int $branchId, string $startDate, string $endDate): array
    {
        return DB::select("
            SELECT 
                YEAR(pickup_date_key/10000) as year,
                MONTH(pickup_date_key/10000) as month,
                COUNT(*) as shipments,
                AVG(delivery_duration_minutes) as avg_delivery_time,
                SUM(revenue) as revenue
            FROM fact_shipments
            WHERE origin_branch_key = ? 
            AND pickup_date_key BETWEEN ? AND ?
            GROUP BY year, month
            ORDER BY year, month
        ", [$branchId, $this->formatDateKey($startDate), $this->formatDateKey($endDate)]);
    }
    
    protected function buildCacheKey(string $type, array $params): string
    {
        $parts = [$this->prefix, $type];
        
        foreach ($params as $key => $value) {
            $parts[] = "{$key}:{$value}";
        }
        
        return implode(':', $parts);
    }
    
    protected function hashDateRange(?array $dateRange): string
    {
        if (!$dateRange) {
            return 'all_time';
        }
        
        return md5(serialize($dateRange));
    }
    
    protected function parseDateRange(?array $dateRange): array
    {
        if (!$dateRange) {
            return [now()->subMonth()->toDateString(), now()->toDateString()];
        }
        
        if (count($dateRange) !== 2) {
            return [now()->subMonth()->toDateString(), now()->toDateString()];
        }
        
        return $dateRange;
    }
    
    protected function formatDateKey(string $date): string
    {
        return date('Ymd', strtotime($date));
    }
    
    protected function getTtl(string $type): int
    {
        $config = $this->getCacheConfig();
        return $config['ttl'][$type] ?? $this->defaultTtl;
    }
    
    protected function getCacheConfig(): array
    {
        return config('etl-pipeline.cache', [
            'ttl' => [
                'dashboard_metrics' => 3600,
                'operational_reports' => 1800,
                'financial_reports' => 7200,
                'customer_analytics' => 14400,
                'performance_metrics' => 300,
            ]
        ]);
    }
    
    protected function getTtlDefaults(): array
    {
        return $this->getCacheConfig()['ttl'];
    }
    
    protected function findKeysByPattern(string $pattern): array
    {
        // Implementation depends on cache driver
        if (config('cache.default') === 'redis') {
            return $this->findRedisKeysByPattern($pattern);
        }
        
        return [];
    }
    
    protected function findRedisKeysByPattern(string $pattern): array
    {
        try {
            $redis = Cache::getStore()->getRedis()->connection();
            $cursor = null;
            $keys = [];
            
            do {
                $result = $redis->scan($cursor, "{$this->prefix}:{$pattern}", 1000);
                $keys = array_merge($keys, $result[1]);
            } while ($cursor !== '0');
            
            return $keys;
        } catch (\Exception $e) {
            Log::error("Failed to find keys by pattern", ['pattern' => $pattern, 'error' => $e->getMessage()]);
            return [];
        }
    }
    
    protected function getActiveBranchIds(): array
    {
        return DB::table('dim_branch')
            ->where('is_active', true)
            ->pluck('branch_id')
            ->toArray();
    }
    
    protected function getTopClients(): array
    {
        return DB::table('dim_client')
            ->where('is_active', true)
            ->orderBy('total_spend', 'desc')
            ->limit(10)
            ->pluck('client_id')
            ->toArray();
    }
    
    protected function getMemoryUsage(): array
    {
        if (function_exists('memory_get_usage')) {
            return [
                'current' => memory_get_usage(),
                'peak' => memory_get_peak_usage(),
                'formatted_current' => $this->formatBytes(memory_get_usage()),
                'formatted_peak' => $this->formatBytes(memory_get_peak_usage()),
            ];
        }
        
        return [];
    }
    
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
    }
    
    protected function getRedisStats(): array
    {
        try {
            $redis = Cache::getStore()->getRedis()->connection();
            return $redis->info();
        } catch (\Exception $e) {
            return [];
        }
    }
}