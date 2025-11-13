export interface BranchWorker {
  id: number;
  branch_id: number;
  user_id: number;
  role: string;
  permissions: string[] | null;
  work_schedule: Record<string, unknown> | null;
  hourly_rate: number | null;
  assigned_at: string;
  unassigned_at: string | null;
  notes: string | null;
  metadata: Record<string, unknown> | null;
  status: string;
  created_at: string;
  updated_at: string;
  branch?: {
    id: number;
    name: string;
    code: string;
    type: string;
  };
  user?: {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    preferred_language?: 'en' | 'fr' | 'sw' | null;
    primary_branch_id?: number | null;
  };
}

export interface BranchWorkerAnalytics {
  assignments: {
    total: number;
    active: number;
    completed: number;
    pending: number;
  };
  performance: {
    completion_rate: number;
    average_time_per_shipment: number;
    efficiency_score: number;
  };
  workload: {
    current_assignments: number;
    capacity_utilization: number;
    hours_worked_this_week: number;
  };
}

export interface BranchWorkerDetail extends BranchWorker {
  analytics: BranchWorkerAnalytics;
  current_assignments: Array<{
    id: number;
    tracking_number: string;
    status: string;
    assigned_at: string;
  }>;
  work_history: Array<{
    date: string;
    hours_worked: number;
    tasks_completed: number;
  }>;
}

export interface BranchWorkerListResponse {
  workers: BranchWorker[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface BranchWorkerListParams {
  page?: number;
  per_page?: number;
  search?: string;
  branch_id?: number;
  status?: string;
  role?: string;
}

export interface BranchWorkerFormData {
  branch_id: number | string;
  user_id?: number | string;
  role: string;
  permissions?: string[];
  work_schedule?: Record<string, unknown>;
  hourly_rate?: number;
  notes?: string;
  status: string;
  name?: string;
  email?: string;
  phone?: string;
  password?: string;
  address?: string;
  preferred_language?: 'en' | 'fr' | 'sw';
  employment_status?: string;
  contact_phone?: string;
  metadata?: Record<string, unknown>;
}

export interface BranchOption {
  value: number | string;
  label: string;
  code: string;
  type: string;
}

export interface UserOption {
  value: number | string;
  label: string;
  email: string;
  phone?: string | null;
  preferred_language?: 'en' | 'fr' | 'sw' | null;
  primary_branch_id?: number | null;
}
