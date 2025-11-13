/**
 * TypeScript type definitions for Branch Seeding and Operations functionality
 * Based on Laravel API endpoints for branch management and seeding
 */

export interface BranchSeedOperation {
  id: string;
  operation_type: 'dry_run' | 'force_seed' | 'validate';
  branch_id: string;
  branch_name: string;
  status: 'pending' | 'running' | 'completed' | 'failed' | 'cancelled';
  progress_percentage: number;
  current_step?: string;
  total_steps: number;
  completed_steps: number;
  estimated_duration_seconds?: number;
  actual_duration_seconds?: number;
  error_message?: string;
  created_by: string;
  created_at: string;
  started_at?: string;
  completed_at?: string;
  logs?: BranchSeedLog[];
  results?: BranchSeedResults;
  mode?: 'dry_run' | 'force_execute' | 'validate';
  branch_count?: number;
  total_operations?: number;
  successful_operations?: number;
  failed_operations?: number;
}

export interface BranchSeedLog {
  id: string;
  operation_id: string;
  level: 'info' | 'warning' | 'error' | 'success';
  message: string;
  details?: Record<string, unknown>;
  step?: string;
  created_at: string;
  timestamp?: string;
}

export interface BranchSeedResults {
  created_entities: {
    branches: number;
    users: number;
    managers: number;
    workers: number;
    settings: number;
  };
  updated_entities: {
    branches: number;
    users: number;
    managers: number;
    workers: number;
    settings: number;
  };
  failed_entities: {
    branches: number;
    users: number;
    managers: number;
    workers: number;
    settings: number;
  };
  total_processed: number;
  success_rate: number;
  execution_time: number;
}

export interface BranchSeedSimulation {
  id: string;
  name: string;
  description?: string;
  parameters: BranchSeedParameters;
 预估: {
    total_entities: number;
    estimated_duration: number;
    resource_requirements: {
      memory: string;
      storage: string;
      processing_power: string;
    };
    potential_issues: string[];
  };
  simulated_results?: {
    total_operations: number;
    success_rate?: number;
    failure_rate?: number;
    summary?: Record<string, unknown>;
  };
  created_at: string;
}

export interface BranchSeedParameters {
  branch_ids?: string[];
  include_inactive?: boolean;
  force_update?: boolean;
  backup_data?: boolean;
  parallel_processing?: boolean;
  batch_size?: number;
  max_retries?: number;
  branch_count?: number;
  managers_per_branch?: number;
  workers_per_branch?: number;
  include_test_data?: boolean;
  include_sample_shipments?: boolean;
  include_routes?: boolean;
  include_customers?: boolean;
  dry_run?: boolean;
  skip_existing?: boolean;
}

export interface BranchManagement {
  id: string;
  name: string;
  code: string;
  parent_branch_id?: string;
  address: string;
  city: string;
  country: string;
  phone?: string;
  email?: string;
  manager_id?: string;
  manager?: {
    id: string;
    user_id: string;
    name?: string;
    email?: string;
    phone?: string | null;
    preferred_language?: 'en' | 'fr' | 'sw' | null;
    primary_branch_id?: number | null;
    assigned_at?: string | null;
  } | null;
  status: 'active' | 'inactive' | 'maintenance';
  capacity: {
    daily_shipments: number;
    storage_space: number;
    staff_count: number;
  };
  team?: {
    total_workers: number;
    active_workers: number;
    roster_preview: Array<{
      id: string;
      user_id: string;
      name?: string;
      role?: string;
      preferred_language?: 'en' | 'fr' | 'sw' | null;
      status?: unknown;
    }>;
  };
  performance_metrics: BranchPerformanceMetrics;
  created_at: string;
  updated_at: string;
  is_seed_data: boolean;
  last_seeded_at?: string;
  location?: string;
}

export interface BranchPerformanceMetrics {
  total_shipments: number;
  on_time_delivery_rate: number;
  average_processing_time: number;
  exception_rate: number;
  revenue_generated: number;
  customer_satisfaction_score: number;
  staff_utilization_rate: number;
  this_month: {
    shipments: number;
    revenue: number;
    exceptions: number;
  };
  last_month: {
    shipments: number;
    revenue: number;
    exceptions: number;
  };
  trend: 'improving' | 'declining' | 'stable';
}

export interface BranchCapacityMetrics {
  branch_id: string;
  branch_name: string;
  current_capacity_usage: number;
  max_capacity: number;
  utilization_rate: number;
  projections: Array<{
    date: string;
    predicted_usage: number;
    confidence_level: number;
  }>;
  bottlenecks: string[];
  recommendations: string[];
}

export interface BranchAnalytics {
  overview: {
    total_branches: number;
    active_branches: number;
    inactive_branches: number;
    under_maintenance: number;
    average_performance_score: number;
  };
  performance_ranking: Array<{
    branch_id: string;
    branch_name: string;
    performance_score: number;
    rank: number;
    change_from_last_month: number;
  }>;
  capacity_analysis: {
    overutilized: string[];
    underutilized: string[];
    optimal_utilization: string[];
  };
  geographic_distribution: Array<{
    country: string;
    branch_count: number;
    total_shipments: number;
  }>;
  trends: {
    growth_rate: number;
    seasonal_patterns: Array<{
      month: string;
      average_shipments: number;
    }>;
  };
}

export interface BranchMaintenanceWindow {
  id: string;
  branch_id: string;
  start_time: string;
  end_time: string;
  reason: string;
  affected_services: string[];
  status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
  created_by: string;
  created_at: string;
  notification_sent: boolean;
  estimated_downtime: number;
}

export interface BranchAlert {
  id: string;
  branch_id: string;
  type: 'capacity' | 'performance' | 'maintenance' | 'security' | 'system';
  severity: 'low' | 'medium' | 'high' | 'critical';
  title: string;
  description: string;
  threshold_value?: number;
  current_value?: number;
  is_resolved: boolean;
  created_at: string;
  resolved_at?: string;
  resolved_by?: string;
}

export interface BranchConfiguration {
  id: string;
  branch_id: string;
  settings: {
    operating_hours: {
      monday: { open: string; close: string; closed: boolean };
      tuesday: { open: string; close: string; closed: boolean };
      wednesday: { open: string; close: string; closed: boolean };
      thursday: { open: string; close: string; closed: boolean };
      friday: { open: string; close: string; closed: boolean };
      saturday: { open: string; close: string; closed: boolean };
      sunday: { open: string; close: string; closed: boolean };
    };
    shipping_services: string[];
    package_types: string[];
    max_package_weight: number;
    max_package_dimensions: {
      length: number;
      width: number;
      height: number;
    };
    supported_payment_methods: string[];
    special_instructions?: string;
  };
  created_at: string;
  updated_at: string;
}

export interface BranchOperationsFilters {
  status?: 'active' | 'inactive' | 'maintenance';
  performance_score?: {
    min?: number;
    max?: number;
  };
  capacity_utilization?: {
    min?: number;
    max?: number;
  };
  location?: {
    country?: string;
    city?: string;
  };
  last_seeded_at?: {
    from?: string;
    to?: string;
  };
  search?: string;
}

export interface BranchOperationsSummary {
  total_branches: number;
  active_branches: number;
  branches_needing_attention: number;
  branches_under_maintenance: number;
  average_performance_score: number;
  total_seeding_operations: number;
  recent_seeding_operations: number;
  failed_seeding_operations: number;
}

export interface PaginatedBranchOperations {
  data: BranchManagement[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
  summary: BranchOperationsSummary;
}