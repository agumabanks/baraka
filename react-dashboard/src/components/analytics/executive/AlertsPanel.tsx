/**
 * Alerts Panel
 * Real-time system alerts and notifications management
 */

import React, { useState } from 'react';
import Card from '../../ui/Card';
import LoadingSpinner from '../../ui/LoadingSpinner';
import Button from '../../ui/Button';
import Badge from '../../ui/Badge';

interface AlertsPanelProps {
  /** Alerts data */
  alertsData: {
    data?: Array<{
      id: string;
      type: string;
      message: string;
      severity: string;
      timestamp: string;
      resolved: boolean;
    }>;
  } | undefined;
  /** Loading state */
  loading?: boolean;
  /** Alert acknowledgment handler */
  onAcknowledgeAlert: (alertId: string) => void;
  /** Acknowledgment loading state */
  acknowledgingAlert: boolean;
  /** Show only active alerts */
  showActiveOnly?: boolean;
  /** Maximum alerts to display */
  maxAlerts?: number;
}

/**
 * Alerts Panel Component
 * Displays and manages real-time system alerts with acknowledgment capabilities
 */
const AlertsPanel: React.FC<AlertsPanelProps> = ({
  alertsData,
  loading = false,
  onAcknowledgeAlert,
  acknowledgingAlert,
  showActiveOnly = true,
  maxAlerts = 10,
}) => {
  const [filter, setFilter] = useState<'all' | 'active' | 'critical'>('active');

  // Get severity color
  const getSeverityColor = (severity: string) => {
    switch (severity.toLowerCase()) {
      case 'critical':
        return {
          text: 'text-red-600',
          bg: 'bg-red-50',
          border: 'border-red-200',
          badge: 'bg-red-100 text-red-800',
          icon: 'fas fa-exclamation-circle',
        };
      case 'high':
        return {
          text: 'text-orange-600',
          bg: 'bg-orange-50',
          border: 'border-orange-200',
          badge: 'bg-orange-100 text-orange-800',
          icon: 'fas fa-exclamation-triangle',
        };
      case 'medium':
      case 'warning':
        return {
          text: 'text-yellow-600',
          bg: 'bg-yellow-50',
          border: 'border-yellow-200',
          badge: 'bg-yellow-100 text-yellow-800',
          icon: 'fas fa-info-circle',
        };
      case 'low':
        return {
          text: 'text-blue-600',
          bg: 'bg-blue-50',
          border: 'border-blue-200',
          badge: 'bg-blue-100 text-blue-800',
          icon: 'fas fa-info-circle',
        };
      default:
        return {
          text: 'text-mono-gray-600',
          bg: 'bg-mono-gray-50',
          border: 'border-mono-gray-200',
          badge: 'bg-mono-gray-100 text-mono-gray-800',
          icon: 'fas fa-circle',
        };
    }
  };

  // Format timestamp
  const formatTimestamp = (timestamp: string) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString();
  };

  // Filter and sort alerts
  const getFilteredAlerts = () => {
    if (!alertsData?.data) return [];
    
    let filtered = alertsData.data;
    
    // Apply active filter
    if (showActiveOnly || filter === 'active') {
      filtered = filtered.filter(alert => !alert.resolved);
    }
    
    // Apply severity filter
    if (filter === 'critical') {
      filtered = filtered.filter(alert => alert.severity.toLowerCase() === 'critical');
    }
    
    // Sort by severity and timestamp
    return filtered
      .sort((a, b) => {
        const severityOrder = { critical: 0, high: 1, medium: 2, low: 3 };
        const aSeverity = severityOrder[a.severity.toLowerCase() as keyof typeof severityOrder] ?? 4;
        const bSeverity = severityOrder[b.severity.toLowerCase() as keyof typeof severityOrder] ?? 4;
        
        if (aSeverity !== bSeverity) {
          return aSeverity - bSeverity;
        }
        
        return new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime();
      })
      .slice(0, maxAlerts);
  };

  // Get alert counts
  const getAlertCounts = () => {
    if (!alertsData?.data) return { total: 0, active: 0, critical: 0 };
    
    const total = alertsData.data.length;
    const active = alertsData.data.filter(alert => !alert.resolved).length;
    const critical = alertsData.data.filter(alert => 
      !alert.resolved && alert.severity.toLowerCase() === 'critical'
    ).length;
    
    return { total, active, critical };
  };

  const alerts = getFilteredAlerts();
  const counts = getAlertCounts();

  // Show loading state
  if (loading) {
    return (
      <Card className="p-6">
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-mono-black">System Alerts</h3>
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

  return (
    <Card className="p-6">
      <div className="space-y-4">
        {/* Header with counts and filters */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-mono-gray-100 rounded-lg">
              <i className="fas fa-bell text-mono-gray-600" aria-hidden="true" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-mono-black">System Alerts</h3>
              <p className="text-sm text-mono-gray-600">
                {counts.active} active alerts
                {counts.critical > 0 && (
                  <span className="ml-1 text-red-600 font-medium">({counts.critical} critical)</span>
                )}
              </p>
            </div>
          </div>
          
          {/* Filter buttons */}
          <div className="flex items-center gap-1">
            <Button
              variant={filter === 'active' ? 'primary' : 'outline'}
              size="xs"
              onClick={() => setFilter('active')}
            >
              Active ({counts.active})
            </Button>
            <Button
              variant={filter === 'critical' ? 'primary' : 'outline'}
              size="xs"
              onClick={() => setFilter('critical')}
            >
              Critical ({counts.critical})
            </Button>
            <Button
              variant={filter === 'all' ? 'primary' : 'outline'}
              size="xs"
              onClick={() => setFilter('all')}
            >
              All ({counts.total})
            </Button>
          </div>
        </div>

        {/* Alerts List */}
        {alerts.length > 0 ? (
          <div className="space-y-3 max-h-64 overflow-y-auto">
            {alerts.map((alert) => {
              const severityConfig = getSeverityColor(alert.severity);
              return (
                <div
                  key={alert.id}
                  className={`p-4 rounded-lg border ${alert.resolved ? 'bg-mono-gray-50 border-mono-gray-200' : severityConfig.bg + ' ' + severityConfig.border}`}
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="flex items-start gap-3 flex-1">
                      <div className={`p-1 rounded ${alert.resolved ? 'bg-mono-gray-200' : severityConfig.bg}`}>
                        <i className={`fas ${severityConfig.icon} text-sm ${alert.resolved ? 'text-mono-gray-500' : severityConfig.text}`} aria-hidden="true" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-1">
                          <Badge
                            variant={alert.severity.toLowerCase() === 'critical' ? 'error' : 'warning'}
                            size="sm"
                          >
                            {alert.severity}
                          </Badge>
                          <Badge
                            variant={alert.resolved ? 'default' : 'info'}
                            size="sm"
                          >
                            {alert.type}
                          </Badge>
                          {alert.resolved && (
                            <Badge variant="success" size="sm">
                              <i className="fas fa-check mr-1" />
                              Resolved
                            </Badge>
                          )}
                        </div>
                        <p className="text-sm text-mono-black font-medium">
                          {alert.message}
                        </p>
                        <p className="text-xs text-mono-gray-500 mt-1">
                          {formatTimestamp(alert.timestamp)}
                        </p>
                      </div>
                    </div>
                    
                    {!alert.resolved && (
                      <Button
                        variant="outline"
                        size="xs"
                        onClick={() => onAcknowledgeAlert(alert.id)}
                        disabled={acknowledgingAlert}
                        className="shrink-0"
                      >
                        {acknowledgingAlert ? (
                          <i className="fas fa-spinner fa-spin" aria-hidden="true" />
                        ) : (
                          <>
                            <i className="fas fa-check mr-1" aria-hidden="true" />
                            Acknowledge
                          </>
                        )}
                      </Button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="text-center py-8">
            <i className="fas fa-check-circle text-green-500 text-3xl mb-3" />
            <p className="text-lg font-medium text-mono-black mb-1">
              All Clear!
            </p>
            <p className="text-sm text-mono-gray-600">
              {filter === 'active' ? 'No active alerts' : 
               filter === 'critical' ? 'No critical alerts' : 
               'No alerts in the system'}
            </p>
          </div>
        )}

        {/* Alert Summary */}
        {alerts.length > 0 && (
          <div className="grid grid-cols-3 gap-3 pt-3 border-t border-mono-gray-200">
            <div className="text-center">
              <div className="text-lg font-bold text-mono-black">
                {alerts.filter(a => !a.resolved && a.severity.toLowerCase() === 'critical').length}
              </div>
              <div className="text-xs text-mono-gray-600">Critical</div>
            </div>
            <div className="text-center">
              <div className="text-lg font-bold text-mono-black">
                {alerts.filter(a => !a.resolved && ['high', 'medium'].includes(a.severity.toLowerCase())).length}
              </div>
              <div className="text-xs text-mono-gray-600">Active</div>
            </div>
            <div className="text-center">
              <div className="text-lg font-bold text-mono-black">
                {alerts.filter(a => a.resolved).length}
              </div>
              <div className="text-xs text-mono-gray-600">Resolved</div>
            </div>
          </div>
        )}

        {/* Auto-refresh indicator */}
        <div className="flex items-center justify-center gap-2 text-xs text-mono-gray-500">
          <div className="w-1 h-1 bg-mono-gray-400 rounded-full animate-pulse" />
          <span>Alerts auto-refresh every 10 seconds</span>
          <div className="w-1 h-1 bg-mono-gray-400 rounded-full animate-pulse" style={{ animationDelay: '0.5s' }} />
        </div>
      </div>
    </Card>
  );
};

export default AlertsPanel;