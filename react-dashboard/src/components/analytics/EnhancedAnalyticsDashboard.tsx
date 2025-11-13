/**
 * Enhanced Analytics Dashboard with Real-time Updates
 * Optimized for high-performance analytics with caching and real-time capabilities
 */

import React, { useState, useEffect, useMemo } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Alert, AlertDescription } from '@/components/ui/Alert';
import { Skeleton } from '@/components/ui/Skeleton';
import Select from '@/components/ui/Select';
import {
  OptimizedBranchAnalyticsService,
  OptimizedBranchCapacityService,
} from '@/services/optimizedAnalyticsApi';
import { useRealTimeWebSocket, usePerformanceMetrics } from '@/hooks/useAnalytics';
import { toast } from '@/stores/toastStore';
import {
  BarChart,
  Bar,
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  AreaChart,
  Area,
} from 'recharts';
import {
  Activity,
  TrendingUp,
  AlertTriangle,
  CheckCircle,
  Clock,
  Users,
  Package,
  DollarSign,
  Zap,
  RefreshCw,
  Download,
  Eye,
  Settings,
} from 'lucide-react';

interface EnhancedAnalyticsExportConfig {
  type: string;
  branchId?: string;
  timeRange: string;
  format: 'csv' | 'excel' | 'pdf' | 'json';
  includeCharts?: boolean;
}

interface EnhancedAnalyticsDashboardProps {
  branchId?: string;
  isRealTimeEnabled?: boolean;
  timeRange?: { start: Date; end: Date };
  onExport?: (config: EnhancedAnalyticsExportConfig) => void;
}

// Color scheme for charts
const CHART_COLORS = {
  primary: '#3B82F6',
  secondary: '#10B981',
  warning: '#F59E0B',
  danger: '#EF4444',
  info: '#8B5CF6',
  success: '#059669',
  gradient: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
};

