/**
 * Mock Dashboard Data
 * Sample data for dashboard demonstration
 */

import type {
  DashboardData,
  KPICard,
  WorkflowItem,
  ChartConfig,
  QuickAction,
  StatementData,
  TeamOverviewEntry,
  DashboardActivityEntry,
} from '../types/dashboard';

/**
 * Mock KPI Cards - Core Metrics
 */
export const mockCoreKPIs: KPICard[] = [
  {
    id: 'total-parcels',
    title: 'Total Parcels',
    value: 2847,
    subtitle: 'This month',
    icon: 'fas fa-box',
    trend: {
      value: 12.5,
      direction: 'up',
    },
    state: 'success',
    drilldownRoute: '/parcels',
    tooltip: 'Total parcels processed this month',
  },
  {
    id: 'total-users',
    title: 'Total Users',
    value: 1523,
    subtitle: 'This month',
    icon: 'fas fa-users',
    trend: {
      value: 8.3,
      direction: 'up',
    },
    state: 'success',
    drilldownRoute: '/users',
    tooltip: 'Total registered users',
  },
  {
    id: 'total-merchants',
    title: 'Total Merchants',
    value: 342,
    subtitle: 'This month',
    icon: 'fas fa-store',
    trend: {
      value: 5.7,
      direction: 'up',
    },
    state: 'success',
    drilldownRoute: '/merchants',
    tooltip: 'Total registered merchants',
  },
  {
    id: 'total-delivery-men',
    title: 'Delivery Personnel',
    value: 186,
    subtitle: 'This month',
    icon: 'fas fa-truck',
    trend: {
      value: 3.2,
      direction: 'up',
    },
    state: 'success',
    drilldownRoute: '/delivery-men',
    tooltip: 'Total delivery personnel',
  },
  {
    id: 'total-hubs',
    title: 'Total Hubs',
    value: 24,
    subtitle: 'This month',
    icon: 'fas fa-map-marker-alt',
    trend: {
      value: 1.8,
      direction: 'stable',
    },
    state: 'success',
    drilldownRoute: '/hubs',
    tooltip: 'Total operational hubs',
  },
  {
    id: 'total-accounts',
    title: 'Total Accounts',
    value: 89,
    subtitle: 'This month',
    icon: 'fas fa-calculator',
    trend: {
      value: 4.1,
      direction: 'up',
    },
    state: 'success',
    drilldownRoute: '/accounts',
    tooltip: 'Total financial accounts',
  },
  {
    id: 'total-customers',
    title: 'Total Customers',
    value: 1876,
    subtitle: 'This month',
    icon: 'fas fa-user-friends',
    trend: {
      value: 6.9,
      direction: 'up',
    },
    state: 'success',
    drilldownRoute: '/customers',
    tooltip: 'Total customer accounts',
  },
  {
    id: 'partial-delivered',
    title: 'Partial Deliveries',
    value: 43,
    subtitle: 'This month',
    icon: 'fas fa-exclamation-triangle',
    trend: {
      value: 2.1,
      direction: 'down',
    },
    state: 'warning',
    drilldownRoute: '/parcels?status=partial',
    tooltip: 'Parcels with partial delivery',
  },
  {
    id: 'total-delivered',
    title: 'Total Delivered',
    value: 2534,
    subtitle: 'This month',
    icon: 'fas fa-check-circle',
    trend: {
      value: 18.7,
      direction: 'up',
    },
    state: 'success',
    drilldownRoute: '/parcels?status=delivered',
    tooltip: 'Successfully delivered parcels',
  },
];

/**
 * Mock Health KPIs
 */
