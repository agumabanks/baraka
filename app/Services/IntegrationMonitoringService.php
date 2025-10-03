<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IntegrationMonitoringService
{
    public function checkIntegrationHealth(): array
    {
        $integrations = DB::table("third_party_integrations")
            ->where("status", "active")
            ->get();

        $healthStatus = [];

        foreach ($integrations as $integration) {
            $healthStatus[] = [
                "integration_id" => $integration->id,
                "name" => $integration->name,
                "type" => $integration->integration_type,
                "status" => $this->checkIntegrationStatus($integration),
                "last_sync" => $this->getLastSyncTime($integration->id),
                "error_count" => $this->getErrorCount($integration->id),
            ];
        }

        $overallHealth = $this->calculateOverallHealth($healthStatus);

        return [
            "overall_health" => $overallHealth,
            "integrations" => $healthStatus,
            "checked_at" => now(),
        ];
    }

    private function checkIntegrationStatus($integration): string
    {
        // Simplified health check
        $lastSync = $this->getLastSyncTime($integration->id);
        
        if (!$lastSync) {
            return "never_synced";
        }

        $hoursSinceSync = $lastSync->diffInHours(now());
        
        if ($hoursSinceSync > 24) {
            return "unhealthy";
        } elseif ($hoursSinceSync > 6) {
            return "warning";
        }
        
        return "healthy";
    }

    private function getLastSyncTime(int $integrationId): ?Carbon
    {
        $lastSync = DB::table("integration_syncs")
            ->where("integration_id", $integrationId)
            ->latest("synced_at")
            ->first();

        return $lastSync ? Carbon::parse($lastSync->synced_at) : null;
    }

    private function getErrorCount(int $integrationId): int
    {
        return DB::table("integration_syncs")
            ->where("integration_id", $integrationId)
            ->where("sync_status", "error")
            ->where("synced_at", ">", now()->subDays(7))
            ->count();
    }

    private function calculateOverallHealth(array $integrations): string
    {
        $statuses = array_column($integrations, "status");
        
        if (in_array("unhealthy", $statuses)) {
            return "critical";
        } elseif (in_array("warning", $statuses)) {
            return "warning";
        } elseif (in_array("never_synced", $statuses)) {
            return "warning";
        }
        
        return "healthy";
    }

    public function getIntegrationMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $metrics = DB::table("integration_syncs")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->selectRaw("
                COUNT(*) as total_syncs,
                SUM(CASE WHEN sync_status = completed THEN 1 ELSE 0 END) as successful_syncs,
                SUM(CASE WHEN sync_status = error THEN 1 ELSE 0 END) as failed_syncs,
                AVG(records_synced) as avg_records_synced
            ")
            ->first();

        return [
            "period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "metrics" => $metrics,
            "success_rate" => $metrics->total_syncs > 0 
                ? ($metrics->successful_syncs / $metrics->total_syncs) * 100 
                : 0,
            "generated_at" => now(),
        ];
    }
}
