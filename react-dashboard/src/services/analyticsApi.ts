/**
 * Analytics API Service
 * Comprehensive API integration for real-time analytics platform
 */

import type {
  AnalyticsApiResponse,
  ExecutiveKPIs,
  OperationalMetrics,
  FinancialMetrics,
  CustomerIntelligence,
  RealTimeMetrics,
  FilterConfig,
  ExportConfig,
  DashboardLayout,
  WidgetConfig,
  StreamingMetrics
} from '../types/analytics';
import api from './api';

// Base API endpoints for analytics
const ANALYTICS_BASE = '/v10/analytics';

// Executive Dashboard API
export const executiveApi = {
  getKPIs: async (): Promise<AnalyticsApiResponse<ExecutiveKPIs>> => {
    const response = await api.get(`${ANALYTICS_BASE}/executive/kpis`);
    return response.data;
  },

  getRealTimeMetrics: async (): Promise<AnalyticsApiResponse<RealTimeMetrics>> => {
    const response = await api.get(`${ANALYTICS_BASE}/executive/realtime`);
    return response.data;
  },

  getKPIsWithFilters: async (filters: FilterConfig[]): Promise<AnalyticsApiResponse<ExecutiveKPIs>> => {
    const params = filters.reduce((acc, filter) => {
      acc[filter.id] = filter.value;
      return acc;
    }, {} as Record<string, unknown>);
    
    const response = await api.get(`${ANALYTICS_BASE}/executive/kpis`, { params });
    return response.data;
  },
};

