import { useQuery } from '@tanstack/react-query';
import { dashboardApi } from '../services/api';
import type {
  DashboardActivityEntry,
  DashboardData,
  KPICard,
  TeamOverviewEntry,
  WorkflowItem,
  ChartConfig,
  StatementData,
  QuickAction,
} from '../types/dashboard';

/**
 * API Response Types
 */
interface DashboardApiResponse {
  success: boolean;
  data: {
    dateFilter: {
      from: string;
      to: string;
      preset: 'today' | 'week' | 'month' | 'custom';
    };
    healthKPIs: {
      slaStatus?: KPICard;
      exceptions?: KPICard;
      onTimeDelivery?: KPICard;
      openTickets?: KPICard;
    };
    coreKPIs: KPICard[];
    workflowQueue: WorkflowItem[];
    statements: {
      deliveryMan?: StatementData;
      merchant?: StatementData;
      hub?: StatementData;
    };
    charts: {
      [key: string]: ChartConfig | undefined;
      incomeExpense?: ChartConfig;
      courierRevenue?: ChartConfig;
      cashCollection?: ChartConfig;
    };
    quickActions: QuickAction[];
    teamOverview?: TeamOverviewEntry[];
    activityTimeline?: DashboardActivityEntry[];
  };
}

interface KPIsApiResponse {
  success: boolean;
  data: {
    healthKPIs: {
      slaStatus?: KPICard;
      exceptions?: KPICard;
      onTimeDelivery?: KPICard;
      openTickets?: KPICard;
    };
    coreKPIs: KPICard[];
  };
}

interface ChartsApiResponse {
  success: boolean;
  data: {
    incomeExpense?: ChartConfig;
    courierRevenue?: ChartConfig;
    cashCollection?: ChartConfig;
  };
}

interface WorkflowQueueApiResponse {
  success: boolean;
  data: WorkflowItem[];
}

/**
 * Custom hook to fetch complete dashboard data
 * Uses React Query for caching and automatic refetching
 */
export const useDashboardData = () => {
  return useQuery<DashboardApiResponse, Error>({
    queryKey: ['dashboard', 'data'],
    queryFn: async () => {
      const response = await dashboardApi.getData();
      return response;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 10 * 60 * 1000, // 10 minutes (previously cacheTime)
    refetchOnWindowFocus: true,
    retry: 2,
  });
};

/**
 * Custom hook to fetch dashboard KPIs only
 * Useful for partial updates
 */
export const useDashboardKPIs = () => {
  return useQuery<KPIsApiResponse, Error>({
    queryKey: ['dashboard', 'kpis'],
    queryFn: async () => {
      const response = await dashboardApi.getKPIs();
      return response;
    },
    staleTime: 2 * 60 * 1000, // 2 minutes
    gcTime: 5 * 60 * 1000,
    refetchOnWindowFocus: true,
    retry: 2,
  });
};

/**
 * Custom hook to fetch dashboard charts data
 * Separate query for better performance
 */
export const useDashboardCharts = () => {
  return useQuery<ChartsApiResponse, Error>({
    queryKey: ['dashboard', 'charts'],
    queryFn: async () => {
      const response = await dashboardApi.getCharts();
      return response;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 10 * 60 * 1000,
    refetchOnWindowFocus: false,
    retry: 2,
  });
};

/**
 * Custom hook to fetch workflow queue items
 * Higher refresh rate for time-sensitive data
 */
export const useDashboardWorkflowQueue = () => {
  return useQuery<WorkflowQueueApiResponse, Error>({
    queryKey: ['dashboard', 'workflow-queue'],
    queryFn: async () => {
      const response = await dashboardApi.getWorkflowQueue();
      return response;
    },
    staleTime: 1 * 60 * 1000, // 1 minute
    gcTime: 5 * 60 * 1000,
    refetchOnWindowFocus: true,
    refetchInterval: 2 * 60 * 1000, // Auto-refetch every 2 minutes
    retry: 2,
  });
};

/**
 * Transform API response to DashboardData format
 * Handles backward compatibility with mock data
 */
export const transformDashboardData = (
  apiResponse?: DashboardApiResponse
): DashboardData | null => {
  if (!apiResponse?.data) return null;

  const data = apiResponse.data;
  
  return {
    dateFilter: data.dateFilter,
    healthKPIs: data.healthKPIs,
    coreKPIs: data.coreKPIs,
    workflowQueue: data.workflowQueue,
    statements: data.statements,
    charts: data.charts,
    quickActions: data.quickActions,
    teamOverview: data.teamOverview ?? [],
    activityTimeline: data.activityTimeline ?? [],
    loading: false,
  };
};
