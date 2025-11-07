/**
 * Analytics Platform Store
 * Zustand-based state management for real-time analytics
 */

import { create } from 'zustand';
import { devtools, subscribeWithSelector } from 'zustand/middleware';
import type { 
  AnalyticsState, 
  FilterConfig, 
  RealTimeMetrics, 
  DashboardLayout,
  WidgetConfig,
  AnalyticsError,
  AnalyticsLoading,
  StreamingMetrics
} from '../types/analytics';

interface AnalyticsStore extends AnalyticsState {
  // Real-time metrics
  realTimeMetrics: RealTimeMetrics | null;
  streamingMetrics: StreamingMetrics | null;
  
  // Loading and error states
  loading: AnalyticsLoading;
  error: AnalyticsError | null;
  
  // Layout management
  availableLayouts: DashboardLayout[];
  isLayoutEditing: boolean;
  
  // Real-time connection
  isConnected: boolean;
  connectionError: string | null;
  
  // Actions
  setCurrentDashboard: (dashboard: AnalyticsState['currentDashboard']) => void;
  setSelectedFilters: (filters: FilterConfig[]) => void;
  addFilter: (filter: FilterConfig) => void;
  removeFilter: (filterId: string) => void;
  updateFilter: (filterId: string, updates: Partial<FilterConfig>) => void;
  
  setTimeRange: (range: { start: string; end: string; preset: string }) => void;
  setRealTimeEnabled: (enabled: boolean) => void;
  setAutoRefresh: (enabled: boolean) => void;
  setRefreshInterval: (interval: number) => void;
  
  updateRealTimeMetrics: (metrics: RealTimeMetrics) => void;
  updateStreamingMetrics: (metrics: StreamingMetrics) => void;
  
  setSelectedWidgets: (widgetIds: string[]) => void;
  setCurrentLayout: (layout: DashboardLayout) => void;
  addLayout: (layout: DashboardLayout) => void;
  updateLayout: (layoutId: string, updates: Partial<DashboardLayout>) => void;
  deleteLayout: (layoutId: string) => void;
  
  setIsLayoutEditing: (editing: boolean) => void;
  
  setLoading: (loading: Partial<AnalyticsLoading>) => void;
  setError: (error: AnalyticsError | null) => void;
  clearError: () => void;
  
  setConnectionStatus: (connected: boolean, error?: string | null) => void;
  reset: () => void;
}

const defaultLoading: AnalyticsLoading = {
  dashboard: false,
  data: false,
  filters: false,
  export: false,
};

const defaultRealTimeMetrics: RealTimeMetrics = {
  timestamp: new Date().toISOString(),
  activeOperations: 0,
  pendingShipments: 0,
  completedDeliveries: 0,
  averageDeliveryTime: 0,
  customerSatisfactionScore: 0,
  systemHealth: 'healthy',
};

const defaultStreamingMetrics: StreamingMetrics = {
  connections: 0,
  updatesPerSecond: 0,
  dataPoints: [],
};

const defaultLayout: DashboardLayout = {
  id: 'default',
  name: 'Default Layout',
  widgets: [],
  isDefault: true,
  permissions: ['read'],
};

const initialState: AnalyticsState & {
  realTimeMetrics: RealTimeMetrics | null;
  streamingMetrics: StreamingMetrics | null;
  loading: AnalyticsLoading;
  error: AnalyticsError | null;
  availableLayouts: DashboardLayout[];
  isLayoutEditing: boolean;
  isConnected: boolean;
  connectionError: string | null;
} = {
  currentDashboard: 'executive',
  selectedFilters: [],
  timeRange: {
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString(),
    end: new Date().toISOString(),
    preset: '30d',
  },
  isRealTimeEnabled: true,
  autoRefresh: true,
  refreshInterval: 30000, // 30 seconds
  selectedWidgets: [],
  layout: defaultLayout,
  realTimeMetrics: null,
  streamingMetrics: null,
  loading: defaultLoading,
  error: null,
  availableLayouts: [defaultLayout],
  isLayoutEditing: false,
  isConnected: false,
  connectionError: null,
};

