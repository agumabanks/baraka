<?php

namespace App\Services\ApiGateway;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker service for API Gateway
 * Implements the circuit breaker pattern to prevent cascading failures
 */
class CircuitBreakerService
{
    protected array $config = [
        'failure_threshold' => 5,        // Number of failures to open circuit
        'recovery_timeout' => 60,        // Seconds before attempting recovery
        'half_open_max_calls' => 3,      // Max calls to test in half-open state
        'success_threshold' => 2,        // Successes needed to close circuit
    ];

    protected const STATE_CLOSED = 'closed';
    protected const STATE_OPEN = 'open';
    protected const STATE_HALF_OPEN = 'half_open';

    /**
     * Check if circuit is open for a service
     */
    public function isOpen(string $service): bool
    {
        $state = $this->getState($service);
        
        if ($state === self::STATE_OPEN) {
            // Check if we've waited long enough to try half-open
            $lastFailureTime = $this->getLastFailureTime($service);
            if ($lastFailureTime && (time() - $lastFailureTime) >= $this->config['recovery_timeout']) {
                $this->setState($service, self::STATE_HALF_OPEN);
                $this->resetCallCount($service);
                return false; // Try half-open
            }
            return true;
        }
        
        return false;
    }

    /**
     * Record a successful call
     */
    public function recordSuccess(string $service): void
    {
        $state = $this->getState($service);
        
        // Clear failure count on success
        $this->setFailureCount($service, 0);
        
        if ($state === self::STATE_HALF_OPEN) {
            $callCount = $this->getCallCount($service);
            $successCount = $this->getSuccessCount($service) + 1;
            $this->setSuccessCount($service, $successCount);
            
            if ($successCount >= $this->config['success_threshold']) {
                $this->setState($service, self::STATE_CLOSED);
                $this->resetCallCount($service);
                $this->setSuccessCount($service, 0);
                
                Log::info("Circuit breaker closed for service: {$service}");
            }
        }
    }

    /**
     * Record a failed call
     */
    public function recordFailure(string $service): void
    {
        $state = $this->getState($service);
        $failureCount = $this->getFailureCount($service) + 1;
        
        $this->setFailureCount($service, $failureCount);
        $this->setLastFailureTime($service, time());
        
        if ($state === self::STATE_CLOSED && $failureCount >= $this->config['failure_threshold']) {
            $this->setState($service, self::STATE_OPEN);
            Log::warning("Circuit breaker opened for service: {$service} after {$failureCount} failures");
        } elseif ($state === self::STATE_HALF_OPEN) {
            $this->setState($service, self::STATE_OPEN);
            $this->resetCallCount($service);
            Log::warning("Circuit breaker re-opened for service: {$service}");
        }
    }

    /**
     * Check if we can make a call (not exceeded half-open limit)
     */
    public function canCall(string $service): bool
    {
        $state = $this->getState($service);
        
        if ($state === self::STATE_HALF_OPEN) {
            return $this->getCallCount($service) < $this->config['half_open_max_calls'];
        }
        
        return true;
    }

    /**
     * Record a call attempt
     */
    public function recordCall(string $service): void
    {
        $state = $this->getState($service);
        
        if ($state === self::STATE_HALF_OPEN) {
            $this->incrementCallCount($service);
        }
    }

    /**
     * Get the current state of a service
     */
    public function getState(string $service): string
    {
        return Cache::get("circuit_breaker:{$service}:state", self::STATE_CLOSED);
    }

    /**
     * Set the state of a service
     */
    protected function setState(string $service, string $state): void
    {
        Cache::put("circuit_breaker:{$service}:state", $state, 3600);
    }

    /**
     * Get failure count for a service
     */
    protected function getFailureCount(string $service): int
    {
        return Cache::get("circuit_breaker:{$service}:failures", 0);
    }

    /**
     * Set failure count for a service
     */
    protected function setFailureCount(string $service, int $count): void
    {
        Cache::put("circuit_breaker:{$service}:failures", $count, 3600);
    }

    /**
     * Get last failure time for a service
     */
    protected function getLastFailureTime(string $service): ?int
    {
        return Cache::get("circuit_breaker:{$service}:last_failure");
    }

    /**
     * Set last failure time for a service
     */
    protected function setLastFailureTime(string $service, int $timestamp): void
    {
        Cache::put("circuit_breaker:{$service}:last_failure", $timestamp, 3600);
    }

    /**
     * Get call count for half-open state
     */
    protected function getCallCount(string $service): int
    {
        return Cache::get("circuit_breaker:{$service}:calls", 0);
    }

