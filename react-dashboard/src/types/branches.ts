export interface BranchQueue {
  id: string;
  label: string;
  value: number;
  max: number;
}

export interface BranchWorkforce {
  active: number;
  total: number;
}

export interface BranchMetrics {
  capacity_utilization: number;
  active_clients: number;
  throughput_24h: number;
}

export interface BranchOperatingWindow {
  opening_time: string | null;
}

export interface BranchManagerSummary {
  id: number;
  name: string | null;
  email: string | null;
  phone: string | null;
}

export interface BranchManagerDetail extends BranchManagerSummary {
  business_name: string | null;
  status: number | string | null;
  current_balance: number | null;
  settlement_summary: Record<string, unknown>;
  performance_metrics: {
    shipments_last_30_days: number;
    delivery_success_rate: number;
    on_time_delivery_rate: number;
    revenue_last_30_days: number;
    average_shipment_value: number;
  };
  pending_requests: number;
}

export interface BranchWorkerDetail {
  id: number;
  name: string | null;
  email: string | null;
  phone: string | null;
  role: string | null;
  status: number | string | null;
  assigned_at: string | null;
  active_assignments: number;
}

export interface BranchRecentShipment {
  id: number;
  tracking_number: string | null;
  status: string | null;
  price_amount: number | null;
  service_level: string | null;
  destination_branch: BranchParentSummary | null;
  assigned_worker: string | null;
  created_at: string | null;
  expected_delivery_date: string | null;
}

export interface BranchParentSummary {
  id: number;
  name: string;
  code: string;
}

export interface BranchCoordinates {
  latitude: number | null;
  longitude: number | null;
}

export type BranchOperationalState = 'operational' | 'delayed' | 'maintenance';

export interface BranchListItem {
  id: number;
  code: string;
  name: string;
  type: string;
  status: number;
  status_label: string;
  status_state: BranchOperationalState;
  is_hub: boolean;
  address: string | null;
  coordinates: BranchCoordinates;
  parent: BranchParentSummary | null;
  manager: BranchManagerSummary | null;
  workforce: BranchWorkforce;
  metrics: BranchMetrics;
  queues: BranchQueue[];
  operating: BranchOperatingWindow;
  hierarchy_path: string;
}

export interface BranchChildSummary {
  id: number;
  name: string;
  code: string;
  type: string;
  status: number;
}

export interface BranchDetail extends BranchListItem {
  children: BranchChildSummary[];
  team: {
    manager: BranchManagerDetail | null;
    active_workers: BranchWorkerDetail[];
  };
  recent_shipments: BranchRecentShipment[];
  insights: {
    open_queues: number;
    active_workers: number;
    manager_status: number | string | null;
  };
}

export interface BranchHierarchyNode {
  id: number;
  name: string;
  code: string;
  type: string;
  is_hub: boolean;
  status: number;
  level: number;
  path: string;
  parent_id: number | null;
  managers_count: number;
  workers_count: number;
  capacity_utilization: number;
  children: BranchHierarchyNode[];
}

export interface BranchHierarchyContext {
  ancestors: Array<{ id: number; name: string; code: string; type: string; is_hub: boolean }>;
  descendants: Array<{ id: number; name: string; code: string; type: string; parent_id: number | null }>;
}

export interface BranchListResponse {
  items: BranchListItem[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters: {
    types: string[];
  };
}

export interface BranchDetailResponse {
  branch: BranchDetail;
  analytics: Record<string, unknown>;
  capacity: Record<string, unknown>;
  hierarchy: BranchHierarchyContext;
}

export interface BranchHierarchyResponse {
  tree: BranchHierarchyNode[];
}

export interface BranchListParams {
  page?: number;
  per_page?: number;
  search?: string;
  type?: string;
  status?: string | number;
  is_hub?: boolean;
  parent_id?: number;
}
