import type { PaginatedResponse } from './common';

export type ActiveState = 'active' | 'inactive';

export interface AdminUserTeam {
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
}

export interface TeamMemberPreview {
  id: number;
  name: string;
  initials?: string;
  status: number;
  role?: string | null;
}

export interface TeamSummary {
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
  recent_hires: number;
  active_ratio: number;
  sample_users: TeamMemberPreview[];
}

export interface RoleSummary {
  role_id: number | null;
  label: string;
  slug?: string | null;
  total: number;
  active: number;
  inactive: number;
  teams: number;
  sample_users: Array<Pick<TeamMemberPreview, 'id' | 'name' | 'initials' | 'status'>>;
}

export interface RecentHireSummary {
  id: number;
  name: string;
  role?: string | null;
  team?: string | null;
  joining_date?: string | null;
}

export interface AdminRole {
  id: number;
  name: string;
  slug: string;
  status: number;
  status_label: ActiveState;
  permissions: string[];
  permissions_count: number;
  users_count: number;
  created_at: string | null;
  updated_at: string | null;
}

export type AdminRoleCollection = PaginatedResponse<AdminRole>;

export interface AdminRoleMeta {
  permissions: Array<{
    id: number;
    attribute: string | null;
    keywords: string[] | null;
  }>;
  statuses: Array<{
    value: number;
    label: string;
  }>;
}

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  mobile: string | null;
  nid_number: string | null;
  address: string | null;
  salary: number | null;
  joining_date: string | null;
  status: number;
  status_label: ActiveState;
  avatar: string | null;
  preferred_language?: 'en' | 'fr' | 'sw';
  role: {
    id: number;
    name: string;
    slug: string;
    status: number;
  } | null;
  role_label?: string | null;
  hub: {
    id: number;
    name: string;
  } | null;
  department: {
    id: number;
    title: string;
  } | null;
  designation: {
    id: number;
    title: string;
  } | null;
  team: AdminUserTeam | null;
  team_label?: string | null;
  primary_branch_id?: number | null;
  primary_branch?: {
    id: number;
    name: string;
    code?: string | null;
    type?: string | null;
  } | null;
  permissions: string[];
  created_at: string | null;
  updated_at: string | null;
}

export type AdminUserCollection = PaginatedResponse<AdminUser>;

export interface AdminUserFilters {
  search?: string;
  role_id?: number;
  status?: number;
  hub_id?: number;
  department_id?: number;
  designation_id?: number;
  page?: number;
  per_page?: number;
}

export interface AdminUserMeta {
  roles: Array<{
    id: number;
    name: string;
    slug: string;
    status: number;
  }>;
  hubs: Array<{
    id: number;
    name: string;
  }>;
  departments: Array<{
    id: number;
    title: string;
  }>;
  designations: Array<{
    id: number;
    title: string;
  }>;
  branches: Array<{
    id: number;
    name: string;
    code?: string | null;
    type?: string | null;
  }>;
  statuses: Array<{
    value: number;
    label: string;
  }>;
  totals?: {
    total: number;
    active: number;
    inactive: number;
    recent_hires: number;
    active_ratio: number;
  };
  team_summary?: TeamSummary[];
  role_summary?: RoleSummary[];
  people_pulse?: {
    recent_hires: RecentHireSummary[];
    awaiting_activation: number;
    trend_window_days?: number;
  };
}

export interface TeamPulseData {
  totals?: {
    total: number;
    active: number;
    inactive: number;
    recent_hires: number;
    active_ratio: number;
  };
  team_summary?: TeamSummary[];
  role_summary?: RoleSummary[];
  people_pulse?: {
    recent_hires: RecentHireSummary[];
    awaiting_activation: number;
    trend_window_days?: number;
  };
}

export interface AdminUserPayload {
  id?: number;
  name: string;
  email: string;
  password?: string;
  mobile: string;
  nid_number?: string | null;
  designation_id: number;
  department_id: number;
  role_id: number;
  hub_id?: number | null;
  joining_date: string;
  salary?: number | null;
  address: string;
  status: number;
  preferred_language?: 'en' | 'fr' | 'sw';
  primary_branch_id?: number | null;
  image?: File | null;
}

export interface AdminUsersBulkAssignPayload {
  user_ids: number[];
  role_id?: number | null;
  hub_id?: number | null;
  department_id?: number | null;
  designation_id?: number | null;
  status?: number | null;
}

export interface AdminUsersBulkAssignResult {
  users: AdminUser[];
  meta: AdminUserMeta;
  applied: {
    fields: string[];
    count: number;
  };
}

export interface AdminRolePayload {
  id?: number;
  name: string;
  status: number;
  permissions: string[];
}
