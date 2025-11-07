<?php

namespace App\Services\Security;

use App\Models\Security\SecurityAuditLog;
use App\Notifications\SecurityIncidentAlert;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class SecurityMonitoringService
{
    private const THREAT_LEVEL_LOW = 'low';
    private const THREAT_LEVEL_MEDIUM = 'medium';
    private const THREAT_LEVEL_HIGH = 'high';
    private const THREAT_LEVEL_CRITICAL = 'critical';
    
    private const INCIDENT_AUTO_CLOSE_TIME = 3600; // 1 hour for low severity incidents

    /**
     * Monitor for security threats in real-time
     */
    public function monitorSecurityThreats(): array
    {
        $threats = [];
        
        try {
            // Check for multiple failed login attempts
            $threats['failed_logins'] = $this->detectMultipleFailedLogins();
            
            // Check for suspicious API usage patterns
            $threats['suspicious_api_usage'] = $this->detectSuspiciousApiUsage();
            
            // Check for unusual access patterns
            $threats['unusual_access'] = $this->detectUnusualAccessPatterns();
            
            // Check for privilege escalation attempts
            $threats['privilege_escalation'] = $this->detectPrivilegeEscalation();
            
            // Check for data access anomalies
            $threats['data_access_anomalies'] = $this->detectDataAccessAnomalies();
            
            // Check for system integrity violations
            $threats['system_integrity'] = $this->checkSystemIntegrity();
            
        } catch (Exception $e) {
            Log::error('Security monitoring failed', ['error' => $e->getMessage()]);
        }
        
        return $threats;
    }

    /**
     * Detect multiple failed login attempts
     */
    private function detectMultipleFailedLogins(): array
    {
        $windowStart = now()->subMinutes(15);
        $failedLogins = SecurityAuditLog::where('event_type', 'login')
            ->where('status', '!=', 'success')
            ->where('created_at', '>=', $windowStart)
            ->get()
            ->groupBy('ip_address')
            ->filter(function ($logs) {
                return $logs->count() >= 5; // 5+ failed attempts in 15 minutes
            });
        
        $threats = [];
        foreach ($failedLogins as $ipAddress => $logs) {
            $threats[] = [
                'type' => 'multiple_failed_logins',
                'severity' => self::THREAT_LEVEL_HIGH,
                'ip_address' => $ipAddress,
                'attempts' => $logs->count(),
                'time_window' => '15 minutes',
                'first_attempt' => $logs->first()->created_at,
                'last_attempt' => $logs->last()->created_at,
            ];
        }
        
        return $threats;
    }

    /**
     * Detect suspicious API usage patterns
     */
    private function detectSuspiciousApiUsage(): array
    {
        $windowStart = now()->subMinutes(5);
        $apiCalls = SecurityAuditLog::where('event_type', 'api_access')
            ->where('created_at', '>=', $windowStart)
            ->get()
            ->groupBy(function ($log) {
                return $log->user_id . '_' . $log->ip_address;
            });
        
        $threats = [];
        foreach ($apiCalls as $identifier => $calls) {
            $uniqueEndpoints = $calls->pluck('action_details.method')->unique();
            $totalRequests = $calls->count();
            
            // Detect rapid-fire requests to multiple endpoints
            if ($totalRequests > 50 && $uniqueEndpoints->count() > 10) {
                $threats[] = [
                    'type' => 'rapid_api_requests',
                    'severity' => self::THREAT_LEVEL_HIGH,
                    'identifier' => $identifier,
                    'total_requests' => $totalRequests,
                    'unique_endpoints' => $uniqueEndpoints->count(),
                    'time_window' => '5 minutes',
                ];
            }
            
            // Detect unusual HTTP methods
            $suspiciousMethods = $calls->filter(function ($call) {
                $method = $call->action_details['method'] ?? '';
                return in_array($method, ['TRACE', 'TRACK', 'DEBUG', 'CONNECT']);
            });
            
            if ($suspiciousMethods->count() > 0) {
                $threats[] = [
                    'type' => 'suspicious_http_methods',
                    'severity' => self::THREAT_LEVEL_CRITICAL,
                    'identifier' => $identifier,
                    'suspicious_methods' => $suspiciousMethods->pluck('action_details.method')->unique(),
                ];
            }
        }
        
        return $threats;
    }

    /**
     * Detect unusual access patterns
     */
    private function detectUnusualAccessPatterns(): array
    {
        $windowStart = now()->subHour();
        $accessLogs = SecurityAuditLog::where('event_type', 'data_access')
            ->where('created_at', '>=', $windowStart)
            ->get();
        
        $threats = [];
        
        // Detect access to sensitive data outside business hours
        $afterHours = $accessLogs->filter(function ($log) {
            $hour = $log->created_at->hour;
            return $hour < 6 || $hour > 22; // Outside 6 AM - 10 PM
        });
        
        foreach ($afterHours->groupBy('user_id') as $userId => $logs) {
            $threats[] = [
                'type' => 'after_hours_access',
                'severity' => self::THREAT_LEVEL_MEDIUM,
                'user_id' => $userId,
                'access_count' => $logs->count(),
                'time_period' => 'outside business hours',
            ];
        }
        
        // Detect access to large amounts of data
        $largeAccess = $accessLogs->filter(function ($log) {
            $data = $log->action_details ?? [];
            return isset($data['records_accessed']) && $data['records_accessed'] > 1000;
        });
        
        foreach ($largeAccess as $log) {
            $threats[] = [
                'type' => 'bulk_data_access',
                'severity' => self::THREAT_LEVEL_HIGH,
                'user_id' => $log->user_id,
                'resource_type' => $log->resource_type,
                'records_accessed' => $log->action_details['records_accessed'] ?? 'unknown',
            ];
        }
        
        return $threats;
    }

    /**
     * Detect privilege escalation attempts
     */
    private function detectPrivilegeEscalation(): array
    {
        $windowStart = now()->subHour();
        $permissionChanges = SecurityAuditLog::where('event_type', 'permission_change')
            ->where('created_at', '>=', $windowStart)
            ->where('severity', 'high')
            ->get();
        
        $threats = [];
        foreach ($permissionChanges as $change) {
            $threats[] = [
                'type' => 'privilege_escalation',
                'severity' => self::THREAT_LEVEL_CRITICAL,
                'user_id' => $change->user_id,
                'change_type' => $change->action_details['action'] ?? 'unknown',
                'timestamp' => $change->created_at,
            ];
        }
        
        return $threats;
    }

    /**
     * Detect data access anomalies
     */
    private function detectDataAccessAnomalies(): array
    {
        $windowStart = now()->subDay();
        $dataAccess = SecurityAuditLog::where('event_type', 'data_access')
            ->where('created_at', '>=', $windowStart)
            ->get();
        
        $threats = [];
        
        // Check for access to new resources
        $normalResources = $this->getNormalUserResources();
        foreach ($dataAccess->groupBy('user_id') as $userId => $userAccess) {
            $userResources = $userAccess->pluck('resource_type')->unique();
            $unusualResources = $userResources->diff($normalResources->get($userId, collect()));
            
            if ($unusualResources->count() > 0) {
                $threats[] = [
                    'type' => 'unusual_resource_access',
                    'severity' => self::THREAT_LEVEL_MEDIUM,
                    'user_id' => $userId,
                    'unusual_resources' => $unusualResources->toArray(),
                ];
            }
        }
        
        return $threats;
    }

    /**
     * Check system integrity
     */
    private function checkSystemIntegrity(): array
    {
        $threats = [];
        
        // Check for configuration changes
        $configChanges = SecurityAuditLog::where('event_type', 'configuration_change')
            ->where('created_at', '>=', now()->subHour())
            ->get();
        
        foreach ($configChanges as $change) {
            if ($change->severity === self::THREAT_LEVEL_HIGH) {
                $threats[] = [
                    'type' => 'configuration_change',
                    'severity' => self::THREAT_LEVEL_HIGH,
                    'user_id' => $change->user_id,
                    'change_details' => $change->action_details,
                ];
            }
        }
        
        return $threats;
    }

    /**
     * Create security incident
     */
    public function createSecurityIncident(array $incidentData): SecurityIncident
    {
        try {
            $incident = SecurityIncident::create([
                'title' => $incidentData['title'],
                'description' => $incidentData['description'],
                'severity' => $incidentData['severity'] ?? self::THREAT_LEVEL_MEDIUM,
                'incident_type' => $incidentData['type'],
                'status' => 'open',
                'detected_at' => now(),
                'detected_by' => $incidentData['detected_by'] ?? auth()->id(),
                'affected_systems' => $incidentData['affected_systems'] ?? [],
                'incident_data' => $incidentData,
            ]);
            
            // Trigger incident response workflow
            $this->triggerIncidentResponse($incident);
            
            return $incident;
            
        } catch (Exception $e) {
            Log::error('Failed to create security incident', ['error' => $e->getMessage()]);
            throw new Exception('Failed to create security incident');
        }
    }

    /**
     * Trigger incident response workflow
     */
    private function triggerIncidentResponse(SecurityIncident $incident): void
    {
        // Notify security team based on severity
        $securityTeam = $this->getSecurityTeam($incident->severity);
        
        foreach ($securityTeam as $user) {
            Notification::send($user, new SecurityIncidentAlert($incident));
        }
        
        // Auto-assign incident based on severity and type
        $this->autoAssignIncident($incident);
        
        // Log incident creation
        SecurityAuditLog::create([
            'event_type' => 'security_incident_created',
            'event_category' => 'security',
            'severity' => $incident->severity,
            'resource_type' => 'security_incident',
            'resource_id' => $incident->id,
            'action_details' => $incident->toArray(),
            'status' => 'success',
            'description' => "Security incident created: {$incident->title}",
        ]);
    }

    /**
     * Get security team members based on severity
     */
    private function getSecurityTeam(string $severity): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['security_admin', 'security_manager', 'cto']);
        });
        
        return match($severity) {
            self::THREAT_LEVEL_CRITICAL => $query->get(), // All security personnel
            self::THREAT_LEVEL_HIGH => $query->where('roles.name', '!=', 'cto')->get(),
            default => $query->where('roles.name', 'security_admin')->get(),
        };
    }

    /**
     * Auto-assign incident to appropriate team member
     */
    private function autoAssignIncident(SecurityIncident $incident): void
    {
        $assignee = $this->findAvailableAssignee($incident);
        
        if ($assignee) {
            $incident->update([
                'assigned_to' => $assignee->id,
                'assigned_at' => now(),
            ]);
        }
    }

    /**
     * Find available assignee for incident
     */
    private function findAvailableAssignee(SecurityIncident $incident): ?User
    {
        $securityAdmins = User::whereHas('roles', function ($q) {
            $q->where('name', 'security_admin');
        })->get();
        
        // Simple round-robin assignment for now
        // In production, this would consider workload, expertise, availability
        return $securityAdmins->first();
    }

    /**
     * Get normal user resource access patterns
     */
    private function getNormalUserResources(): \Illuminate\Support\Collection
    {
        // This would typically be learned from historical data
        // For now, return basic patterns
        return collect([
            'user_1' => collect(['shipments', 'reports', 'profile']),
            'user_2' => collect(['shipments', 'customers', 'analytics']),
        ]);
    }

    /**
     * Get security dashboard metrics
     */
    public function getSecurityMetrics(string $period = '24h'): array
    {
        $startDate = match($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };
        
        return [
            'total_threats_detected' => $this->monitorSecurityThreats() ? 
                count(array_merge(...array_values($this->monitorSecurityThreats()))) : 0,
            'active_incidents' => SecurityIncident::where('status', 'open')->count(),
            'security_events' => SecurityAuditLog::where('created_at', '>=', $startDate)->count(),
            'failed_logins' => SecurityAuditLog::where('event_type', 'login')
                ->where('status', '!=', 'success')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'api_security_violations' => SecurityAuditLog::where('event_type', 'security_violation')
                ->where('resource_type', 'api')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'privilege_escalation_attempts' => SecurityAuditLog::where('event_type', 'permission_change')
                ->where('severity', 'high')
                ->where('created_at', '>=', $startDate)
                ->count(),
        ];
    }
}