export const mockHealthKPIs = {
  slaStatus: {
    id: 'sla-status',
    title: 'SLA Performance',
    value: '94.2%',
    subtitle: 'Last 24 hours',
    icon: 'fas fa-clipboard-check',
    trend: {
      value: 2.3,
      direction: 'up' as const,
    },
    state: 'success' as const,
    tooltip: 'Service Level Agreement performance',
  },
  exceptions: {
    id: 'exceptions',
    title: 'Active Exceptions',
    value: 12,
    subtitle: 'Requires attention',
    icon: 'fas fa-exclamation-circle',
    trend: {
      value: 15.4,
      direction: 'down' as const,
    },
    state: 'warning' as const,
    tooltip: 'Current exception cases',
  },
  onTimeDelivery: {
    id: 'on-time-delivery',
    title: 'On-Time Delivery',
    value: '91.8%',
    subtitle: 'This week',
    icon: 'fas fa-clock',
    trend: {
      value: 3.2,
      direction: 'up' as const,
    },
    state: 'success' as const,
    tooltip: 'Percentage of on-time deliveries',
  },
  openTickets: {
    id: 'open-tickets',
    title: 'Open Tickets',
    value: 28,
    subtitle: 'Support queue',
    icon: 'fas fa-ticket-alt',
    trend: {
      value: 5.1,
      direction: 'down' as const,
    },
    state: 'neutral' as const,
    tooltip: 'Current open support tickets',
  },
};

/**
 * Mock Workflow Queue Items
 */
export const mockWorkflowItems: WorkflowItem[] = [
  {
    id: 'wf-1',
    title: 'Process pending merchant payments',
    description: '15 merchants awaiting payment approval',
    status: 'pending',
    priority: 5,
    assignedTo: 'Finance Team',
    dueDate: 'Today',
    actionUrl: '/payments/pending',
  },
  {
    id: 'wf-2',
    title: 'Review delivery exceptions',
    description: '8 parcels require attention',
    status: 'in_progress',
    priority: 4,
    assignedTo: 'Operations',
    dueDate: 'Today',
    actionUrl: '/parcels/exceptions',
  },
  {
    id: 'wf-3',
    title: 'Weekly hub reconciliation',
    description: 'Reconcile accounts for 5 hubs',
    status: 'pending',
    priority: 3,
    assignedTo: 'Accounting',
    dueDate: 'Tomorrow',
    actionUrl: '/hubs/reconciliation',
  },
  {
    id: 'wf-4',
    title: 'Update merchant delivery rates',
    description: 'Rate review for Q4 2025',
    status: 'delayed',
    priority: 4,
    assignedTo: 'Pricing Team',
    dueDate: 'Overdue',
    actionUrl: '/merchants/rates',
  },
  {
    id: 'wf-5',
    title: 'Driver training session',
    description: 'Schedule training for new drivers',
    status: 'pending',
    priority: 2,
    assignedTo: 'HR Department',
    dueDate: 'This week',
    actionUrl: '/training/schedule',
  },
];

/**
 * Mock Financial Statements
 */
export const mockStatements = {
  deliveryMan: {
    income: 125000,
    expense: 98000,
    balance: 27000,
    currency: '$',
  } as StatementData,
  merchant: {
    income: 450000,
    expense: 380000,
    balance: 70000,
    currency: '$',
  } as StatementData,
  hub: {
    income: 280000,
    expense: 215000,
    balance: 65000,
    currency: '$',
  } as StatementData,
};

/**
 * Mock Chart Configurations
 */
export const mockCharts = {
  incomeExpense: {
    title: 'Income / Expense Trends',
    type: 'area' as const,
    height: 300,
    data: [
      { label: '2025-09-24', value: 12000, category: 'income' },
      { label: '2025-09-25', value: 15000, category: 'income' },
      { label: '2025-09-26', value: 13500, category: 'income' },
      { label: '2025-09-27', value: 16000, category: 'income' },
      { label: '2025-09-28', value: 14500, category: 'income' },
      { label: '2025-09-29', value: 17000, category: 'income' },
      { label: '2025-09-30', value: 15500, category: 'income' },
    ],
  } as ChartConfig,
  courierRevenue: {
    title: 'Courier Revenue',
    type: 'polar' as const,
    height: 300,
    data: [
      { label: 'Income', value: 450000 },
      { label: 'Expense', value: 380000 },
    ],
  } as ChartConfig,
  cashCollection: {
    title: 'Cash Collection',
    type: 'bar' as const,
    height: 260,
    data: [
      { label: 'Mon', value: 8500 },
      { label: 'Tue', value: 9200 },
      { label: 'Wed', value: 7800 },
      { label: 'Thu', value: 10500 },
      { label: 'Fri', value: 11200 },
      { label: 'Sat', value: 6800 },
      { label: 'Sun', value: 5200 },
    ],
  } as ChartConfig,
};

