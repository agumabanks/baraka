export interface WorkflowBoardQueues {
  unassigned_shipments: Array<WorkflowBoardShipment>;
  exceptions: Array<WorkflowBoardException>;
  load_balancing: Record<string, unknown>;
  driver_queues: Array<WorkflowBoardDriverQueue>;
}

export interface WorkflowBoardShipment {
  id?: number | string;
  tracking_number?: string | null;
  service_level?: string | null;
  status?: string | null;
  origin_branch?: string | null;
  destination_branch?: string | null;
  promised_at?: string | null;
  created_at?: string | null;
  priority?: string | null;
}

export interface WorkflowBoardException {
  id?: number | string;
  tracking_number?: string | null;
  exception_type?: string | null;
  exception_severity?: string | null;
  age_hours?: number | null;
  branch?: string | null;
  updated_at?: string | null;
}

export interface WorkflowBoardDriverQueue {
  worker_id: number | string | null;
  worker_name: string | null;
  assigned_shipments: number;
  capacity: number | null;
  utilization: number | null;
}

export interface WorkflowBoardNotification {
  id: number | string;
  title?: string;
  message?: string;
  created_at?: string;
  type?: string;
}

export interface WorkflowBoardResponse {
  hub_branch: {
    id: number;
    name: string;
    code: string | null;
    type: string | null;
  } | null;
  queues: WorkflowBoardQueues;
  dispatch_snapshot: Record<string, unknown> | null;
  kpis: Record<string, unknown>;
  shipment_metrics: Record<string, unknown>;
  worker_utilization: Record<string, unknown>;
  exception_metrics: Record<string, unknown>;
  notifications: WorkflowBoardNotification[];
}