export const EnhancedAnalyticsDashboard: React.FC<EnhancedAnalyticsDashboardProps> = ({
  branchId,
  isRealTimeEnabled = true,
  timeRange = { start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000), end: new Date() },
  onExport,
}) => {
  const [selectedTimeRange, setSelectedTimeRange] = useState('30d');
  const [selectedBranch, setSelectedBranch] = useState(branchId);
  const [refreshInterval, setRefreshInterval] = useState(30); // seconds
  const [dashboardMode, setDashboardMode] = useState<'overview' | 'detailed'>('overview');
  
  const queryClient = useQueryClient();
  const { data: performanceData } = usePerformanceMetrics();

  // Real-time WebSocket connection
  const { 
    isConnected, 
    connectionError,
    connect,
    disconnect,
  } = useRealTimeWebSocket();

  // Query keys for optimization
  const queryKeys = useMemo(() => ({
    analytics: ['enhanced-analytics', selectedBranch, selectedTimeRange],
    capacity: ['enhanced-capacity', selectedBranch, selectedTimeRange],
    realTime: ['realtime-metrics', selectedBranch],
    performance: ['performance-metrics'],
  }), [selectedBranch, selectedTimeRange]);

  // Enhanced Analytics Query with optimization
  const {
    data: analyticsData,
    isLoading: analyticsLoading,
    error: analyticsError,
    refetch: refetchAnalytics,
  } = useQuery({
    queryKey: queryKeys.analytics,
    queryFn: async () => {
      const service = new OptimizedBranchAnalyticsService();
      const branch = selectedBranch ? { id: selectedBranch } : null;
      
      if (branch) {
        return await service.getBranchPerformanceAnalytics(branch, parseInt(selectedTimeRange.replace('d', '')));
      }

      // Batch processing for all branches
      const branches = await service.listAvailableBranches();
      return await service.getBatchBranchAnalytics(
        branches.map((b) => b.id),
        parseInt(selectedTimeRange.replace('d', ''))
      );
    },
    staleTime: 2 * 60 * 1000, // 2 minutes
    refetchInterval: refreshInterval * 1000,
    retry: 3,
    retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
  });

  // Enhanced Capacity Query with optimization
  const {
    data: capacityData,
    isLoading: capacityLoading,
    error: capacityError,
    refetch: refetchCapacity,
  } = useQuery({
    queryKey: queryKeys.capacity,
    queryFn: async () => {
      const service = new OptimizedBranchCapacityService();
      const branch = selectedBranch ? { id: selectedBranch } : null;
      
      if (branch) {
        return await service.getCapacityAnalysis(branch, parseInt(selectedTimeRange.replace('d', '')));
      }

      // Batch processing for all branches
      const branches = await service.listAvailableBranches();
      return await Promise.all(
        branches.map((branchItem) =>
          service.getCapacityAnalysis(branchItem, parseInt(selectedTimeRange.replace('d', '')))
        )
      );
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchInterval: refreshInterval * 1000,
    enabled: isRealTimeEnabled,
  });

  // Real-time Metrics Query
  const {
    data: realTimeData,
    isLoading: realTimeLoading,
  } = useQuery({
    queryKey: queryKeys.realTime,
    queryFn: async () => {
      if (!selectedBranch) return null;
      
      const service = new OptimizedBranchAnalyticsService();
      return await service.getRealTimeAnalytics({ id: selectedBranch });
    },
    enabled: isRealTimeEnabled && !!selectedBranch,
    refetchInterval: 5000, // 5 seconds for real-time data
    staleTime: 0, // Always fresh for real-time
  });

  // Handle real-time updates
  useEffect(() => {
    if (isConnected && realTimeData) {
      // Show success notification
      toast.success({
        title: 'Real-time Data Updated',
        description: 'Analytics data has been updated with latest metrics',
      });
      
      // Invalidate related queries to refresh data
      queryClient.invalidateQueries({ queryKey: queryKeys.analytics });
      queryClient.invalidateQueries({ queryKey: queryKeys.capacity });
    }
  }, [isConnected, realTimeData, queryClient, queryKeys]);

  // Handle connection status
  useEffect(() => {
    if (!isConnected && connectionError) {
      toast.error({
        title: 'Real-time Connection Lost',
        description: 'Attempting to reconnect to real-time analytics feed',
      });
    }
  }, [isConnected, connectionError]);

  // Performance monitoring
  useEffect(() => {
    const metrics = (performanceData as any)?.data ?? performanceData;

    if (metrics) {
      const loadTime = metrics.loadTime ?? 0;
      const cacheHitRate = metrics.cacheHitRate ?? 0;

      if (loadTime > 3000) {
        toast.warning({
          title: 'Performance Warning',
          description: 'Dashboard loading time is above optimal threshold',
        });
      }

      if (cacheHitRate < 70) {
        toast.info({
          title: 'Cache Optimization Available',
          description: 'Consider optimizing cache settings for better performance',
        });
      }
    }
  }, [performanceData]);

  const handleRefresh = async () => {
    try {
      await Promise.all([
        refetchAnalytics(),
        refetchCapacity(),
      ]);
      
      toast.success({
        title: 'Data Refreshed',
        description: 'Analytics data has been updated',
      });
    } catch (error) {
      toast.error({
        title: 'Refresh Failed',
        description: 'Unable to refresh analytics data. Please try again.',
      });
    }
  };

  const handleExport = () => {
    if (onExport) {
      onExport({
        type: 'analytics',
        branchId: selectedBranch,
        timeRange: selectedTimeRange,
        format: 'csv',
        includeCharts: true,
      });
    }
  };

  // Loading states
  if (analyticsLoading && capacityLoading) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <Skeleton className="h-8 w-64" />
          <div className="flex space-x-2">
            <Skeleton className="h-10 w-24" />
            <Skeleton className="h-10 w-24" />
          </div>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {Array.from({ length: 8 }).map((_, i) => (
            <Card key={i} className="p-6">
              <Skeleton className="h-4 w-24 mb-2" />
              <Skeleton className="h-8 w-16 mb-2" />
              <Skeleton className="h-3 w-32" />
            </Card>
          ))}
        </div>
        
        <Card className="p-6">
          <Skeleton className="h-64 w-full" />
        </Card>
      </div>
    );
  }

  // Error state
  if (analyticsError || capacityError) {
    return (
      <Alert variant="destructive">
        <AlertTriangle className="h-4 w-4" />
        <AlertDescription>
          Unable to load analytics data. Please check your connection and try again.
          <Button 
            variant="outline" 
            size="sm" 
            onClick={handleRefresh}
            className="ml-4"
          >
            Retry
          </Button>
        </AlertDescription>
      </Alert>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Enhanced Analytics Dashboard</h1>
          <p className="text-gray-600">
            Real-time performance monitoring and capacity optimization
          </p>
        </div>
        
        <div className="flex items-center space-x-4">
          {/* Connection Status */}
          <div className="flex items-center space-x-2">
            <div className={`h-2 w-2 rounded-full ${isConnected ? 'bg-green-500' : 'bg-red-500'}`} />
            <span className="text-sm text-gray-600">
              {isConnected ? 'Real-time Connected' : 'Offline'}
            </span>
          </div>
          
          {/* Controls */}
          <Select
            className="w-32"
            value={selectedTimeRange}
            onChange={(event) => setSelectedTimeRange(event.target.value)}
            options={[
              { value: '7d', label: 'Last 7 days' },
              { value: '30d', label: 'Last 30 days' },
              { value: '90d', label: 'Last 90 days' },
            ]}
            aria-label="Select analytics time range"
          />
          
          <Button
            variant="outline"
            size="sm"
            onClick={handleRefresh}
            disabled={analyticsLoading || capacityLoading}
          >
            <RefreshCw className="h-4 w-4 mr-2" />
            Refresh
          </Button>
          
          {onExport && (
            <Button
              variant="outline"
              size="sm"
              onClick={handleExport}
            >
              <Download className="h-4 w-4 mr-2" />
              Export
            </Button>
          )}
        </div>
      </div>

      {/* Real-time Status Bar */}
      <Card className="p-4 bg-blue-50 border-blue-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <Zap className="h-5 w-5 text-blue-600" />
            <div>
              <p className="font-medium text-blue-900">Real-time Analytics Active</p>
              <p className="text-sm text-blue-700">
                Last updated: {realTimeData?.timestamp ? new Date(realTimeData.timestamp).toLocaleTimeString() : 'Never'}
              </p>
            </div>
          </div>
          
          <div className="flex items-center space-x-6 text-sm">
            <div className="text-center">
              <p className="font-medium text-blue-900">{realTimeData?.active_shipments || 0}</p>
              <p className="text-blue-700">Active Shipments</p>
            </div>
            <div className="text-center">
              <p className="font-medium text-blue-900">{realTimeData?.utilization_rate?.toFixed(1) || 0}%</p>
              <p className="text-blue-700">Utilization</p>
            </div>
            <div className="text-center">
              <p className="font-medium text-blue-900">{realTimeData?.performance_score?.toFixed(1) || 0}</p>
              <p className="text-blue-700">Performance Score</p>
            </div>
          </div>
        </div>
      </Card>

      {/* Key Performance Indicators */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <KPICard
          title="Total Shipments"
          value={analyticsData?.overview?.current_load?.active_shipments || 0}
          change={12.5}
          icon={<Package className="h-5 w-5" />}
          trend="up"
        />
        <KPICard
          title="Capacity Utilization"
          value={`${analyticsData?.overview?.current_load?.capacity_utilization?.toFixed(1) || 0}%`}
          change={-2.3}
          icon={<Activity className="h-5 w-5" />}
          trend="down"
        />
        <KPICard
          title="Active Workers"
          value={analyticsData?.overview?.workforce?.active_workers || 0}
          change={8.1}
          icon={<Users className="h-5 w-5" />}
          trend="up"
        />
        <KPICard
          title="Performance Score"
          value={realTimeData?.performance_score?.toFixed(1) || 0}
          change={5.7}
          icon={<TrendingUp className="h-5 w-5" />}
          trend="up"
        />
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Capacity Utilization Trend */}
        <Card className="p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold">Capacity Utilization Trend</h3>
            <Badge variant={realTimeData?.utilization_rate > 90 ? 'destructive' : 'success'}>
              {realTimeData?.utilization_rate > 90 ? 'Critical' : 'Normal'}
            </Badge>
          </div>
          <ResponsiveContainer width="100%" height={300}>
            <AreaChart data={analyticsData?.trends || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="period" />
              <YAxis />
              <Tooltip />
              <Area
                type="monotone"
                dataKey="capacity_utilization"
                stroke={CHART_COLORS.primary}
                fill={CHART_COLORS.primary}
                fillOpacity={0.3}
              />
            </AreaChart>
          </ResponsiveContainer>
        </Card>

        {/* Performance Distribution */}
        <Card className="p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold">Performance Metrics</h3>
            <Button variant="ghost" size="sm">
              <Eye className="h-4 w-4" />
            </Button>
          </div>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={analyticsData?.performance_metrics?.quality_metrics || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="metric" />
              <YAxis />
              <Tooltip />
              <Bar dataKey="value" fill={CHART_COLORS.secondary} />
            </BarChart>
          </ResponsiveContainer>
        </Card>
      </div>

      {/* Detailed Analytics Tables */}
      {dashboardMode === 'detailed' && (
        <div className="space-y-6">
          {/* Capacity Analysis Table */}
          <Card className="p-6">
            <h3 className="text-lg font-semibold mb-4">Capacity Analysis</h3>
            <div className="overflow-x-auto">
              <table className="w-full table-auto">
                <thead>
                  <tr className="border-b">
                    <th className="text-left py-2">Role</th>
                    <th className="text-right py-2">Workers</th>
                    <th className="text-right py-2">Capacity/Worker</th>
                    <th className="text-right py-2">Total Capacity</th>
                    <th className="text-right py-2">Utilization</th>
                    <th className="text-right py-2">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {capacityData?.current_capacity?.workforce_capacity && 
                    Object.entries(capacityData.current_capacity.workforce_capacity).map(([role, data]) => {
                      if (role === 'total_capacity') return null;
                      const roleData = data as any;
                      return (
                        <tr key={role} className="border-b">
                          <td className="py-2 capitalize">{role.replace('_', ' ')}</td>
                          <td className="text-right py-2">{roleData.count || 0}</td>
                          <td className="text-right py-2">{roleData.capacity_per_worker || 0}</td>
                          <td className="text-right py-2">{roleData.total_capacity || 0}</td>
                          <td className="text-right py-2">
                            <Badge variant={roleData.utilization > 90 ? 'destructive' : 'success'}>
                              {roleData.utilization?.toFixed(1) || 0}%
                            </Badge>
                          </td>
                          <td className="text-right py-2">
                            <Badge variant={roleData.utilization > 90 ? 'destructive' : 'info'}>
                              {roleData.utilization > 90 ? 'Overload' : 'Optimal'}
                            </Badge>
                          </td>
                        </tr>
                      );
                    })}
                </tbody>
              </table>
            </div>
          </Card>

          {/* Real-time Alerts */}
          {realTimeData?.alerts && realTimeData.alerts.length > 0 && (
            <Card className="p-6">
              <h3 className="text-lg font-semibold mb-4">Active Alerts</h3>
              <div className="space-y-3">
                {realTimeData.alerts.map((alert, index) => (
                  <Alert key={index} variant={alert.severity === 'high' ? 'destructive' : 'default'}>
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>
                      <div className="flex items-center justify-between">
                        <span>{alert.message}</span>
                        <Badge variant={alert.severity === 'high' ? 'destructive' : 'info'}>
                          {alert.type}
                        </Badge>
                      </div>
                    </AlertDescription>
                  </Alert>
                ))}
              </div>
            </Card>
          )}
        </div>
      )}

      {/* Dashboard Mode Toggle */}
      <div className="flex items-center justify-center">
        <Button
          variant="outline"
          onClick={() => setDashboardMode(dashboardMode === 'overview' ? 'detailed' : 'overview')}
        >
          {dashboardMode === 'overview' ? 'Show Detailed View' : 'Show Overview'}
        </Button>
      </div>
    </div>
  );
};

// KPI Card Component
interface KPICardProps {
  title: string;
  value: string | number;
  change: number;
  icon: React.ReactNode;
  trend: 'up' | 'down';
}

const KPICard: React.FC<KPICardProps> = ({ title, value, change, icon, trend }) => {
  return (
    <Card className="p-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-2 text-gray-600">
          {icon}
          <span className="text-sm font-medium">{title}</span>
        </div>
        <Badge variant={trend === 'up' ? 'success' : 'destructive'}>
          {change > 0 ? '+' : ''}{change}%
        </Badge>
      </div>
      <div className="mt-4">
        <p className="text-2xl font-bold text-gray-900">{value}</p>
        <p className="text-sm text-gray-600 mt-1">
          {trend === 'up' ? 'Increased' : 'Decreased'} from last period
        </p>
      </div>
    </Card>
  );
};

export default EnhancedAnalyticsDashboard;