/**
 * Mock Quick Actions
 */
export const mockQuickActions: QuickAction[] = [
  {
    id: 'book-shipment',
    title: 'Book Shipment',
    icon: 'fas fa-clipboard-check',
    url: '/booking/step1',
    badge: 3,
    shortcut: 'Ctrl+B',
    description: 'Launch the guided booking flow',
  },
  {
    id: 'bulk-upload',
    title: 'Bulk Upload',
    icon: 'fas fa-file-upload',
    url: '/parcels/import',
    shortcut: 'Ctrl+U',
    description: 'Upload parcels from a spreadsheet',
  },
  {
    id: 'view-parcels',
    title: 'View All Parcels',
    icon: 'fas fa-dolly',
    url: '/parcels',
    badge: 2847,
    description: 'Inspect the live parcel pipeline',
  },
];

/**
 * Mock Team Overview
 */
export const mockTeamOverview: TeamOverviewEntry[] = [
  {
    id: 'ops',
    label: 'Operations',
    department: { id: 1, title: 'Operations' },
    hub: { id: 101, name: 'Kampala HQ' },
    total: 48,
    active: 42,
    inactive: 6,
    active_ratio: 0.875,
    recent_hires: 3,
    sample_users: [
      { id: 1, name: 'Aisha K', status: 1 },
      { id: 2, name: 'Daniel M', status: 1 },
      { id: 3, name: 'Brenda T', status: 0 },
    ],
  },
  {
    id: 'customer-success',
    label: 'Customer Success',
    department: { id: 2, title: 'Customer Success' },
    hub: { id: 102, name: 'Nairobi Support' },
    total: 26,
    active: 24,
    inactive: 2,
    active_ratio: 0.923,
    recent_hires: 1,
    sample_users: [
      { id: 11, name: 'Chris L', status: 1 },
      { id: 12, name: 'Zayne P', status: 1 },
      { id: 13, name: 'Irene W', status: 1 },
    ],
  },
  {
    id: 'fleet',
    label: 'Fleet & Dispatch',
    department: { id: 3, title: 'Fleet' },
    hub: { id: 103, name: 'Entebbe Dispatch' },
    total: 64,
    active: 57,
    inactive: 7,
    active_ratio: 0.89,
    recent_hires: 5,
    sample_users: [
      { id: 21, name: 'James O', status: 1 },
      { id: 22, name: 'Hanifa G', status: 1 },
      { id: 23, name: 'Peter B', status: 0 },
    ],
  },
];

/**
 * Mock Activity Timeline
 */
export const mockActivityTimeline: DashboardActivityEntry[] = [
  {
    id: 'activity-1',
    action: 'shipment_initiated',
    details: { tracking: 'BRX12345', customer: 'Nexus Gadgets' },
    createdAt: new Date().toISOString(),
    actor: { id: 'user-1', name: 'Jacinta Atwine' },
    task: { id: 'wf-21', title: 'Schedule pickup', status: 'in_progress' },
  },
  {
    id: 'activity-2',
    action: 'cod_settlement_requested',
    details: { amount: 1250000, currency: 'UGX' },
    createdAt: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
    actor: { id: 'user-5', name: 'Brian Kintu' },
    task: { id: 'wf-42', title: 'Settle COD batch', status: 'pending' },
  },
  {
    id: 'activity-3',
    action: 'incident_resolved',
    details: { incident: 'INC-7782', severity: 'medium' },
    createdAt: new Date(Date.now() - 45 * 60 * 1000).toISOString(),
    actor: { id: 'user-7', name: 'Support Bot' },
    task: { id: 'wf-10', title: 'Delivery exception', status: 'resolved' },
  },
];

/**
 * Complete Mock Dashboard Data
 */
export const mockDashboardData: DashboardData = {
  dateFilter: {
    from: '2025-09-01',
    to: '2025-09-30',
    preset: 'month',
  },
  healthKPIs: mockHealthKPIs,
  coreKPIs: mockCoreKPIs,
  workflowQueue: mockWorkflowItems,
  statements: mockStatements,
  charts: mockCharts,
  quickActions: mockQuickActions,
  teamOverview: mockTeamOverview,
  activityTimeline: mockActivityTimeline,
  loading: false,
};
