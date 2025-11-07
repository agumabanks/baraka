/**
 * Analytics React Hooks
 * Custom hooks for data fetching, real-time updates, and analytics operations
 */

import { useQuery, useQueryClient, useMutation } from '@tanstack/react-query';
import { useEffect, useRef, useCallback, useMemo } from 'react';
import { useAnalyticsStore } from '../stores/analyticsStore';
import { toast } from '../stores/toastStore';
import analyticsApi from '../services/analyticsApi';
import type {
  FilterConfig,
  ExportConfig,
  DashboardLayout,
  RealTimeMetrics,
  StreamingMetrics,
  WebSocketMessage
} from '../types/analytics';

// Query keys for React Query
export const queryKeys = {
  executive: {
    kpis: ['executive', 'kpis'] as const,
    realTimeMetrics: ['executive', 'real-time-metrics'] as const,
    kpisWithFilters: (filters: FilterConfig[]) => ['executive', 'kpis', 'filtered', filters] as const,
  },
  operational: {
    metrics: ['operational', 'metrics'] as const,
    originDestination: ['operational', 'origin-destination'] as const,
    routeEfficiency: ['operational', 'route-efficiency'] as const,
    onTimeDelivery: ['operational', 'on-time-delivery'] as const,
    exceptionAnalysis: ['operational', 'exception-analysis'] as const,
    driverPerformance: ['operational', 'driver-performance'] as const,
    containerUtilization: ['operational', 'container-utilization'] as const,
    transitTimeAnalysis: ['operational', 'transit-time-analysis'] as const,
  },
  financial: {
    metrics: ['financial', 'metrics'] as const,
    revenueRecognition: ['financial', 'revenue-recognition'] as const,
    cogsAnalysis: ['financial', 'cogs-analysis'] as const,
    grossMarginAnalysis: ['financial', 'gross-margin'] as const,
    codCollection: ['financial', 'cod-collection'] as const,
    paymentProcessing: ['financial', 'payment-processing'] as const,
    profitabilityAnalysis: ['financial', 'profitability-analysis'] as const,
  },
  customer: {
    intelligence: ['customer', 'intelligence'] as const,
    activityMonitoring: ['customer', 'activity-monitoring'] as const,
    dormantAccounts: ['customer', 'dormant-accounts'] as const,
    valueAnalysis: ['customer', 'value-analysis'] as const,
    sentimentAnalysis: ['customer', 'sentiment-analysis'] as const,
    satisfactionMetrics: ['customer', 'satisfaction-metrics'] as const,
    clvCalculations: ['customer', 'clv-calculations'] as const,
    churnPrediction: ['customer', 'churn-prediction'] as const,
    customerSegmentation: ['customer', 'segmentation'] as const,
    automatedAlerts: ['customer', 'automated-alerts'] as const,
  },
  dashboard: {
    layouts: ['dashboard', 'layouts'] as const,
    layout: (id: string) => ['dashboard', 'layout', id] as const,
    widgetTemplates: ['dashboard', 'widget-templates'] as const,
  },
  filters: {
    options: (module: string) => ['filters', 'options', module] as const,
    presets: ['filters', 'presets'] as const,
  },
  geographic: {
    mapData: ['geographic', 'map-data'] as const,
    heatMapData: (module: string) => ['geographic', 'heatmap', module] as const,
    routes: (ids: string[]) => ['geographic', 'routes', ids] as const,
  },
  performance: {
    metrics: ['performance', 'metrics'] as const,
    recommendations: ['performance', 'recommendations'] as const,
  },
  health: {
    systemHealth: ['health', 'system'] as const,
    alerts: ['health', 'alerts'] as const,
  },
  streaming: {
    metrics: ['streaming', 'metrics'] as const,
    status: ['streaming', 'status'] as const,
  },
};

