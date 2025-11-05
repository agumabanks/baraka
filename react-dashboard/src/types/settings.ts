import type { PaginatedResponse } from './common';

export type ActiveState = 'active' | 'inactive';

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
  role: {
    id: number;
    name: string;
    slug: string;
    status: number;
  } | null;
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
  statuses: Array<{
    value: number;
    label: string;
  }>;
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
  image?: File | null;
}

export interface AdminRolePayload {
  id?: number;
  name: string;
  status: number;
  permissions: string[];
}
