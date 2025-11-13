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
export type WorkflowStatus =
  | 'pending'
  | 'in_progress'
  | 'testing'
  | 'awaiting_feedback'
  | 'completed'
  | 'delayed';

/**
 * Individual workflow item
 */
export interface WorkflowDependency {
  id: string;
  title?: string;
  status?: 'blocked' | 'at_risk' | 'complete';
}

export interface WorkflowAttachment {
  id: string;
  name: string;
  url: string;
  preview_url?: string;
  mime_type?: string;
  size_bytes?: number;
}

export interface WorkflowTimeTracking {
  totalSeconds: number;
  running?: boolean;
  started_at?: string | null;
  updated_at?: string | null;
}

export interface WorkflowActivityEntry {
  id: string;
  message: string;
  created_at: string;
  user_name?: string;
  user_id?: number | string;
}

export interface DashboardActivityEntry {
  id: string;
  action: string;
  details: Record<string, unknown>;
  createdAt?: string | null;
  actor?: {
    id: string;
    name?: string | null;
    email?: string | null;
    avatar?: string | null;
  } | null;
  task?: {
    id: string;
    title?: string | null;
    status?: string | null;
  } | null;
}

export interface TeamOverviewEntry {
  id: string;
  label: string;
  department: {
    id: number;
    title: string;
  } | null;
  hub: {
    id: number;
    name: string;
  } | null;
  total: number;
  active: number;
  inactive: number;
  active_ratio: number;
  recent_hires: number;
  sample_users: Array<{
    id: number;
    name: string;
    status: number;
  }>;
}

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
  /** Assigned user display name */
  assignedTo?: string;
  /** Assigned user identifier (for permissions) */
  assignedUserId?: number | string | null;
  /** Related tracking number */
  trackingNumber?: string;
  /** Associated tags */
  tags?: string[];
  /** Due date */
  dueDate?: string;
  /** Action URL */
  actionUrl?: string;
  /** Project context */
  projectId?: number | string | null;
  /** Workspace / hub context */
  workspaceId?: number | string | null;
  /** Dependency graph */
  dependencies?: WorkflowDependency[];
  /** Attachment metadata */
  attachments?: WorkflowAttachment[];
  /** Time tracking snapshot */
  timeTracking?: WorkflowTimeTracking;
  /** Activity log entries */
  activityLog?: WorkflowActivityEntry[];
  /** Allowed transitions definition */
  allowedTransitions?: Partial<Record<WorkflowStatus | 'any', WorkflowStatus[]>>;
  /** Roles permitted to mutate this task */
  restrictedRoles?: string[];
  /** Arbitrary metadata */
  metadata?: Record<string, unknown>;
  /** Alternative snake_case tracking number */
  tracking_number?: string | null;
  /** Service level */
  serviceLevel?: string | null;
  /** Status label */
  statusLabel?: string | null;
  /** Status label (snake_case) */
  status_label?: string | null;
  /** Project name/context */
  project?: string | null;
  /** Client name */
  client?: string | null;
  /** Stage information */
  stage?: string | null;
  /** Origin branch */
  originBranch?: string | null;
  /** Origin branch (snake_case) */
  origin_branch?: string | null;
  /** Destination branch */
  destinationBranch?: string | null;
  /** Destination branch (snake_case) */
  destination_branch?: string | null;
  /** Promised delivery date */
  promisedAt?: string | null;
  /** Promised delivery date (snake_case) */
  promised_at?: string | null;
  /** Created at timestamp */
  createdAt?: string | null;
  /** Created at timestamp (snake_case) */
  created_at?: string | null;
  /** Due at timestamp */
  due_at?: string | null;
  /** Assigned user ID (snake_case) */
  assigned_user_id?: number | string | null;
  /** Assigned user name (snake_case) */
  assigned_user_name?: string | null;
  /** Assigned user avatar */
  assignedUserAvatar?: string | null;
  /** Assigned user avatar (snake_case) */
  assigned_user_avatar?: string | null;
  /** Assigned user initials */
  assigned_user_initials?: string | null;
  /** Attachments count */
  attachmentsCount?: number | null;
  /** Attachments count (snake_case) */
  attachments_count?: number | null;
  /** Time tracking (snake_case) */
  time_tracking?: {
    total_seconds?: number;
    running?: boolean;
    started_at?: string | null;
    updated_at?: string | null;
  } | null;
  /** Watchers */
  watchers?: Array<{
    id: number | string;
    name: string | null;
    avatar?: string | null;
  }> | null;
  /** Comments count */
  commentsCount?: number | null;
  /** Comments count (snake_case) */
  comments_count?: number | null;
  /** Activity count */
  activityCount?: number | null;
  /** Activity count (snake_case) */
  activity_count?: number | null;
  /** Allowed transitions (snake_case) */
  allowed_transitions?: Partial<Record<WorkflowStatus | 'any', WorkflowStatus[]>>;
  /** Restricted roles (snake_case) */
  restricted_roles?: string[];
  /** Project ID (snake_case) */
  project_id?: number | string | null;
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
    [key: string]: ChartConfig | undefined;
    incomeExpense?: ChartConfig;
    courierRevenue?: ChartConfig;
    cashCollection?: ChartConfig;
  };
  
  /** Quick actions */
  quickActions: QuickAction[];

  /** Team operations overview */
  teamOverview: TeamOverviewEntry[];

  /** Recent workflow activity timeline */
  activityTimeline: DashboardActivityEntry[];
  
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
  badge?: number | {
    count: number | string;
    variant?: 'default' | 'success' | 'warning' | 'info' | 'attention' | 'error';
  };
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

export interface BranchOpsAlert {
  id: string;
  label: string;
  count: number;
  severity?: 'low' | 'medium' | 'high' | 'critical';
}

export interface BranchOpsSnapshot {
  coverage_rate: number;
  active_branches: number;
  total_branches: number;
  hub_count: number;
  needs_attention: number;
  alerts: BranchOpsAlert[];
  top_branches: Array<{
    id: string;
    name: string;
    status_label: string;
    live_shipments: number;
    active_workers: number;
  }>;
  overview?: Record<string, unknown>;
}

export interface ShipmentMixEntry {
  mode: string;
  label: string;
  count: number;
  active: number;
  percentage?: number;
}

export interface ShipmentMixTrendEntry {
  date: string;
  groupage: number;
  individual: number;
}

export interface ShipmentMix {
  window: { from: string; to: string };
  distribution: ShipmentMixEntry[];
  trend: ShipmentMixTrendEntry[];
}
