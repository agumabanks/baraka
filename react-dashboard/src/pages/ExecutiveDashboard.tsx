/**
 * Executive Dashboard
 * Real-time executive overview with key business metrics
 */

import React, { useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import { 
  useExecutiveKPIs, 
  useRealTimeMetrics, 
  useRealTimeWebSocket,
  useSystemHealth,
  useHealthAlerts,
  useAcknowledgeAlert
} from '../hooks/useAnalytics';
import { useAnalyticsStore } from '../stores/analyticsStore';
import ExecutiveKPIGrid from '../components/analytics/executive/ExecutiveKPIGrid';
import RealTimeMetricsCard from '../components/analytics/executive/RealTimeMetricsCard';
import ExecutiveChartSection from '../components/analytics/executive/ExecutiveChartSection';
import SystemHealthPanel from '../components/analytics/executive/SystemHealthPanel';
import AlertsPanel from '../components/analytics/executive/AlertsPanel';
import DateRangeFilter from '../components/dashboard/DateRangeFilter';
import RealTimeConnectionStatus from '../components/analytics/executive/RealTimeConnectionStatus';
import PerformanceMetricsPanel from '../components/analytics/executive/PerformanceMetricsPanel';
import { t } from '../lib/i18n';
import Can from '../components/rbac/Can';
import type { FilterConfig } from '../types/analytics';

/**
 * Executive Dashboard Page Component
 * Provides real-time executive overview of key business metrics
 */
const ExecutiveDashboard: React.FC = () => {
  const [searchParams] = useSearchParams();
  const filterDate = searchParams.get('filter_date') || '';
  
  // Store state
  const { 
    selectedFilters, 
    timeRange, 
    isRealTimeEnabled, 
    setCurrentDashboard,
    setSelectedFilters,
    addFilter
  } = useAnalyticsStore();

  // Data hooks
  const { 
    data: kpisData, 
    isLoading: kpisLoading, 
    isError: kpisError, 
    error: kpisErrorMsg,
    refetch: refetchKPIs 
  } = useExecutiveKPIs();
  
  const { 
    data: realTimeData, 
    isLoading: realTimeLoading 
  } = useRealTimeMetrics();
  
  const { 
    data: healthData, 
    isLoading: healthLoading 
  } = useSystemHealth();
  
  const { 
    data: alertsData, 
    isLoading: alertsLoading,
    refetch: refetchAlerts
  } = useHealthAlerts();

  const { 
    mutate: acknowledgeAlert,
    isPending: acknowledgingAlert 
  } = useAcknowledgeAlert();

  // Real-time WebSocket connection
  const { 
    isConnected, 
    connectionError,
    connect: reconnectWebSocket,
    disconnect: disconnectWebSocket
  } = useRealTimeWebSocket();

  // Set current dashboard in store
  useEffect(() => {
    setCurrentDashboard('executive');
  }, [setCurrentDashboard]);

  // Auto-refresh alerts when real-time data changes
  useEffect(() => {
    if (isConnected && realTimeData) {
      refetchAlerts();
    }
  }, [isConnected, realTimeData, refetchAlerts]);

  // Handle date filter
  const handleDateFilter = (date: string) => {
    // Convert date string to time range and filters
    const now = new Date();
    let start: string;
    
    switch (date) {
      case 'today':
        start = new Date(now.getFullYear(), now.getMonth(), now.getDate()).toISOString();
        break;
      case 'week':
        start = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString();
        break;
      case 'month':
        start = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString();
        break;
      default:
        return;
    }
    
    // Update time range in store
    useAnalyticsStore.getState().setTimeRange({
      start,
      end: now.toISOString(),
      preset: date,
    });
    
    // Trigger data refresh
    refetchKPIs();
  };

  // Handle KPI click for drill-down
  const handleKPIClick = (kpiId: string) => {
    console.log('KPI clicked for drill-down:', kpiId);
    // TODO: Implement drill-down functionality
  };

  // Handle filter change
  const handleFilterChange = (filters: FilterConfig[]) => {
    setSelectedFilters(filters);
    refetchKPIs();
  };

  // Handle alert acknowledgment
  const handleAcknowledgeAlert = (alertId: string) => {
    acknowledgeAlert(alertId);
  };

  // Handle connection retry
  const handleRetryConnection = () => {
    reconnectWebSocket();
  };

  // Handle connection toggle
  const handleConnectionToggle = () => {
    if (isConnected) {
      disconnectWebSocket();
    } else {
      reconnectWebSocket();
    }
  };

  // Show loading state
  if (kpisLoading && !kpisData) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h1 className="text-3xl font-bold text-mono-black">
            Executive Dashboard
          </h1>
          <RealTimeConnectionStatus
            isConnected={isConnected}
            connectionError={connectionError}
            onRetry={handleRetryConnection}
            onToggle={handleConnectionToggle}
          />
        </div>
        <LoadingSpinner message="Loading executive dashboard..." />
      </div>
    );
  }

  // Show error state
  if (kpisError && !kpisData) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h1 className="text-3xl font-bold text-mono-black">
            Executive Dashboard
          </h1>
          <RealTimeConnectionStatus
            isConnected={isConnected}
            connectionError={connectionError}
            onRetry={handleRetryConnection}
            onToggle={handleConnectionToggle}
          />
        </div>
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-bold text-mono-black mb-2">
                Failed to Load Dashboard
              </h2>
              <p className="text-mono-gray-600 mb-4">
                {kpisErrorMsg?.message || 'Unable to fetch executive dashboard data. Please try again.'}
              </p>
            </div>
            <Button
              variant="primary"
              size="md"
              onClick={() => refetchKPIs()}
            >
              <i className="fas fa-redo mr-2" aria-hidden="true" />
              Retry
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  // Ensure we have data to display
  if (!kpisData) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h1 className="text-3xl font-bold text-mono-black">
            Executive Dashboard
          </h1>
          <RealTimeConnectionStatus
            isConnected={isConnected}
            connectionError={connectionError}
            onRetry={handleRetryConnection}
            onToggle={handleConnectionToggle}
          />
        </div>
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-database text-2xl" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-2xl font-bold text-mono-black mb-2">
                No Dashboard Data Available
              </h2>
              <p className="text-mono-gray-600 mb-4">
                We could not retrieve executive dashboard metrics for the selected period.
              </p>
            </div>
            <Button
              variant="primary"
              size="md"
              onClick={() => refetchKPIs()}
            >
              <i className="fas fa-redo mr-2" aria-hidden="true" />
              Retry
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header with Connection Status */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-mono-black">
            Executive Dashboard
          </h1>
          <p className="text-mono-gray-600 mt-1">
            Real-time business intelligence and key performance indicators
          </p>
        </div>
        <div className="flex items-center gap-4">
          <RealTimeConnectionStatus
            isConnected={isConnected}
            connectionError={connectionError}
            onRetry={handleRetryConnection}
            onToggle={handleConnectionToggle}
          />
        </div>
      </div>

      {/* Date Filter and Controls */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <DateRangeFilter
            value={timeRange.preset}
            loading={kpisLoading}
            onChange={handleDateFilter}
          />
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => refetchKPIs()}
            disabled={kpisLoading}
          >
            <i className="fas fa-sync-alt mr-2" aria-hidden="true" />
            Refresh
          </Button>
          <Can permission="export_analytics">
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                // TODO: Implement export functionality
                console.log('Export executive dashboard');
              }}
            >
              <i className="fas fa-download mr-2" aria-hidden="true" />
              Export
            </Button>
          </Can>
        </div>
      </div>

      {/* Real-time Metrics Alert */}
      {isConnected && realTimeData && (
        <RealTimeMetricsCard
          metrics={realTimeData}
          loading={realTimeLoading}
        />
      )}

      {/* System Health Panel */}
      <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <SystemHealthPanel
          healthData={healthData}
          loading={healthLoading}
        />
        <AlertsPanel
          alertsData={alertsData}
          loading={alertsLoading}
          onAcknowledgeAlert={handleAcknowledgeAlert}
          acknowledgingAlert={acknowledgingAlert}
        />
      </div>

      {/* Executive KPIs Grid */}
      <section className="space-y-4">
        <header>
          <h2 className="text-lg font-semibold text-mono-black">
            Key Performance Indicators
          </h2>
          <p className="text-sm text-mono-gray-600">
            Real-time business metrics with trend analysis
          </p>
        </header>
        <ExecutiveKPIGrid
          kpis={kpisData}
          loading={kpisLoading}
          onKPIClick={handleKPIClick}
          isRealTime={isConnected}
        />
      </section>

      {/* Executive Charts Section */}
      <ExecutiveChartSection
        kpisData={kpisData}
        filters={selectedFilters}
        loading={kpisLoading}
      />

      {/* Performance Metrics Panel */}
      <PerformanceMetricsPanel />

      {/* Summary Statistics */}
      <section className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-mono-gray-600">Data Freshness</p>
              <p className="text-2xl font-bold text-mono-black">
                {realTimeData ? 'Live' : 'Manual'}
              </p>
            </div>
            <div className={`w-3 h-3 rounded-full ${isConnected ? 'bg-green-500' : 'bg-gray-400'}`} />
          </div>
          <p className="text-xs text-mono-gray-500 mt-2">
            {isConnected ? 'Connected to real-time stream' : 'Manual refresh mode'}
          </p>
        </Card>

        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-mono-gray-600">System Health</p>
              <p className="text-2xl font-bold text-mono-black">
                {healthData?.data?.status === 'healthy' ? 'Good' : 'Warning'}
              </p>
            </div>
            <div className={`w-3 h-3 rounded-full ${healthData?.data?.status === 'healthy' ? 'bg-green-500' : 'bg-yellow-500'}`} />
          </div>
          <p className="text-xs text-mono-gray-500 mt-2">
            {healthData?.data?.status || 'Unknown status'}
          </p>
        </Card>

        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-mono-gray-600">Active Alerts</p>
              <p className="text-2xl font-bold text-mono-black">
                {alertsData?.data?.filter((alert) => !alert.resolved).length || 0}
              </p>
            </div>
            <div className={`w-3 h-3 rounded-full ${(alertsData?.data?.filter((alert) => !alert.resolved).length || 0) > 0 ? 'bg-red-500' : 'bg-green-500'}`} />
          </div>
          <p className="text-xs text-mono-gray-500 mt-2">
            {alertsData?.data?.filter((alert) => alert.severity === 'critical').length || 0} critical issues
          </p>
        </Card>
      </section>
    </div>
  );
};

export default ExecutiveDashboard;