// Operational Reporting API
export const operationalApi = {
  getMetrics: async (): Promise<AnalyticsApiResponse<OperationalMetrics>> => {
    const response = await api.get(`${ANALYTICS_BASE}/operational/metrics`);
    return response.data;
  },

  getOriginDestinationAnalytics: async (filters?: FilterConfig[]): Promise<AnalyticsApiResponse<unknown>> => {
    const params = filters?.reduce((acc, filter) => {
      acc[filter.id] = filter.value;
      return acc;
    }, {} as Record<string, unknown>) || {};
    
    const response = await api.get(`${ANALYTICS_BASE}/operational/origin-destination`, { params });
    return response.data;
  },

  getRouteEfficiency: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/operational/route-efficiency`);
    return response.data;
  },

  getOnTimeDelivery: async (filters?: FilterConfig[]): Promise<AnalyticsApiResponse<unknown>> => {
    const params = filters?.reduce((acc, filter) => {
      acc[filter.id] = filter.value;
      return acc;
    }, {} as Record<string, unknown>) || {};
    
    const response = await api.get(`${ANALYTICS_BASE}/operational/on-time-delivery`, { params });
    return response.data;
  },

  getExceptionAnalysis: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/operational/exception-analysis`);
    return response.data;
  },

  getDriverPerformance: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/operational/driver-performance`);
    return response.data;
  },

  getContainerUtilization: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/operational/container-utilization`);
    return response.data;
  },

  getTransitTimeAnalysis: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/operational/transit-time-analysis`);
    return response.data;
  },
};

// Financial Reporting API
export const financialApi = {
  getMetrics: async (): Promise<AnalyticsApiResponse<FinancialMetrics>> => {
    const response = await api.get(`${ANALYTICS_BASE}/financial/metrics`);
    return response.data;
  },

  getRevenueRecognition: async (filters?: FilterConfig[]): Promise<AnalyticsApiResponse<unknown>> => {
    const params = filters?.reduce((acc, filter) => {
      acc[filter.id] = filter.value;
      return acc;
    }, {} as Record<string, unknown>) || {};
    
    const response = await api.get(`${ANALYTICS_BASE}/financial/revenue-recognition`, { params });
    return response.data;
  },

  getCOGSAnalysis: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/financial/cogs-analysis`);
    return response.data;
  },

  getGrossMarginAnalysis: async (filters?: FilterConfig[]): Promise<AnalyticsApiResponse<unknown>> => {
    const params = filters?.reduce((acc, filter) => {
      acc[filter.id] = filter.value;
      return acc;
    }, {} as Record<string, unknown>) || {};
    
    const response = await api.get(`${ANALYTICS_BASE}/financial/gross-margin`, { params });
    return response.data;
  },

  getCODCollection: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/financial/cod-collection`);
    return response.data;
  },

  getPaymentProcessing: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/financial/payment-processing`);
    return response.data;
  },

  getProfitabilityAnalysis: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/financial/profitability-analysis`);
    return response.data;
  },
};

// Customer Intelligence API
export const customerApi = {
  getIntelligence: async (): Promise<AnalyticsApiResponse<CustomerIntelligence>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/intelligence`);
    return response.data;
  },

  getActivityMonitoring: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/activity-monitoring`);
    return response.data;
  },

  getDormantAccounts: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/dormant-accounts`);
    return response.data;
  },

  getValueAnalysis: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/value-analysis`);
    return response.data;
  },

  getSentimentAnalysis: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/sentiment-analysis`);
    return response.data;
  },

  getSatisfactionMetrics: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/satisfaction-metrics`);
    return response.data;
  },

  getCLVCalculations: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/clv-calculations`);
    return response.data;
  },

  getChurnPrediction: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/churn-prediction`);
    return response.data;
  },

  getCustomerSegmentation: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/segmentation`);
    return response.data;
  },

  getAutomatedAlerts: async (): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/customer/automated-alerts`);
    return response.data;
  },
};

// Real-time streaming API
export const streamingApi = {
  getStreamingMetrics: async (): Promise<AnalyticsApiResponse<StreamingMetrics>> => {
    const response = await api.get(`${ANALYTICS_BASE}/streaming/metrics`);
    return response.data;
  },

  getStreamStatus: async (): Promise<AnalyticsApiResponse<{ connected: boolean; lastUpdate: string }>> => {
    const response = await api.get(`${ANALYTICS_BASE}/streaming/status`);
    return response.data;
  },

  // WebSocket connection details (for client-side WebSocket setup)
  getWebSocketConfig: async (): Promise<AnalyticsApiResponse<{ url: string; protocols: string[] }>> => {
    const response = await api.get(`${ANALYTICS_BASE}/streaming/websocket-config`);
    return response.data;
  },

  // Server-Sent Events configuration
  getSSEConfig: async (): Promise<AnalyticsApiResponse<{ url: string; eventTypes: string[] }>> => {
    const response = await api.get(`${ANALYTICS_BASE}/streaming/sse-config`);
    return response.data;
  },
};

// Dashboard and layout management API
export const dashboardApi = {
  getLayouts: async (): Promise<AnalyticsApiResponse<DashboardLayout[]>> => {
    const response = await api.get(`${ANALYTICS_BASE}/dashboard/layouts`);
    return response.data;
  },

  getLayout: async (layoutId: string): Promise<AnalyticsApiResponse<DashboardLayout>> => {
    const response = await api.get(`${ANALYTICS_BASE}/dashboard/layouts/${layoutId}`);
    return response.data;
  },

  saveLayout: async (layout: DashboardLayout): Promise<AnalyticsApiResponse<DashboardLayout>> => {
    const response = await api.post(`${ANALYTICS_BASE}/dashboard/layouts`, layout);
    return response.data;
  },

  updateLayout: async (layoutId: string, updates: Partial<DashboardLayout>): Promise<AnalyticsApiResponse<DashboardLayout>> => {
    const response = await api.put(`${ANALYTICS_BASE}/dashboard/layouts/${layoutId}`, updates);
    return response.data;
  },

  deleteLayout: async (layoutId: string): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.delete(`${ANALYTICS_BASE}/dashboard/layouts/${layoutId}`);
    return response.data;
  },

  getWidgetTemplates: async (): Promise<AnalyticsApiResponse<WidgetConfig[]>> => {
    const response = await api.get(`${ANALYTICS_BASE}/dashboard/widget-templates`);
    return response.data;
  },
};

// Filtering and export API
export const filterApi = {
  getFilterOptions: async (module: string): Promise<AnalyticsApiResponse<Record<string, unknown>>> => {
    const response = await api.get(`${ANALYTICS_BASE}/filters/options/${module}`);
    return response.data;
  },

  saveFilterPreset: async (name: string, filters: FilterConfig[]): Promise<AnalyticsApiResponse<{ id: string; name: string; filters: FilterConfig[] }>> => {
    const response = await api.post(`${ANALYTICS_BASE}/filters/presets`, { name, filters });
    return response.data;
  },

  getFilterPresets: async (): Promise<AnalyticsApiResponse<Array<{ id: string; name: string; filters: FilterConfig[] }>>> => {
    const response = await api.get(`${ANALYTICS_BASE}/filters/presets`);
    return response.data;
  },

  deleteFilterPreset: async (presetId: string): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.delete(`${ANALYTICS_BASE}/filters/presets/${presetId}`);
    return response.data;
  },
};

export const exportApi = {
  exportData: async (config: ExportConfig): Promise<Blob> => {
    const response = await api.post(`${ANALYTICS_BASE}/export`, config, {
      responseType: 'blob',
    });
    return response.data;
  },

  getExportStatus: async (exportId: string): Promise<AnalyticsApiResponse<{ status: string; progress: number; downloadUrl?: string }>> => {
    const response = await api.get(`${ANALYTICS_BASE}/export/status/${exportId}`);
    return response.data;
  },

  scheduleExport: async (config: ExportConfig & { schedule: string }): Promise<AnalyticsApiResponse<{ exportId: string; scheduled: boolean }>> => {
    const response = await api.post(`${ANALYTICS_BASE}/export/schedule`, config);
    return response.data;
  },
};

// Geographic and mapping API
export const geographicApi = {
  getMapData: async (bounds?: [[number, number], [number, number]]): Promise<AnalyticsApiResponse<unknown>> => {
    const params = bounds ? { bounds: JSON.stringify(bounds) } : {};
    const response = await api.get(`${ANALYTICS_BASE}/geographic/map-data`, { params });
    return response.data;
  },

  getHeatMapData: async (module: string, filters?: FilterConfig[]): Promise<AnalyticsApiResponse<unknown>> => {
    const params = filters?.reduce((acc, filter) => {
      acc[filter.id] = filter.value;
      return acc;
    }, {} as Record<string, unknown>) || {};
    
    params.module = module;
    const response = await api.get(`${ANALYTICS_BASE}/geographic/heatmap`, { params });
    return response.data;
  },

  getRouteVisualization: async (routeIds: string[]): Promise<AnalyticsApiResponse<unknown>> => {
    const response = await api.get(`${ANALYTICS_BASE}/geographic/routes`, {
      params: { routeIds: routeIds.join(',') },
    });
    return response.data;
  },
};

// Performance and optimization API
export const performanceApi = {
  getMetrics: async (): Promise<AnalyticsApiResponse<{ loadTime: number; renderTime: number; dataSize: number; cacheHitRate: number; errorRate: number }>> => {
    const response = await api.get(`${ANALYTICS_BASE}/performance/metrics`);
    return response.data;
  },

  optimizeCache: async (): Promise<AnalyticsApiResponse<{ optimized: boolean; freedSpace: number }>> => {
    const response = await api.post(`${ANALYTICS_BASE}/performance/optimize-cache`);
    return response.data;
  },

  getRecommendations: async (): Promise<AnalyticsApiResponse<Array<{ type: string; message: string; impact: string; priority: string }>>> => {
    const response = await api.get(`${ANALYTICS_BASE}/performance/recommendations`);
    return response.data;
  },
};

// Health and monitoring API
export const healthApi = {
  getSystemHealth: async (): Promise<AnalyticsApiResponse<{ status: string; checks: Array<{ name: string; status: string; responseTime: number; message?: string }> }>> => {
    const response = await api.get(`${ANALYTICS_BASE}/health`);
    return response.data;
  },

  getAlerts: async (): Promise<AnalyticsApiResponse<Array<{ id: string; type: string; message: string; severity: string; timestamp: string; resolved: boolean }>>> => {
    const response = await api.get(`${ANALYTICS_BASE}/health/alerts`);
    return response.data;
  },

  acknowledgeAlert: async (alertId: string): Promise<AnalyticsApiResponse<{ acknowledged: boolean }>> => {
    const response = await api.post(`${ANALYTICS_BASE}/health/alerts/${alertId}/acknowledge`);
    return response.data;
  },
};

export default {
  executive: executiveApi,
  operational: operationalApi,
  financial: financialApi,
  customer: customerApi,
  streaming: streamingApi,
  dashboard: dashboardApi,
  filter: filterApi,
  export: exportApi,
  geographic: geographicApi,
  performance: performanceApi,
  health: healthApi,
};