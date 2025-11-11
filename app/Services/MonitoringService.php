<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitoringService
{
    private array $metrics = [];

    public function recordMetric(string $name, float $value, array $tags = []): void
    {
        $metric = [
            'name' => $name,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->timestamp,
        ];

        $this->metrics[] = $metric;

        // Log structured metrics
        Log::channel('metrics')->info('metric_recorded', [
            'metric_name' => $name,
            'metric_value' => $value,
            'tags' => $tags,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);

        // Push to external monitoring if configured
        $this->pushToMonitoring($metric);
    }

    public function recordLatency(string $operation, float $milliseconds, array $context = []): void
    {
        $this->recordMetric("operation_latency_ms", $milliseconds, [
            'operation' => $operation,
            'threshold' => $milliseconds > 1000 ? 'high' : 'normal',
            ...$context,
        ]);
    }

    public function recordThroughput(string $resource, int $count, array $context = []): void
    {
        $this->recordMetric("throughput_count", $count, [
            'resource' => $resource,
            ...$context,
        ]);
    }

    public function recordError(string $type, string $message, array $context = []): void
    {
        Log::channel('metrics')->error('error_recorded', [
            'error_type' => $type,
            'error_message' => $message,
            'context' => $context,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    public function recordCapacityUtilization(float $utilization, string $resource, array $context = []): void
    {
        $severity = match (true) {
            $utilization >= 95 => 'critical',
            $utilization >= 80 => 'warning',
            default => 'normal',
        };

        $this->recordMetric("capacity_utilization_percent", $utilization, [
            'resource' => $resource,
            'severity' => $severity,
            ...$context,
        ]);
    }

    private function pushToMonitoring(array $metric): void
    {
        if (!config('monitoring.prometheus.enabled')) {
            return;
        }

        try {
            // Push to Prometheus Push Gateway or similar
            // This is a placeholder for actual implementation
        } catch (\Throwable $e) {
            Log::warning('Failed to push metric to monitoring', [
                'metric' => $metric['name'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }
}