export const useAnalyticsStore = create<AnalyticsStore>()(
  devtools(
    subscribeWithSelector(
      (set, get) => ({
        ...initialState,
        
        // Dashboard management
        setCurrentDashboard: (dashboard) =>
          set({ currentDashboard: dashboard }, false, 'setCurrentDashboard'),
        
        // Filter management
        setSelectedFilters: (filters) =>
          set({ selectedFilters: filters }, false, 'setSelectedFilters'),
        
        addFilter: (filter) =>
          set(
            (state) => ({
              selectedFilters: [...state.selectedFilters, filter],
            }),
            false,
            'addFilter'
          ),
        
        removeFilter: (filterId) =>
          set(
            (state) => ({
              selectedFilters: state.selectedFilters.filter(
                (f) => f.id !== filterId
              ),
            }),
            false,
            'removeFilter'
          ),
        
        updateFilter: (filterId, updates) =>
          set(
            (state) => ({
              selectedFilters: state.selectedFilters.map((filter) =>
                filter.id === filterId ? { ...filter, ...updates } : filter
              ),
            }),
            false,
            'updateFilter'
          ),
        
        // Time range management
        setTimeRange: (range) =>
          set({ timeRange: range }, false, 'setTimeRange'),
        
        // Real-time settings
        setRealTimeEnabled: (enabled) =>
          set({ isRealTimeEnabled: enabled }, false, 'setRealTimeEnabled'),
        
        setAutoRefresh: (enabled) =>
          set({ autoRefresh: enabled }, false, 'setAutoRefresh'),
        
        setRefreshInterval: (interval) =>
          set({ refreshInterval: interval }, false, 'setRefreshInterval'),
        
        // Real-time metrics updates
        updateRealTimeMetrics: (metrics) =>
          set(
            { realTimeMetrics: metrics },
            false,
            'updateRealTimeMetrics'
          ),
        
        updateStreamingMetrics: (metrics) =>
          set(
            { streamingMetrics: metrics },
            false,
            'updateStreamingMetrics'
          ),
        
        // Widget selection
        setSelectedWidgets: (widgetIds) =>
          set(
            { selectedWidgets: widgetIds },
            false,
            'setSelectedWidgets'
          ),
        
        // Layout management
        setCurrentLayout: (layout) =>
          set({ layout }, false, 'setCurrentLayout'),
        
        addLayout: (layout) =>
          set(
            (state) => ({
              availableLayouts: [...state.availableLayouts, layout],
            }),
            false,
            'addLayout'
          ),
        
        updateLayout: (layoutId, updates) =>
          set(
            (state) => ({
              availableLayouts: state.availableLayouts.map((layout) =>
                layout.id === layoutId ? { ...layout, ...updates } : layout
              ),
            }),
            false,
            'updateLayout'
          ),
        
        deleteLayout: (layoutId) =>
          set(
            (state) => ({
              availableLayouts: state.availableLayouts.filter(
                (layout) => layout.id !== layoutId
              ),
            }),
            false,
            'deleteLayout'
          ),
        
        setIsLayoutEditing: (editing) =>
          set({ isLayoutEditing: editing }, false, 'setIsLayoutEditing'),
        
        // Loading and error management
        setLoading: (loading) =>
          set(
            (state) => ({
              loading: { ...state.loading, ...loading },
            }),
            false,
            'setLoading'
          ),
        
        setError: (error) =>
          set({ error }, false, 'setError'),
        
        clearError: () =>
          set({ error: null }, false, 'clearError'),
        
        // Connection management
        setConnectionStatus: (connected, error = null) =>
          set(
            { isConnected: connected, connectionError: error },
            false,
            'setConnectionStatus'
          ),
        
        // Reset to initial state
        reset: () =>
          set(initialState, false, 'reset'),
      })
    ),
    {
      name: 'analytics-store',
    }
  )
);

// Selector hooks for better performance
export const useCurrentDashboard = () =>
  useAnalyticsStore((state) => state.currentDashboard);

export const useSelectedFilters = () =>
  useAnalyticsStore((state) => state.selectedFilters);

export const useTimeRange = () =>
  useAnalyticsStore((state) => state.timeRange);

export const useRealTimeSettings = () =>
  useAnalyticsStore((state) => ({
    isRealTimeEnabled: state.isRealTimeEnabled,
    autoRefresh: state.autoRefresh,
    refreshInterval: state.refreshInterval,
  }));

export const useRealTimeMetrics = () =>
  useAnalyticsStore((state) => state.realTimeMetrics);

export const useStreamingMetrics = () =>
  useAnalyticsStore((state) => state.streamingMetrics);

export const useAnalyticsLoading = () =>
  useAnalyticsStore((state) => state.loading);

export const useAnalyticsError = () =>
  useAnalyticsStore((state) => state.error);

export const useCurrentLayout = () =>
  useAnalyticsStore((state) => state.layout);

export const useAvailableLayouts = () =>
  useAnalyticsStore((state) => state.availableLayouts);

export const useConnectionStatus = () =>
  useAnalyticsStore((state) => ({
    isConnected: state.isConnected,
    connectionError: state.connectionError,
  }));

// Performance monitoring subscription
useAnalyticsStore.subscribe(
  (state) => state.realTimeMetrics,
  (realTimeMetrics, previousRealTimeMetrics) => {
    if (realTimeMetrics && previousRealTimeMetrics) {
      const timestamp = new Date(realTimeMetrics.timestamp);
      const previousTimestamp = new Date(previousRealTimeMetrics.timestamp);
      const timeDiff = timestamp.getTime() - previousTimestamp.getTime();
      
      if (timeDiff > 0) {
        const updatesPerSecond = 1000 / timeDiff;
        useAnalyticsStore.getState().updateStreamingMetrics({
          connections: realTimeMetrics.activeOperations,
          updatesPerSecond,
          dataPoints: [
            {
              metric: 'system_health',
              value: realTimeMetrics.systemHealth === 'healthy' ? 1 : 0,
              timestamp: realTimeMetrics.timestamp,
            },
          ],
        });
      }
    }
  }
);