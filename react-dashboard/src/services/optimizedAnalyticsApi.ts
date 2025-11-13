/**
 * Optimized Analytics API Service
 * Enhanced API integration for high-performance analytics with real-time capabilities
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
  PerformanceMetrics
} from '../types/analytics';
import api from './api';

// Base API endpoints for optimized analytics
const OPTIMIZED_ANALYTICS_BASE = '/v10/analytics/optimized';
const REAL_TIME_BASE = '/v10/realtime';
const PERFORMANCE_BASE = '/v10/performance';

class OptimizedAnalyticsApiService {
  // Enhanced Branch Analytics
  async getBranchPerformanceAnalytics(branchId?: string, days = 30): Promise<AnalyticsApiResponse<any>> {
    const params = { 
      branch_id: branchId,
      days,
      include_trends: true,
      include_forecasts: true,
      include_recommendations: true
    };
    
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/branch/performance`, { params });
    return response.data;
  }

  // Batch analytics for multiple branches
  async getBatchBranchAnalytics(branchIds: string[], days = 30): Promise<AnalyticsApiResponse<any>> {
    const response = await api.post(`${OPTIMIZED_ANALYTICS_BASE}/branch/batch`, {
      branch_ids: branchIds,
      days,
      optimized: true
    });
    return response.data;
  }

  // Real-time branch analytics
  async getRealTimeAnalytics(branchId: string): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${REAL_TIME_BASE}/branch/${branchId}/analytics`);
    return response.data;
  }

  // Enhanced Capacity Analysis
  async getCapacityAnalysis(branchId: string, days = 30): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/capacity/${branchId}`, {
      params: { days, include_forecasts: true, include_optimization: true }
    });
    return response.data;
  }

  // Intelligent resource allocation
  async getIntelligentResourceAllocation(branchId: string): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/capacity/${branchId}/allocation`);
    return response.data;
  }

  // Predictive capacity planning
  async getPredictiveCapacityPlanning(branchId: string, forecastDays = 90): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/capacity/${branchId}/forecast`, {
      params: { forecast_days: forecastDays }
    });
    return response.data;
  }

  // Dynamic thresholds
  async getDynamicThresholds(branchId: string): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/capacity/${branchId}/thresholds`);
    return response.data;
  }

  // Performance monitoring
  async getPerformanceAnalytics(hours = 24): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${PERFORMANCE_BASE}/analytics`, {
      params: { hours }
    });
    return response.data;
  }

  // Real-time performance monitoring
  async getRealTimePerformance(): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${PERFORMANCE_BASE}/realtime`);
    return response.data;
  }

  // Performance optimization recommendations
  async getPerformanceRecommendations(): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${PERFORMANCE_BASE}/recommendations`);
    return response.data;
  }

  // Real-time metrics streaming
  async getRealTimeMetrics(): Promise<AnalyticsApiResponse<RealTimeMetrics>> {
    const response = await api.get(`${REAL_TIME_BASE}/metrics`);
    return response.data;
  }

  // Capacity utilization metrics
  async getCapacityUtilizationMetrics(branchId?: string): Promise<AnalyticsApiResponse<any>> {
    const params = branchId ? { branch_id: branchId } : {};
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/capacity/utilization`, { params });
    return response.data;
  }

  // Precompute analytics data
  async precomputeAnalytics(branchIds: string[], days = 30): Promise<AnalyticsApiResponse<{ job_id: string }>> {
    const response = await api.post(`${OPTIMIZED_ANALYTICS_BASE}/precompute`, {
      branch_ids: branchIds,
      days,
      priority: 'high'
    });
    return response.data;
  }

  // Clear cache
  async clearAnalyticsCache(branchId?: string): Promise<AnalyticsApiResponse<{ cleared: boolean }>> {
    const params = branchId ? { branch_id: branchId } : {};
    const response = await api.delete(`${OPTIMIZED_ANALYTICS_BASE}/cache`, { params });
    return response.data;
  }

  // Materialized snapshots
  async getMaterializedSnapshot(branchId: string, date: string): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/snapshot/${branchId}/${date}`);
    return response.data;
  }

  // WebSocket configuration for real-time updates
  async getWebSocketConfig(): Promise<AnalyticsApiResponse<{ url: string; protocols: string[] }>> {
    const response = await api.get(`${REAL_TIME_BASE}/websocket-config`);
    return response.data;
  }

  // Server-Sent Events configuration
  async getSSEConfig(): Promise<AnalyticsApiResponse<{ url: string; eventTypes: string[] }>> {
    const response = await api.get(`${REAL_TIME_BASE}/sse-config`);
    return response.data;
  }

  // Enhanced export functionality
  async exportAnalytics(config: ExportConfig): Promise<Blob> {
    const response = await api.post(`${OPTIMIZED_ANALYTICS_BASE}/export`, config, {
      responseType: 'blob',
    });
    return response.data;
  }

  // Schedule export
  async scheduleExport(config: ExportConfig & { schedule: string }): Promise<AnalyticsApiResponse<{ exportId: string; scheduled: boolean }>> {
    const response = await api.post(`${OPTIMIZED_ANALYTICS_BASE}/export/schedule`, config);
    return response.data;
  }

  // Get available branches
  async getAvailableBranches(): Promise<AnalyticsApiResponse<Array<{ id: string; name: string; code: string }>>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/branches`);
    return response.data;
  }

  // Performance alerts
  async getPerformanceAlerts(): Promise<AnalyticsApiResponse<any[]>> {
    const response = await api.get(`${PERFORMANCE_BASE}/alerts`);
    return response.data;
  }

  // Acknowledge alert
  async acknowledgeAlert(alertId: string): Promise<AnalyticsApiResponse<{ acknowledged: boolean }>> {
    const response = await api.post(`${PERFORMANCE_BASE}/alerts/${alertId}/acknowledge`);
    return response.data;
  }

  // System health check
  async getSystemHealth(): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${PERFORMANCE_BASE}/health`);
    return response.data;
  }

  // Cache statistics
  async getCacheStatistics(): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${PERFORMANCE_BASE}/cache-stats`);
    return response.data;
  }

  // Query optimization suggestions
  async getQueryOptimizationSuggestions(): Promise<AnalyticsApiResponse<any[]>> {
    const response = await api.get(`${PERFORMANCE_BASE}/query-optimization`);
    return response.data;
  }

  // Database performance metrics
  async getDatabasePerformanceMetrics(): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${PERFORMANCE_BASE}/database-metrics`);
    return response.data;
  }

  // Memory usage analytics
  async getMemoryUsageAnalytics(): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${PERFORMANCE_BASE}/memory-usage`);
    return response.data;
  }

  // Background job status
  async getBackgroundJobStatus(): Promise<AnalyticsApiResponse<any[]>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/jobs/status`);
    return response.data;
  }

  // Background job history
  async getJobHistory(jobType?: string): Promise<AnalyticsApiResponse<any[]>> {
    const params = jobType ? { job_type: jobType } : {};
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/jobs/history`, { params });
    return response.data;
  }

  // Dashboard layout optimization
  async optimizeDashboardLayout(layoutId: string): Promise<AnalyticsApiResponse<any>> {
    const response = await api.post(`${OPTIMIZED_ANALYTICS_BASE}/dashboard/optimize/${layoutId}`);
    return response.data;
  }

  // Predictive alerts
  async getPredictiveAlerts(branchId: string): Promise<AnalyticsApiResponse<any[]>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/alerts/predictive/${branchId}`);
    return response.data;
  }

  // Performance benchmarking
  async getPerformanceBenchmark(): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${PERFORMANCE_BASE}/benchmark`);
    return response.data;
  }

  // Capacity forecasting
  async getCapacityForecasting(branchId: string, horizon = 30): Promise<AnalyticsApiResponse<any>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/capacity/forecasting/${branchId}`, {
      params: { horizon }
    });
    return response.data;
  }

  // Resource optimization suggestions
  async getResourceOptimizationSuggestions(branchId: string): Promise<AnalyticsApiResponse<any[]>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/capacity/optimization/${branchId}`);
    return response.data;
  }

  // Analytics audit trail
  async getAnalyticsAuditTrail(branchId: string, startDate: string, endDate: string): Promise<AnalyticsApiResponse<any[]>> {
    const response = await api.get(`${OPTIMIZED_ANALYTICS_BASE}/audit/${branchId}`, {
      params: { start_date: startDate, end_date: endDate }
    });
    return response.data;
  }
}

export const optimizedAnalyticsApi = new OptimizedAnalyticsApiService();
export default optimizedAnalyticsApi;

// Additional utility functions for frontend optimization
export const analyticsApiUtils = {
  // Cache key generators
  generateCacheKey: (type: string, branchId?: string, days?: number): string => {
    const base = `analytics:${type}`;
    const parts = [base];
    if (branchId) parts.push(branchId);
    if (days) parts.push(String(days));
    return parts.join(':');
  },

  // Performance monitoring
  measurePerformance: async <T>(operation: () => Promise<T>, operationName: string): Promise<T> => {
    const startTime = performance.now();
      const startMemory = 'memory' in performance ? (performance as any).memory?.usedJSHeapSize ?? 0 : 0;
    
    try {
      const result = await operation();
      const endTime = performance.now();
      const endMemory = 'memory' in performance ? (performance as any).memory?.usedJSHeapSize ?? 0 : 0;
      
      // Log performance metrics
      console.log(`Analytics Performance - ${operationName}:`, {
        executionTime: `${(endTime - startTime).toFixed(2)}ms`,
        memoryUsage: `${((endMemory - startMemory) / 1024 / 1024).toFixed(2)}MB`,
        operation: operationName,
        timestamp: new Date().toISOString(),
      });
      
      return result;
    } catch (error) {
      console.error(`Analytics Error - ${operationName}:`, error);
      throw error;
    }
  },

  // Data transformation utilities
  transformAnalyticsData: (data: any) => {
    // Transform backend data to frontend format
    return {
      trends: data.trends || [],
      capacity: data.capacity_metrics || {},
      performance: data.performance_metrics || {},
      recommendations: data.recommendations || [],
    };
  },

  // Error handling utilities
  handleApiError: (error: any) => {
    if (error.response?.status === 429) {
      return 'Rate limit exceeded. Please try again later.';
    } else if (error.response?.status >= 500) {
      return 'Server error. Please try again later.';
    } else if (error.code === 'NETWORK_ERROR') {
      return 'Network error. Please check your connection.';
    }
    return error.message || 'An unexpected error occurred.';
  },

  // Retry utilities with exponential backoff
  retryWithBackoff: async <T>(
    operation: () => Promise<T>,
    maxRetries = 3,
    baseDelay = 1000
  ): Promise<T> => {
    let lastError: any;
    
    for (let attempt = 0; attempt <= maxRetries; attempt++) {
      try {
        return await operation();
      } catch (error) {
        lastError = error;
        
        if (attempt === maxRetries) {
          break;
        }
        
        const delay = baseDelay * Math.pow(2, attempt);
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }
    
    throw lastError;
  },

  // Batch request utilities
  batchRequests: async <T>(requests: Array<() => Promise<T>>): Promise<T[]> => {
    const results = await Promise.allSettled(requests.map(req => req()));
    
    return results.map((result, index) => {
      if (result.status === 'fulfilled') {
        return result.value;
      } else {
        console.error(`Batch request ${index} failed:`, result.reason);
        return null;
      }
    }).filter(Boolean) as T[];
  },
};

type BranchReference = { id: string } | string;

const resolveBranchId = (branch: BranchReference): string =>
  typeof branch === 'string' ? branch : branch.id;

export class OptimizedBranchAnalyticsService extends OptimizedAnalyticsApiService {
  async getBranchPerformanceAnalytics(branch: BranchReference, days = 30): Promise<any> {
    const response = await super.getBranchPerformanceAnalytics(resolveBranchId(branch), days);
    return response?.data ?? response;
  }

  async getBatchBranchAnalytics(branchIds: string[], days = 30): Promise<any> {
    const response = await super.getBatchBranchAnalytics(branchIds, days);
    return response?.data ?? response;
  }

  async getRealTimeAnalytics(branch: BranchReference): Promise<any> {
    const response = await super.getRealTimeAnalytics(resolveBranchId(branch));
    return response?.data ?? response;
  }

  async listAvailableBranches(): Promise<Array<{ id: string; name?: string; code?: string }>> {
    const response = await super.getAvailableBranches();
    return response?.data ?? [];
  }
}

export class OptimizedBranchCapacityService extends OptimizedAnalyticsApiService {
  async getCapacityAnalysis(branch: BranchReference, days = 30): Promise<any> {
    const response = await super.getCapacityAnalysis(resolveBranchId(branch), days);
    return response?.data ?? response;
  }

  async listAvailableBranches(): Promise<Array<{ id: string; name?: string; code?: string }>> {
    const response = await super.getAvailableBranches();
    return response?.data ?? [];
  }
}