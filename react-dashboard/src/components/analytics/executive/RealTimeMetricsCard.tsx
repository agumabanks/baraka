/**
 * Real-time Metrics Card
 * Live display of critical operational metrics with real-time updates
 */

import React, { useEffect, useState } from 'react';
import Card from '../../ui/Card';
import LoadingSpinner from '../../ui/LoadingSpinner';
import type { RealTimeMetrics } from '../../../types/analytics';

interface RealTimeMetricsCardProps {
  /** Real-time metrics data */
  metrics: RealTimeMetrics;
  /** Loading state */
  loading?: boolean;
  /** Auto-hide after period of inactivity */
  autoHide?: boolean;
  /** Hide after milliseconds of inactivity */
  hideAfter?: number;
}

/**
 * Real-time Metrics Card Component
 * Displays critical live metrics with real-time updates and visual indicators
 */
const RealTimeMetricsCard: React.FC<RealTimeMetricsCardProps> = ({
  metrics,
  loading = false,
  autoHide = false,
  hideAfter = 30000, // 30 seconds
}) => {
  const [isVisible, setIsVisible] = useState(true);
  const [timeSinceUpdate, setTimeSinceUpdate] = useState(0);
  const [lastMetrics, setLastMetrics] = useState<RealTimeMetrics | null>(null);

  // Auto-hide logic
  useEffect(() => {
    if (autoHide && isVisible) {
      const timer = setTimeout(() => {
        setIsVisible(false);
      }, hideAfter);

      return () => clearTimeout(timer);
    }
  }, [autoHide, isVisible, hideAfter, metrics]);

  // Track time since last update
  useEffect(() => {
    if (JSON.stringify(metrics) !== JSON.stringify(lastMetrics)) {
      setLastMetrics(metrics);
      setTimeSinceUpdate(0);
    }

    const interval = setInterval(() => {
      setTimeSinceUpdate((prev) => prev + 1);
    }, 1000);

    return () => clearInterval(interval);
  }, [metrics, lastMetrics]);

  // Calculate system health color
  const getHealthColor = (health: string) => {
    switch (health) {
      case 'healthy':
        return 'text-green-600 bg-green-50 border-green-200';
      case 'warning':
        return 'text-yellow-600 bg-yellow-50 border-yellow-200';
      case 'critical':
        return 'text-red-600 bg-red-50 border-red-200';
      default:
        return 'text-mono-gray-600 bg-mono-gray-50 border-mono-gray-200';
    }
  };

  // Format timestamp
  const formatTimestamp = (timestamp: string) => {
    const date = new Date(timestamp);
    return date.toLocaleTimeString();
  };

  // Format delivery time
  const formatDeliveryTime = (time: number) => {
    if (time < 60) return `${time}m`;
    if (time < 1440) return `${Math.floor(time / 60)}h ${time % 60}m`;
    return `${Math.floor(time / 1440)}d ${Math.floor((time % 1440) / 60)}h`;
  };

  // Show loading state
  if (loading) {
    return (
      <Card className="p-6">
        <div className="flex items-center justify-center">
          <LoadingSpinner message="Loading real-time metrics..." />
        </div>
      </Card>
    );
  }

  // Auto-hide if enabled and not visible
  if (autoHide && !isVisible) {
    return (
      <Card className="p-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
            <span className="text-sm text-mono-gray-600">Real-time metrics</span>
          </div>
          <button
            onClick={() => setIsVisible(true)}
            className="text-xs text-mono-gray-500 hover:text-mono-gray-700"
          >
            Show
          </button>
        </div>
      </Card>
    );
  }

  return (
    <Card className="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500">
      <div className="space-y-4">
        {/* Header with status and timestamp */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="relative">
              <div className="w-3 h-3 bg-green-500 rounded-full animate-pulse" />
              <div className="absolute inset-0 w-3 h-3 bg-green-500 rounded-full animate-ping opacity-75" />
            </div>
            <h3 className="text-lg font-semibold text-mono-black">
              Live Operations
            </h3>
            <span className={`px-2 py-1 text-xs font-medium rounded-full border ${getHealthColor(metrics.systemHealth)}`}>
              {metrics.systemHealth.toUpperCase()}
            </span>
          </div>
          <div className="text-right">
            <div className="text-sm font-medium text-mono-black">
              {formatTimestamp(metrics.timestamp)}
            </div>
            <div className="text-xs text-mono-gray-500">
              {timeSinceUpdate}s ago
            </div>
          </div>
        </div>

        {/* Metrics Grid */}
        <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
          {/* Active Operations */}
          <div className="bg-white rounded-lg p-4 border border-mono-gray-200">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-mono-gray-600 uppercase tracking-wide">
                  Active Operations
                </p>
                <p className="text-2xl font-bold text-mono-black">
                  {metrics.activeOperations.toLocaleString()}
                </p>
              </div>
              <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <i className="fas fa-cogs text-blue-600" aria-hidden="true" />
              </div>
            </div>
          </div>

          {/* Pending Shipments */}
          <div className="bg-white rounded-lg p-4 border border-mono-gray-200">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-mono-gray-600 uppercase tracking-wide">
                  Pending Shipments
                </p>
                <p className="text-2xl font-bold text-mono-black">
                  {metrics.pendingShipments.toLocaleString()}
                </p>
              </div>
              <div className="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i className="fas fa-clock text-yellow-600" aria-hidden="true" />
              </div>
            </div>
          </div>

          {/* Completed Deliveries */}
          <div className="bg-white rounded-lg p-4 border border-mono-gray-200">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-mono-gray-600 uppercase tracking-wide">
                  Completed Today
                </p>
                <p className="text-2xl font-bold text-green-600">
                  {metrics.completedDeliveries.toLocaleString()}
                </p>
              </div>
              <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <i className="fas fa-check-circle text-green-600" aria-hidden="true" />
              </div>
            </div>
          </div>

          {/* Average Delivery Time */}
          <div className="bg-white rounded-lg p-4 border border-mono-gray-200">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-mono-gray-600 uppercase tracking-wide">
                  Avg. Delivery Time
                </p>
                <p className="text-2xl font-bold text-mono-black">
                  {formatDeliveryTime(metrics.averageDeliveryTime)}
                </p>
              </div>
              <div className="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <i className="fas fa-route text-purple-600" aria-hidden="true" />
              </div>
            </div>
          </div>

          {/* Customer Satisfaction */}
          <div className="bg-white rounded-lg p-4 border border-mono-gray-200">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-mono-gray-600 uppercase tracking-wide">
                  Customer Satisfaction
                </p>
                <p className="text-2xl font-bold text-mono-black">
                  {metrics.customerSatisfactionScore}%
                </p>
              </div>
              <div className="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                <i className="fas fa-heart text-pink-600" aria-hidden="true" />
              </div>
            </div>
          </div>
        </div>

        {/* Performance Indicators */}
        <div className="grid grid-cols-3 gap-4">
          {/* Efficiency Indicator */}
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 bg-blue-500 rounded-full" />
            <span className="text-sm text-mono-gray-600">
              Efficiency: 
              <span className="ml-1 font-medium text-mono-black">
                {((metrics.completedDeliveries / Math.max(metrics.activeOperations + metrics.pendingShipments, 1)) * 100).toFixed(1)}%
              </span>
            </span>
          </div>

          {/* Volume Indicator */}
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 bg-green-500 rounded-full" />
            <span className="text-sm text-mono-gray-600">
              Volume: 
              <span className="ml-1 font-medium text-mono-black">
                {(metrics.completedDeliveries + metrics.pendingShipments).toLocaleString()}
              </span>
            </span>
          </div>

          {/* Quality Indicator */}
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 bg-pink-500 rounded-full" />
            <span className="text-sm text-mono-gray-600">
              Quality: 
              <span className="ml-1 font-medium text-mono-black">
                {metrics.customerSatisfactionScore > 85 ? 'Excellent' : 
                 metrics.customerSatisfactionScore > 70 ? 'Good' : 'Fair'}
              </span>
            </span>
          </div>
        </div>

        {/* Real-time Status Bar */}
        <div className="flex items-center justify-between pt-3 border-t border-mono-gray-200">
          <div className="flex items-center gap-2">
            <div className="flex items-center gap-1">
              <div className="w-1 h-1 bg-green-500 rounded-full animate-pulse" />
              <div className="w-1 h-1 bg-green-500 rounded-full animate-pulse" style={{ animationDelay: '0.2s' }} />
              <div className="w-1 h-1 bg-green-500 rounded-full animate-pulse" style={{ animationDelay: '0.4s' }} />
            </div>
            <span className="text-xs text-mono-gray-500">
              Real-time data streaming
            </span>
          </div>
          
          {autoHide && (
            <button
              onClick={() => setIsVisible(false)}
              className="text-xs text-mono-gray-400 hover:text-mono-gray-600"
            >
              <i className="fas fa-times" aria-hidden="true" />
            </button>
          )}
        </div>
      </div>
    </Card>
  );
};

export default RealTimeMetricsCard;