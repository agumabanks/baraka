import type { PaginationLinks, PaginationMeta } from './common';

export type DriverStatus = 'ACTIVE' | 'INACTIVE' | 'SUSPENDED' | 'ON_LEAVE' | 'OFFBOARDING';

export const DRIVER_STATUS_LABELS: Record<DriverStatus, string> = {
  ACTIVE: 'Active',
  INACTIVE: 'Inactive',
  SUSPENDED: 'Suspended',
  ON_LEAVE: 'On Leave',
  OFFBOARDING: 'Offboarding',
};

export type EmploymentStatus =
  | 'ACTIVE'
  | 'INACTIVE'
  | 'ON_LEAVE'
  | 'TERMINATED'
  | 'PROBATION'
  | 'SUSPENDED';

export const EMPLOYMENT_STATUS_LABELS: Record<EmploymentStatus, string> = {
  ACTIVE: 'Active',
  INACTIVE: 'Inactive',
  ON_LEAVE: 'On Leave',
  TERMINATED: 'Terminated',
  PROBATION: 'Probation',
  SUSPENDED: 'Suspended',
};

export type RosterStatus = 'SCHEDULED' | 'IN_PROGRESS' | 'COMPLETED' | 'NO_SHOW' | 'CANCELLED';

export const ROSTER_STATUS_LABELS: Record<RosterStatus, string> = {
  SCHEDULED: 'Scheduled',
  IN_PROGRESS: 'In Progress',
  COMPLETED: 'Completed',
  NO_SHOW: 'No Show',
  CANCELLED: 'Cancelled',
};

export type DriverTimeLogType = 'CHECK_IN' | 'CHECK_OUT' | 'BREAK_START' | 'BREAK_END' | 'PAUSE';

export const DRIVER_TIME_LOG_LABELS: Record<DriverTimeLogType, string> = {
  CHECK_IN: 'Check In',
  CHECK_OUT: 'Check Out',
  BREAK_START: 'Break Start',
  BREAK_END: 'Break End',
  PAUSE: 'Paused',
};

export interface DriverBranchSummary {
  id: number;
  name: string;
  code: string;
}

export interface DriverUserSummary {
  id: number;
  name: string;
  email: string | null;
}

export interface DriverVehicleSummary {
  id: number;
  registration_number?: string | null;
  make?: string | null;
  model?: string | null;
}

export interface DriverRecord {
  id: number;
  user_id: number | null;
  branch_id: number | null;
  vehicle_id: number | null;
  code: string;
  name: string;
  phone: string | null;
  email: string | null;
  status: DriverStatus;
  employment_status: EmploymentStatus;
  license_number: string | null;
  license_expiry: string | null;
  documents: Record<string, unknown> | null;
  metadata: Record<string, unknown> | null;
  onboarded_at: string | null;
  offboarded_at: string | null;
  branch?: DriverBranchSummary | null;
  user?: DriverUserSummary | null;
  vehicle?: DriverVehicleSummary | null;
}

export interface PaginatedDriverCollection {
  data: DriverRecord[];
  meta: PaginationMeta;
  links: PaginationLinks;
}

export interface DriverListResponse extends PaginatedDriverCollection {}

export interface DriverDetailResponse extends DriverRecord {
  rosters?: DriverRosterRecord[];
}

export interface DriverRosterRecord {
  id: number;
  driver_id: number;
  branch_id: number | null;
  shift_type: string | null;
  start_time: string;
  end_time: string;
  planned_hours: number | null;
  status: RosterStatus;
  metadata: Record<string, unknown> | null;
  branch?: DriverBranchSummary | null;
}

export interface PaginatedRosterCollection {
  data: DriverRosterRecord[];
  meta: PaginationMeta;
  links: PaginationLinks;
}

export interface DriverRosterListResponse extends PaginatedRosterCollection {}

export interface DriverTimeLogRecord {
  id: number;
  driver_id: number;
  roster_id: number | null;
  log_type: DriverTimeLogType;
  logged_at: string;
  latitude: number | null;
  longitude: number | null;
  source: string | null;
  metadata: Record<string, unknown> | null;
}

export interface PaginatedTimeLogCollection {
  data: DriverTimeLogRecord[];
  meta: PaginationMeta;
  links: PaginationLinks;
}

export interface DriverTimeLogListResponse extends PaginatedTimeLogCollection {}

export interface DriverListParams {
  page?: number;
  per_page?: number;
  branch_id?: number;
  status?: DriverStatus;
  employment_status?: EmploymentStatus;
  search?: string;
}

export interface DriverFormPayload {
  user_id?: number;
  branch_id: number | null;
  name: string;
  email?: string | null;
  phone?: string | null;
  status?: DriverStatus;
  employment_status?: EmploymentStatus;
  license_number?: string | null;
  license_expiry?: string | null;
  vehicle_id?: number | null;
  documents?: Record<string, unknown> | null;
  metadata?: Record<string, unknown> | null;
  code?: string;
  password?: string;
}

export interface DriverRosterFormPayload {
  driver_id: number;
  branch_id: number | null;
  shift_type?: string | null;
  start_time: string;
  end_time: string;
  status?: RosterStatus;
  planned_hours?: number | null;
  metadata?: Record<string, unknown> | null;
}

export interface DriverRosterListParams {
  driver_id?: number;
  branch_id?: number;
  status?: RosterStatus;
  from?: string;
  to?: string;
  page?: number;
  per_page?: number;
}

export interface DriverTimeLogFormPayload {
  driver_id: number;
  roster_id?: number | null;
  log_type: DriverTimeLogType;
  logged_at?: string;
  latitude?: number | null;
  longitude?: number | null;
  source?: string | null;
  metadata?: Record<string, unknown> | null;
}

export interface DriverTimeLogListParams {
  driver_id?: number;
  log_type?: DriverTimeLogType;
  page?: number;
  per_page?: number;
}