    /**
     * Increment call count
     */
    protected function incrementCallCount(string $service): void
    {
        $count = $this->getCallCount($service) + 1;
        Cache::put("circuit_breaker:{$service}:calls", $count, 3600);
    }

    /**
     * Reset call count
     */
    protected function resetCallCount(string $service): void
    {
        Cache::put("circuit_breaker:{$service}:calls", 0, 3600);
    }

    /**
     * Get success count for half-open state
     */
    protected function getSuccessCount(string $service): int
    {
        return Cache::get("circuit_breaker:{$service}:successes", 0);
    }

    /**
     * Set success count
     */
    protected function setSuccessCount(string $service, int $count): void
    {
        Cache::put("circuit_breaker:{$service}:successes", $count, 3600);
    }

    /**
     * Get circuit breaker status for all services
     */
    public function getStatus(): array
    {
        // This would typically be loaded from configuration or service discovery
        $services = config('api_gateway.circuit_breaker.services', [
            'operational-reporting',
            'financial-reporting',
            'customer-intelligence',
            'real-time-dashboard',
        ]);

        $status = [];
        foreach ($services as $service) {
            $status[$service] = [
                'state' => $this->getState($service),
                'failure_count' => $this->getFailureCount($service),
                'last_failure' => $this->getLastFailureTime($service),
                'call_count' => $this->getCallCount($service),
            ];
        }

        return $status;
    }

    /**
     * Manually reset a service circuit breaker
     */
    public function reset(string $service): void
    {
        $this->setState($service, self::STATE_CLOSED);
        $this->setFailureCount($service, 0);
        $this->setLastFailureTime($service, null);
        $this->resetCallCount($service);
        $this->setSuccessCount($service, 0);
        
        Log::info("Circuit breaker manually reset for service: {$service}");
    }

    /**
     * Get circuit breaker statistics
     */
    public function getStatistics(): array
    {
        $status = $this->getStatus();
        
        $stats = [
            'total_services' => count($status),
            'closed_circuits' => 0,
            'open_circuits' => 0,
            'half_open_circuits' => 0,
            'total_failures' => 0,
        ];
        
        foreach ($status as $service => $info) {
            $stats['total_failures'] += $info['failure_count'];
            
            switch ($info['state']) {
                case self::STATE_CLOSED:
                    $stats['closed_circuits']++;
                    break;
                case self::STATE_OPEN:
                    $stats['open_circuits']++;
                    break;
                case self::STATE_HALF_OPEN:
                    $stats['half_open_circuits']++;
                    break;
            }
        }
        
        return $stats;
    }

    /**
     * Check service health
     */
    public function checkServiceHealth(string $service): array
    {
        $state = $this->getState($service);
        $failureCount = $this->getFailureCount($service);
        $lastFailure = $this->getLastFailureTime($service);
        
        $isHealthy = $state === self::STATE_CLOSED;
        $canMakeCalls = $this->canCall($service);
        
        return [
            'service' => $service,
            'state' => $state,
            'is_healthy' => $isHealthy,
            'can_make_calls' => $canMakeCalls,
            'failure_count' => $failureCount,
            'last_failure' => $lastFailure,
            'time_since_last_failure' => $lastFailure ? time() - $lastFailure : null,
            'recovery_eligible' => $state === self::STATE_OPEN && 
                                  $lastFailure && 
                                  (time() - $lastFailure) >= $this->config['recovery_timeout'],
        ];
    }

    /**
     * Get services that need attention
     */
    public function getServicesNeedingAttention(): array
    {
        $status = $this->getStatus();
        $services = [];
        
        foreach ($status as $service => $info) {
            if ($info['state'] === self::STATE_OPEN) {
                $lastFailure = $info['last_failure'];
                $timeSinceFailure = $lastFailure ? time() - $lastFailure : null;
                
                $services[] = [
                    'service' => $service,
                    'state' => $info['state'],
                    'failure_count' => $info['failure_count'],
                    'last_failure' => $lastFailure,
                    'time_since_failure' => $timeSinceFailure,
                    'attention_level' => $this->calculateAttentionLevel($info),
                ];
            }
        }
        
        return $services;
    }

    /**
     * Calculate attention level for a service
     */
    protected function calculateAttentionLevel(array $serviceInfo): string
    {
        $failureCount = $serviceInfo['failure_count'];
        $lastFailure = $serviceInfo['last_failure'];
        $timeSinceFailure = $lastFailure ? time() - $lastFailure : 0;
        
        if ($failureCount >= 10) return 'critical';
        if ($failureCount >= 5) return 'high';
        if ($timeSinceFailure > 300) return 'medium'; // 5 minutes
        return 'low';
    }

    /**
     * Configure circuit breaker
     */
    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}