// Executive Dashboard Hooks
export const useExecutiveKPIs = (filters?: FilterConfig[]) => {
  const queryKey = useMemo(
    () => (filters ? queryKeys.executive.kpisWithFilters(filters) : queryKeys.executive.kpis),
    [filters]
  );

  return useQuery({
    queryKey,
    queryFn: async () => {
      const response = filters 
        ? await analyticsApi.executive.getKPIsWithFilters(filters)
        : await analyticsApi.executive.getKPIs();
      return response.data;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchInterval: 30 * 1000, // 30 seconds
  });
};

export const useRealTimeMetrics = () => {
  const { isRealTimeEnabled } = useRealTimeSettings();
  
  return useQuery({
    queryKey: queryKeys.executive.realTimeMetrics,
    queryFn: async () => {
      const response = await analyticsApi.executive.getRealTimeMetrics();
      return response.data;
    },
    enabled: isRealTimeEnabled,
    refetchInterval: isRealTimeEnabled ? 5 * 1000 : false, // 5 seconds
    staleTime: 0, // Always fresh for real-time data
  });
};

// Operational Reporting Hooks
export const useOperationalMetrics = () => {
  return useQuery({
    queryKey: queryKeys.operational.metrics,
    queryFn: () => analyticsApi.operational.getMetrics(),
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
};

export const useOriginDestinationAnalytics = (filters?: FilterConfig[]) => {
  return useQuery({
    queryKey: [...queryKeys.operational.originDestination, filters],
    queryFn: () => analyticsApi.operational.getOriginDestinationAnalytics(filters),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useRouteEfficiency = () => {
  return useQuery({
    queryKey: queryKeys.operational.routeEfficiency,
    queryFn: () => analyticsApi.operational.getRouteEfficiency(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useOnTimeDelivery = (filters?: FilterConfig[]) => {
  return useQuery({
    queryKey: [...queryKeys.operational.onTimeDelivery, filters],
    queryFn: () => analyticsApi.operational.getOnTimeDelivery(filters),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useExceptionAnalysis = () => {
  return useQuery({
    queryKey: queryKeys.operational.exceptionAnalysis,
    queryFn: () => analyticsApi.operational.getExceptionAnalysis(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useDriverPerformance = () => {
  return useQuery({
    queryKey: queryKeys.operational.driverPerformance,
    queryFn: () => analyticsApi.operational.getDriverPerformance(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useContainerUtilization = () => {
  return useQuery({
    queryKey: queryKeys.operational.containerUtilization,
    queryFn: () => analyticsApi.operational.getContainerUtilization(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useTransitTimeAnalysis = () => {
  return useQuery({
    queryKey: queryKeys.operational.transitTimeAnalysis,
    queryFn: () => analyticsApi.operational.getTransitTimeAnalysis(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

// Financial Reporting Hooks
export const useFinancialMetrics = () => {
  return useQuery({
    queryKey: queryKeys.financial.metrics,
    queryFn: () => analyticsApi.financial.getMetrics(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useRevenueRecognition = (filters?: FilterConfig[]) => {
  return useQuery({
    queryKey: [...queryKeys.financial.revenueRecognition, filters],
    queryFn: () => analyticsApi.financial.getRevenueRecognition(filters),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useCOGSAnalysis = () => {
  return useQuery({
    queryKey: queryKeys.financial.cogsAnalysis,
    queryFn: () => analyticsApi.financial.getCOGSAnalysis(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useGrossMarginAnalysis = (filters?: FilterConfig[]) => {
  return useQuery({
    queryKey: [...queryKeys.financial.grossMarginAnalysis, filters],
    queryFn: () => analyticsApi.financial.getGrossMarginAnalysis(filters),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useCODCollection = () => {
  return useQuery({
    queryKey: queryKeys.financial.codCollection,
    queryFn: () => analyticsApi.financial.getCODCollection(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const usePaymentProcessing = () => {
  return useQuery({
    queryKey: queryKeys.financial.paymentProcessing,
    queryFn: () => analyticsApi.financial.getPaymentProcessing(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useProfitabilityAnalysis = () => {
  return useQuery({
    queryKey: queryKeys.financial.profitabilityAnalysis,
    queryFn: () => analyticsApi.financial.getProfitabilityAnalysis(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

// Customer Intelligence Hooks
export const useCustomerIntelligence = () => {
  return useQuery({
    queryKey: queryKeys.customer.intelligence,
    queryFn: () => analyticsApi.customer.getIntelligence(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useActivityMonitoring = () => {
  return useQuery({
    queryKey: queryKeys.customer.activityMonitoring,
    queryFn: () => analyticsApi.customer.getActivityMonitoring(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useDormantAccounts = () => {
  return useQuery({
    queryKey: queryKeys.customer.dormantAccounts,
    queryFn: () => analyticsApi.customer.getDormantAccounts(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useValueAnalysis = () => {
  return useQuery({
    queryKey: queryKeys.customer.valueAnalysis,
    queryFn: () => analyticsApi.customer.getValueAnalysis(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useSentimentAnalysis = () => {
  return useQuery({
    queryKey: queryKeys.customer.sentimentAnalysis,
    queryFn: () => analyticsApi.customer.getSentimentAnalysis(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useSatisfactionMetrics = () => {
  return useQuery({
    queryKey: queryKeys.customer.satisfactionMetrics,
    queryFn: () => analyticsApi.customer.getSatisfactionMetrics(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useCLVCalculations = () => {
  return useQuery({
    queryKey: queryKeys.customer.clvCalculations,
    queryFn: () => analyticsApi.customer.getCLVCalculations(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useChurnPrediction = () => {
  return useQuery({
    queryKey: queryKeys.customer.churnPrediction,
    queryFn: () => analyticsApi.customer.getChurnPrediction(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useCustomerSegmentation = () => {
  return useQuery({
    queryKey: queryKeys.customer.customerSegmentation,
    queryFn: () => analyticsApi.customer.getCustomerSegmentation(),
    staleTime: 10 * 60 * 1000, // 10 minutes
  });
};

export const useAutomatedAlerts = () => {
  return useQuery({
    queryKey: queryKeys.customer.automatedAlerts,
    queryFn: () => analyticsApi.customer.getAutomatedAlerts(),
    staleTime: 1 * 60 * 1000, // 1 minute
    refetchInterval: 60 * 1000, // Refresh every minute
  });
};

// Dashboard Management Hooks
export const useDashboardLayouts = () => {
  return useQuery({
    queryKey: queryKeys.dashboard.layouts,
    queryFn: () => analyticsApi.dashboard.getLayouts(),
    staleTime: 30 * 1000, // 30 seconds
  });
};

export const useDashboardLayout = (layoutId: string) => {
  return useQuery({
    queryKey: queryKeys.dashboard.layout(layoutId),
    queryFn: () => analyticsApi.dashboard.getLayout(layoutId),
    enabled: !!layoutId,
  });
};

export const useWidgetTemplates = () => {
  return useQuery({
    queryKey: queryKeys.dashboard.widgetTemplates,
    queryFn: () => analyticsApi.dashboard.getWidgetTemplates(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

// Filter Management Hooks
export const useFilterOptions = (module: string) => {
  return useQuery({
    queryKey: queryKeys.filters.options(module),
    queryFn: () => analyticsApi.filter.getFilterOptions(module),
    staleTime: 30 * 60 * 1000, // 30 minutes
  });
};

export const useFilterPresets = () => {
  return useQuery({
    queryKey: queryKeys.filters.presets,
    queryFn: () => analyticsApi.filter.getFilterPresets(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

// Real-time WebSocket Hook
export const useRealTimeWebSocket = () => {
  const { isRealTimeEnabled, autoRefresh } = useRealTimeSettings();
  const { updateRealTimeMetrics, updateStreamingMetrics, setConnectionStatus } = useAnalyticsStore();
  const queryClient = useQueryClient();
  const wsRef = useRef<WebSocket | null>(null);
  const reconnectTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const reconnectAttempts = useRef(0);
  const maxReconnectAttempts = 5;

  const connect = useCallback(async () => {
    if (!isRealTimeEnabled) return;

    try {
      const config = await analyticsApi.streaming.getWebSocketConfig();
      const { url, protocols } = config.data;

      wsRef.current = new WebSocket(url, protocols);

      wsRef.current.onopen = () => {
        setConnectionStatus(true, null);
        reconnectAttempts.current = 0;
        console.log('WebSocket connected for real-time analytics');
      };

      wsRef.current.onmessage = (event) => {
        try {
          const message: WebSocketMessage = JSON.parse(event.data);
          handleRealTimeMessage(message);
        } catch (error) {
          console.error('Failed to parse WebSocket message:', error);
        }
      };

      wsRef.current.onclose = () => {
        setConnectionStatus(false, 'Connection closed');
        scheduleReconnect();
      };

      wsRef.current.onerror = (error) => {
        console.error('WebSocket error:', error);
        setConnectionStatus(false, 'Connection error');
      };
    } catch (error) {
      console.error('Failed to connect WebSocket:', error);
      setConnectionStatus(false, 'Failed to connect');
      scheduleReconnect();
    }
  }, [isRealTimeEnabled, setConnectionStatus]);

  const handleRealTimeMessage = useCallback((message: WebSocketMessage) => {
    switch (message.type) {
      case 'metric':
        if (message.payload && typeof message.payload === 'object') {
          // Update real-time metrics
          if ('timestamp' in message.payload && 'activeOperations' in message.payload) {
            updateRealTimeMetrics(message.payload as RealTimeMetrics);
          }
          // Update streaming metrics
          if ('connections' in message.payload && 'updatesPerSecond' in message.payload) {
            updateStreamingMetrics(message.payload as StreamingMetrics);
          }
        }
        break;

      case 'alert':
        toast.info({
          title: 'Real-time Alert',
          description: JSON.stringify(message.payload),
        });
        break;

      case 'update':
        // Invalidate related queries to refresh data
        if (message.payload && typeof message.payload === 'object') {
          const { module } = message.payload as { module: string };
          if (module) {
            queryClient.invalidateQueries({ queryKey: [module] });
          }
        }
        break;

      default:
        console.log('Unknown WebSocket message type:', message.type);
    }
  }, [updateRealTimeMetrics, updateStreamingMetrics, queryClient]);

  const scheduleReconnect = useCallback(() => {
    if (reconnectAttempts.current >= maxReconnectAttempts) {
      console.log('Max reconnection attempts reached');
      return;
    }

    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts.current), 30000);
    reconnectTimeoutRef.current = setTimeout(() => {
      reconnectAttempts.current++;
      connect();
    }, delay);
  }, [connect]);

  const disconnect = useCallback(() => {
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
      reconnectTimeoutRef.current = null;
    }
    
    if (wsRef.current) {
      wsRef.current.close();
      wsRef.current = null;
    }
    
    setConnectionStatus(false, 'Manual disconnect');
  }, [setConnectionStatus]);

  useEffect(() => {
    if (isRealTimeEnabled && autoRefresh) {
      connect();
    } else {
      disconnect();
    }

    return () => {
      disconnect();
    };
  }, [isRealTimeEnabled, autoRefresh, connect, disconnect]);

  return {
    connect,
    disconnect,
    isConnected: useAnalyticsStore((state) => state.isConnected),
    connectionError: useAnalyticsStore((state) => state.connectionError),
  };
};

// Export Data Hook
export const useExportData = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (config: ExportConfig) => analyticsApi.export.exportData(config),
    onSuccess: (blob) => {
      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${Date.now()}-analytics-export`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      toast.success({
        title: 'Export Complete',
        description: 'Your analytics data has been exported successfully.',
      });
    },
    onError: (error) => {
      console.error('Export failed:', error);
      toast.error({
        title: 'Export Failed',
        description: 'Failed to export analytics data. Please try again.',
      });
    },
  });
};

// Filter Preset Management Hooks
export const useSaveFilterPreset = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ name, filters }: { name: string; filters: FilterConfig[] }) =>
      analyticsApi.filter.saveFilterPreset(name, filters),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.filters.presets });
      toast.success({
        title: 'Filter Preset Saved',
        description: 'Your filter preset has been saved successfully.',
      });
    },
  });
};

export const useDeleteFilterPreset = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (presetId: string) => analyticsApi.filter.deleteFilterPreset(presetId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.filters.presets });
      toast.success({
        title: 'Filter Preset Deleted',
        description: 'The filter preset has been deleted successfully.',
      });
    },
  });
};

// Dashboard Layout Management Hooks
export const useSaveDashboardLayout = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (layout: DashboardLayout) => analyticsApi.dashboard.saveLayout(layout),
    onSuccess: (response) => {
      const savedLayout = response.data;
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard.layouts });
      if (savedLayout) {
        queryClient.setQueryData(queryKeys.dashboard.layout(savedLayout.id), savedLayout);
      }
      toast.success({
        title: 'Layout Saved',
        description: 'Dashboard layout has been saved successfully.',
      });
    },
  });
};

export const useUpdateDashboardLayout = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ layoutId, updates }: { layoutId: string; updates: Partial<DashboardLayout> }) =>
      analyticsApi.dashboard.updateLayout(layoutId, updates),
    onSuccess: (response, { layoutId }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard.layouts });
      queryClient.setQueryData(queryKeys.dashboard.layout(layoutId), response.data);
      toast.success({
        title: 'Layout Updated',
        description: 'Dashboard layout has been updated successfully.',
      });
    },
  });
};

// Geographic Data Hooks
export const useMapData = (bounds?: [[number, number], [number, number]]) => {
  return useQuery({
    queryKey: [...queryKeys.geographic.mapData, bounds],
    queryFn: () => analyticsApi.geographic.getMapData(bounds),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useHeatMapData = (module: string, filters?: FilterConfig[]) => {
  return useQuery({
    queryKey: [...queryKeys.geographic.heatMapData(module), filters],
    queryFn: () => analyticsApi.geographic.getHeatMapData(module, filters),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useRouteVisualization = (routeIds: string[]) => {
  return useQuery({
    queryKey: queryKeys.geographic.routes(routeIds),
    queryFn: () => analyticsApi.geographic.getRouteVisualization(routeIds),
    enabled: routeIds.length > 0,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

// Performance and Health Hooks
export const usePerformanceMetrics = () => {
  return useQuery({
    queryKey: queryKeys.performance.metrics,
    queryFn: () => analyticsApi.performance.getMetrics(),
    staleTime: 30 * 1000, // 30 seconds
  });
};

export const usePerformanceRecommendations = () => {
  return useQuery({
    queryKey: queryKeys.performance.recommendations,
    queryFn: () => analyticsApi.performance.getRecommendations(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

export const useSystemHealth = () => {
  return useQuery({
    queryKey: queryKeys.health.systemHealth,
    queryFn: () => analyticsApi.health.getSystemHealth(),
    staleTime: 30 * 1000, // 30 seconds
    refetchInterval: 30 * 1000, // 30 seconds
  });
};

export const useHealthAlerts = () => {
  return useQuery({
    queryKey: queryKeys.health.alerts,
    queryFn: () => analyticsApi.health.getAlerts(),
    staleTime: 10 * 1000, // 10 seconds
    refetchInterval: 10 * 1000, // 10 seconds
  });
};

export const useAcknowledgeAlert = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (alertId: string) => analyticsApi.health.acknowledgeAlert(alertId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.health.alerts });
      toast.success({
        title: 'Alert Acknowledged',
        description: 'The alert has been acknowledged successfully.',
      });
    },
  });
};

// Combined hook for all real-time settings
export const useRealTimeSettings = () =>
  useAnalyticsStore((state) => ({
    isRealTimeEnabled: state.isRealTimeEnabled,
    autoRefresh: state.autoRefresh,
    refreshInterval: state.refreshInterval,
  }));
