/**
 * Dashboard TypeScript Type Definitions
 * Monochrome theme - Steve Jobs minimalist design standards
 */

/**
 * Trend direction for KPI changes
 */
export type TrendDirection = 'up' | 'down' | 'stable';

/**
 * Visual state for KPI cards
 */
export type KPIState = 'success' | 'warning' | 'neutral' | 'critical' | 'loading' | 'empty';

/**
 * Trend information for metrics
 */
export interface Trend {
  /** Percentage or value change */
  value: number;
  /** Direction of the trend */
  direction: TrendDirection;
  /** Optional label for the trend period */
  label?: string;
}

/**
 * Individual KPI Card data
 */
export interface KPICard {
  /** Unique identifier for the KPI */
  id: string;
  /** Display title */
  title: string;
  /** Main metric value */
  value: number | string;
  /** Subtitle or time period */
  subtitle?: string;
  /** Icon class (e.g., 'fas fa-box') */
  icon?: string;
  /** Trend information */
  trend?: Trend;
  /** Visual state */
  state?: KPIState;
  /** Drill-down route for details */
  drilldownRoute?: string;
  /** Tooltip text */
  tooltip?: string;
  /** Loading state */
  loading?: boolean;
  /** KPI identifier for data attributes */
  kpi?: string;
}

/**
 * Chart data point
 */
export interface ChartDataPoint {
  /** X-axis label or date */
  label: string;
  /** Y-axis value */
  value: number;
  /** Optional category for multi-series charts */
  category?: string;
}

/**
 * Chart configuration
 */
export interface ChartConfig {
  /** Chart title */
  title: string;
  /** Chart type */
  type: 'line' | 'bar' | 'area' | 'pie' | 'polar';
  /** Chart data */
  data: ChartDataPoint[];
  /** Loading state */
  loading?: boolean;
  /** Height in pixels */
  height?: number;
}

/**
 * Workflow queue item status
 */
export type WorkflowStatus = 'pending' | 'in_progress' | 'completed' | 'delayed';

/**
 * Individual workflow item
 */
export interface WorkflowItem {
  /** Unique identifier */
  id: string;
  /** Item title */
  title: string;
  /** Description or details */
  description?: string;
  /** Current status */
  status: WorkflowStatus;
  /** Priority level (1-5, 5 being highest) */
  priority?: number | 'low' | 'medium' | 'high';
  /** Assigned user */
  assignedTo?: string;
  /** Due date */
  dueDate?: string;
  /** Action URL */
  actionUrl?: string;
}

/**
 * Financial statement data
 */
export interface StatementData {
  /** Income amount */
  income: number;
  /** Expense amount */
  expense: number;
  /** Net balance */
  balance: number;
  /** Currency symbol */
  currency?: string;
}

/**
 * Date filter options
 */
export interface DateFilter {
  /** Start date */
  from: string;
  /** End date */
  to: string;
  /** Preset period (today, week, month, custom) */
  preset?: 'today' | 'week' | 'month' | 'custom';
}

/**
 * Complete dashboard data structure
 */
export interface DashboardData {
  /** Date filter applied */
  dateFilter: DateFilter;
  
  /** Business health KPIs */
  healthKPIs: {
    slaStatus?: KPICard;
    exceptions?: KPICard;
    onTimeDelivery?: KPICard;
    openTickets?: KPICard;
  };
  
  /** Core metrics KPIs */
  coreKPIs: KPICard[];
  
  /** Workflow queue items */
  workflowQueue: WorkflowItem[];
  
  /** Financial statements */
  statements: {
    deliveryMan?: StatementData;
    merchant?: StatementData;
    hub?: StatementData;
  };
  
  /** Chart configurations */
  charts: {
    incomeExpense?: ChartConfig;
    courierRevenue?: ChartConfig;
    cashCollection?: ChartConfig;
  };
  
  /** Quick actions */
  quickActions: QuickAction[];
  
  /** Loading state */
  loading?: boolean;
  
  /** Error message */
  error?: string;
}

/**
 * Quick action button
 */
export interface QuickAction {
  /** Unique identifier */
  id: string;
  /** Action title */
  title: string;
  /** Icon class */
  icon: string;
  /** Action URL */
  url: string;
  /** Optional badge count */
  badge?: number;
  /** Permission required */
  permission?: string;
  /** Short description */
  description?: string;
  /** Keyboard shortcut hint */
  shortcut?: string;
}

/**
 * Dashboard filter parameters
 */
export interface DashboardFilters {
  /** Date range */
  dateRange?: DateFilter;
  /** Hub filter */
  hubId?: string;
  /** Status filter */
  status?: string[];
  /** Merchant filter */
  merchantId?: string;
}

/**
 * Loading states for dashboard sections
 */
export interface LoadingStates {
  kpis: boolean;
  charts: boolean;
  workflow: boolean;
  statements: boolean;
}
