export interface BranchManager {
  id: number;
  branch_id: number;
  user_id: number;
  business_name: string;
  current_balance: number;
  cod_charges: Record<string, unknown> | null;
  payment_info: Record<string, unknown> | null;
  settlement_config: Record<string, unknown> | null;
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
  };
}

export interface BranchManagerAnalytics {
  shipments: {
    total: number;
    last_30_days: number;
    pending: number;
    completed: number;
  };
  revenue: {
    total: number;
    last_30_days: number;
    average_shipment_value: number;
  };
  performance: {
    delivery_success_rate: number;
    on_time_delivery_rate: number;
    customer_satisfaction: number;
  };
  settlements: {
    pending_amount: number;
    last_settlement_date: string | null;
    total_settled: number;
  };
}

export interface BranchManagerDetail extends BranchManager {
  analytics: BranchManagerAnalytics;
  recent_shipments: Array<{
    id: number;
    tracking_number: string;
    status: string;
    amount: number;
    created_at: string;
  }>;
  settlements: Array<{
    id: number;
    amount: number;
    status: string;
    date: string;
  }>;
}

export interface BranchManagerListResponse {
  managers: BranchManager[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface BranchManagerListParams {
  page?: number;
  per_page?: number;
  search?: string;
  branch_id?: number;
  status?: string;
}

export interface BranchManagerFormData {
  branch_id: number | string;
  user_id: number | string;
  business_name: string;
  cod_charges?: Record<string, unknown>;
  payment_info?: Record<string, unknown>;
  settlement_config?: Record<string, unknown>;
  status: string;
}

export interface BranchOption {
  value: number;
  label: string;
  code: string;
  type: string;
}

export interface UserOption {
  value: number;
  label: string;
  email: string;
}
