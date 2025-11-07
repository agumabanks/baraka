/**
 * System Health Panel
 * Real-time system health monitoring and status display
 */

import React from 'react';
import Card from '../../ui/Card';
import LoadingSpinner from '../../ui/LoadingSpinner';
import Button from '../../ui/Button';

interface SystemHealthPanelProps {
  /** System health data */
  healthData: {
    data?: {
      status: string;
      checks: Array<{
        name: string;
        status: string;
        responseTime: number;
        message?: string;
      }>;
    };
  } | undefined;
  /** Loading state */
  loading?: boolean;
  /** Refresh handler */
  onRefresh?: () => void;
}

/**
 * System Health Panel Component
 * Displays real-time system health status and performance metrics
 */
const SystemHealthPanel: React.FC<SystemHealthPanelProps> = ({
  healthData,
  loading = false,
  onRefresh,
}) => {
  // Get status color
  const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
      case 'healthy':
      case 'pass':
      case 'ok':
        return {
          text: 'text-green-600',
          bg: 'bg-green-50',
          border: 'border-green-200',
          icon: 'fas fa-check-circle',
        };
      case 'warning':
        return {
          text: 'text-yellow-600',
          bg: 'bg-yellow-50',
          border: 'border-yellow-200',
          icon: 'fas fa-exclamation-triangle',
        };
      case 'critical':
      case 'fail':
      case 'error':
        return {
          text: 'text-red-600',
          bg: 'bg-red-50',
          border: 'border-red-200',
          icon: 'fas fa-times-circle',
        };
      default:
        return {
          text: 'text-mono-gray-600',
          bg: 'bg-mono-gray-50',
          border: 'border-mono-gray-200',
          icon: 'fas fa-question-circle',
        };
    }
  };

  // Format response time
  const formatResponseTime = (time: number) => {
    if (time < 1000) return `${time}ms`;
    return `${(time / 1000).toFixed(1)}s`;
  };

  // Get overall status
  const getOverallStatus = () => {
    if (!healthData?.data) return 'unknown';
    return healthData.data.status || 'unknown';
  };

  // Get status summary
  const getStatusSummary = () => {
    if (!healthData?.data?.checks) return { healthy: 0, total: 0 };
    
    const checks = healthData.data.checks;
    const healthy = checks.filter(check => 
      check.status.toLowerCase() === 'healthy' || 
      check.status.toLowerCase() === 'pass' || 
      check.status.toLowerCase() === 'ok'
    ).length;
    
    return { healthy, total: checks.length };
  };

  // Show loading state
  if (loading) {
    return (
      <Card className="p-6">
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-mono-black">System Health</h3>
            <LoadingSpinner size="sm" />
          </div>
          <div className="space-y-2">
            {[1, 2, 3].map((i) => (
              <div key={i} className="animate-pulse">
                <div className="h-4 bg-mono-gray-200 rounded w-3/4 mb-2" />
                <div className="h-3 bg-mono-gray-100 rounded w-1/2" />
              </div>
            ))}
          </div>
        </div>
      </Card>
    );
  }

  const overallStatus = getOverallStatus();
  const statusConfig = getStatusColor(overallStatus);
  const summary = getStatusSummary();

  return (
    <Card className="p-6">
      <div className="space-y-4">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className={`p-2 rounded-lg ${statusConfig.bg} ${statusConfig.border} border`}>
              <i className={`fas ${statusConfig.icon} ${statusConfig.text}`} aria-hidden="true" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-mono-black">System Health</h3>
              <p className="text-sm text-mono-gray-600">
                {summary.healthy} of {summary.total} services healthy
              </p>
            </div>
          </div>
          <Button
            variant="outline"
            size="sm"
            onClick={onRefresh}
            disabled={loading}
            title="Refresh system health"
          >
            <i className="fas fa-sync-alt" aria-hidden="true" />
          </Button>
        </div>

        {/* Overall Status */}
        <div className={`p-4 rounded-lg border ${statusConfig.bg} ${statusConfig.border}`}>
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-mono-black">
                Overall Status: <span className={statusConfig.text}>{overallStatus.toUpperCase()}</span>
              </p>
              {healthData?.data && (
                <p className="text-xs text-mono-gray-600 mt-1">
                  Last checked: {new Date().toLocaleTimeString()}
                </p>
              )}
            </div>
            <div className="text-right">
              <div className="text-2xl font-bold text-mono-black">
                {summary.healthy}/{summary.total}
              </div>
              <div className="text-xs text-mono-gray-500">Services Online</div>
            </div>
          </div>
        </div>

        {/* Service Status List */}
        {healthData?.data?.checks && healthData.data.checks.length > 0 ? (
          <div className="space-y-3">
            <h4 className="text-sm font-semibold text-mono-black">Service Status</h4>
            <div className="space-y-2 max-h-48 overflow-y-auto">
              {healthData.data.checks.map((check, index) => {
                const checkConfig = getStatusColor(check.status);
                return (
                  <div
                    key={index}
                    className="flex items-center justify-between p-3 bg-mono-gray-50 rounded-lg border border-mono-gray-200"
                  >
                    <div className="flex items-center gap-3">
                      <div className={`w-2 h-2 rounded-full ${checkConfig.text.replace('text-', 'bg-')}`} />
                      <div>
                        <p className="text-sm font-medium text-mono-black">
                          {check.name}
                        </p>
                        {check.message && (
                          <p className="text-xs text-mono-gray-600">
                            {check.message}
                          </p>
                        )}
                      </div>
                    </div>
                    <div className="text-right">
                      <div className={`text-sm font-medium ${checkConfig.text}`}>
                        {check.status}
                      </div>
                      <div className="text-xs text-mono-gray-500">
                        {formatResponseTime(check.responseTime)}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        ) : (
          <div className="text-center py-6">
            <i className="fas fa-info-circle text-mono-gray-400 text-2xl mb-2" />
            <p className="text-sm text-mono-gray-500">
              No service health data available
            </p>
          </div>
        )}

        {/* Quick Actions */}
        {overallStatus === 'critical' && (
          <div className="p-3 bg-red-50 border border-red-200 rounded-lg">
            <div className="flex items-center gap-2">
              <i className="fas fa-exclamation-triangle text-red-600" aria-hidden="true" />
              <span className="text-sm font-medium text-red-800">
                Critical system issues detected
              </span>
            </div>
            <p className="text-xs text-red-700 mt-1">
              Some services may be unavailable. Check individual service status above.
            </p>
          </div>
        )}

        {overallStatus === 'warning' && (
          <div className="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div className="flex items-center gap-2">
              <i className="fas fa-exclamation-triangle text-yellow-600" aria-hidden="true" />
              <span className="text-sm font-medium text-yellow-800">
                Some services have degraded performance
              </span>
            </div>
            <p className="text-xs text-yellow-700 mt-1">
              Monitor the services listed above for updates.
            </p>
          </div>
        )}
      </div>
    </Card>
  );
};

export default SystemHealthPanel;