import axios from 'axios';
import type { AxiosInstance, InternalAxiosRequestConfig, AxiosError } from 'axios';
import type {
  SalesAddressBookEntry,
  SalesAddressBookListResponse,
  SalesContract,
  SalesContractListResponse,
  SalesCustomer,
  SalesCustomerDetail,
  SalesCustomerListResponse,
  SalesQuotation,
  SalesQuotationListResponse,
} from '../types/sales';
import type {
  PaginationMeta,
  Support,
  SupportCreateMeta,
  SupportDetail,
  SupportEditMeta,
  SupportFilters,
  SupportFormData,
  SupportListResponse,
  SupportReplyData,
  SupportSelectOption,
  SupportSummary,
} from '../types/support';
import type {
  BranchDetailResponse,
  BranchHierarchyResponse,
  BranchFormPayload,
  BranchListParams,
  BranchListResponse,
  BranchStatusValue,
} from '../types/branches';
import type {
  DriverDetailResponse,
  DriverFormPayload,
  DriverListParams,
  DriverListResponse,
  DriverRosterFormPayload,
  DriverRosterListParams,
  DriverRosterListResponse,
  DriverRosterRecord,
  DriverTimeLogFormPayload,
  DriverTimeLogListParams,
  DriverTimeLogListResponse,
  DriverTimeLogRecord,
} from '../types/drivers';
import type {
  MerchantDetailResponse,
  MerchantListParams,
  MerchantListResponse,
} from '../types/merchants';
import type { WorkflowBoardResponse } from '../types/workflow';
import { toast } from '../stores/toastStore';
import type {
  BranchManager,
  BranchManagerDetail,
  BranchManagerFormData,
  BranchManagerListParams,
  BranchManagerListResponse,
  BranchOption,
  UserOption,
} from '../types/branchManagers';
import type {
  BranchWorker,
  BranchWorkerDetail,
  BranchWorkerFormData,
  BranchWorkerListParams,
  BranchWorkerListResponse,
} from '../types/branchWorkers';
import type {
  AdminRole,
  AdminRoleCollection,
  AdminRoleMeta,
  AdminRolePayload,
  AdminUser,
  AdminUserCollection,
  AdminUserFilters,
  AdminUserMeta,
  AdminUserPayload,
  AdminUsersBulkAssignPayload,
  AdminUsersBulkAssignResult,
} from '../types/settings';

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

type ApiListParams = Record<string, string | number | undefined>;

export const redirectToLogin = () => {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    window.localStorage.removeItem('auth_token');
    window.localStorage.removeItem('user');
  } catch (error) {
    console.error('Failed to clear auth storage', error);
  }

  const currentPath = window.location.pathname;
  if (currentPath !== '/login' && currentPath !== '/register') {
    window.location.replace('/login');
  }
};

const DEFAULT_DEV_API_BASE = 'http://localhost:8000/api';
const DEFAULT_PROD_API_BASE = 'https://baraka.sanaa.ug/api';

const sanitizeBaseUrl = (url: string): string => url.replace(/\/$/, '');

const resolveApiBaseUrl = (): string => {
  const envUrl = import.meta.env.VITE_API_URL as string | undefined;

  if (envUrl && envUrl.trim().length > 0) {
    return sanitizeBaseUrl(envUrl);
  }

  if (typeof window !== 'undefined') {
    const { hostname } = window.location;

    if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '::1') {
      return DEFAULT_DEV_API_BASE;
    }

    return DEFAULT_PROD_API_BASE;
  }

  return import.meta.env.PROD ? DEFAULT_PROD_API_BASE : DEFAULT_DEV_API_BASE;
};

const API_BASE_URL = resolveApiBaseUrl();

const resolveSanctumUrl = (): string => {
  const envSanctum = import.meta.env.VITE_SANCTUM_URL as string | undefined;

  if (envSanctum && envSanctum.trim().length > 0) {
    return envSanctum;
  }

  try {
    const resolved = new URL(API_BASE_URL);
    return `${resolved.origin}/sanctum/csrf-cookie`;
  } catch (error) {
    if (typeof window !== 'undefined') {
      return `${window.location.origin.replace(/\/$/, '')}/sanctum/csrf-cookie`;
    }
    return `${DEFAULT_PROD_API_BASE.replace(/\/$/, '')}/sanctum/csrf-cookie`;
  }
};

const SANCTUM_URL = resolveSanctumUrl();

// Create axios instance with default config
const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'apiKey': '123456rx-ecourier123456', // Required API key for backend
  },
});

if (typeof window !== 'undefined') {
  const storedLocale = window.localStorage.getItem('dashboard_locale');
  if (storedLocale) {
    api.defaults.headers.common['Accept-Language'] = storedLocale;
  }
}

// Request interceptor to add auth token
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('auth_token');
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    const status = error.response?.status;
    const responseData = error.response?.data as Record<string, unknown> | undefined;
    const message = typeof responseData?.message === 'string'
      ? responseData.message
      : error.message ?? 'An unexpected error occurred';

    if (!status) {
      toast.error({
        title: 'Network error',
        description: 'Unable to reach the server. Check your connection and try again.',
      });
    } else if (status === 401 || status === 419) {
      redirectToLogin();
    } else if (status === 403) {
      toast.error({
        title: 'Access denied',
        description: message,
      });
    } else if (status >= 500) {
      toast.error({
        title: 'Server error',
        description: message,
      });
    } else {
      toast.warning({
        title: 'Request failed',
        description: message,
      });
    }

    return Promise.reject(error);
  }
);

// Get CSRF token from Laravel Sanctum
export const getCsrfToken = async (): Promise<void> => {
  await axios.get(SANCTUM_URL, {
    withCredentials: true,
  });
};

// Auth API functions
export const authApi = {
  login: async (email: string, password: string) => {
    await getCsrfToken();
    const response = await api.post('/auth/login', { email, password });
    return response.data;
  },

  register: async (name: string, email: string, password: string, password_confirmation: string) => {
    await getCsrfToken();
    const response = await api.post('/auth/register', {
      name,
      email,
      password,
      password_confirmation,
    });
    return response.data;
  },

  logout: async () => {
    const response = await api.post('/auth/logout');
    return response.data;
  },

  getUser: async () => {
    const response = await api.get('/auth/user');
    return response.data;
  },
};

// Dashboard API functions
export const dashboardApi = {
  getKPIs: async () => {
    const response = await api.get('/v10/dashboard/kpis');
    return response.data;
  },

  getCharts: async () => {
    const response = await api.get('/v10/dashboard/charts');
    return response.data;
  },

  getWorkflowQueue: async () => {
    const response = await api.get('/v10/dashboard/workflow-queue');
    return response.data;
  },

  getData: async () => {
    const response = await api.get('/v10/dashboard/data');
    return response.data;
  },
};

export const navigationApi = {
  getAdminNavigation: async () => {
    const response = await api.get('/navigation/admin');
    return response.data;
  },
};

export const salesApi = {
  getCustomers: async (params?: ApiListParams): Promise<ApiResponse<SalesCustomerListResponse>> => {
    const response = await api.get('/sales/customers', { params });
    return response.data;
  },
  getCustomerMeta: async (): Promise<ApiResponse<{ hub_options: Array<{ value: string; label: string }>; status_options: Array<{ value: string; label: string }>; engagement_options: Array<{ value: string; label: string }> }>> => {
    const response = await api.get('/sales/customers/meta');
    return response.data;
  },
  createCustomer: async (payload: Record<string, unknown>): Promise<ApiResponse<{ customer: SalesCustomer }>> => {
    const response = await api.post('/sales/customers', payload);
    return response.data;
  },
  getCustomerDetail: async (customerId: number): Promise<ApiResponse<SalesCustomerDetail>> => {
    const response = await api.get(`/sales/customers/${customerId}`);
    return response.data;
  },
  updateCustomer: async (customerId: number, payload: Record<string, unknown>): Promise<ApiResponse<{ customer: SalesCustomer }>> => {
    const response = await api.put(`/sales/customers/${customerId}`, payload);
    return response.data;
  },
  deleteCustomer: async (customerId: number): Promise<ApiResponse<unknown>> => {
    const response = await api.delete(`/sales/customers/${customerId}`);
    return response.data;
  },
  getQuotations: async (params?: ApiListParams): Promise<ApiResponse<SalesQuotationListResponse>> => {
    const response = await api.get('/sales/quotations', { params });
    return response.data;
  },
  createQuotation: async (payload: Record<string, unknown>): Promise<ApiResponse<{ quotation: SalesQuotation }>> => {
    const response = await api.post('/sales/quotations', payload);
    return response.data;
  },
  getContracts: async (params?: ApiListParams): Promise<ApiResponse<SalesContractListResponse>> => {
    const response = await api.get('/sales/contracts', { params });
    return response.data;
  },
  createContract: async (payload: Record<string, unknown>): Promise<ApiResponse<{ contract: SalesContract }>> => {
    const response = await api.post('/sales/contracts', payload);
    return response.data;
  },
  getAddressBook: async (params?: ApiListParams): Promise<ApiResponse<SalesAddressBookListResponse>> => {
    const response = await api.get('/sales/address-book', { params });
    return response.data;
  },
  createAddressBookEntry: async (payload: Record<string, unknown>): Promise<ApiResponse<{ entry: SalesAddressBookEntry }>> => {
    const response = await api.post('/sales/address-book', payload);
    return response.data;
  },
};

export const branchesApi = {
  getBranches: async (params?: BranchListParams): Promise<ApiResponse<BranchListResponse>> => {
    const response = await api.get<ApiResponse<BranchListResponse>>('/v10/branches', { params });
    return response.data;
  },
  getBranch: async (branchId: number | string): Promise<ApiResponse<BranchDetailResponse>> => {
    const response = await api.get<ApiResponse<BranchDetailResponse>>(`/v10/branches/${branchId}`);
    return response.data;
  },
  getHierarchy: async (): Promise<ApiResponse<BranchHierarchyResponse>> => {
    const response = await api.get<ApiResponse<BranchHierarchyResponse>>('/v10/branches/hierarchy');
    return response.data;
  },
  createBranch: async (payload: BranchFormPayload): Promise<ApiResponse<BranchDetailResponse>> => {
    const response = await api.post<ApiResponse<BranchDetailResponse>>('/v10/branches', payload);
    return response.data;
  },
  updateBranch: async (branchId: number | string, payload: Partial<BranchFormPayload>): Promise<ApiResponse<BranchDetailResponse>> => {
    const response = await api.put<ApiResponse<BranchDetailResponse>>(`/v10/branches/${branchId}`, payload);
    return response.data;
  },
  toggleStatus: async (branchId: number | string, status: BranchStatusValue): Promise<ApiResponse<BranchDetailResponse>> => {
    const response = await api.patch<ApiResponse<BranchDetailResponse>>(`/v10/branches/${branchId}/status`, { status });
    return response.data;
  },
};

export const branchManagersApi = {
  getManagers: async (params?: BranchManagerListParams): Promise<ApiResponse<BranchManagerListResponse>> => {
    const response = await api.get('/admin/branch-managers', { params });
    return response.data;
  },
  getManager: async (managerId: number | string): Promise<ApiResponse<{ manager: BranchManagerDetail; analytics: Record<string, unknown> }>> => {
    const response = await api.get(`/admin/branch-managers/${managerId}`);
    return response.data;
  },
  createManager: async (data: BranchManagerFormData): Promise<ApiResponse<{ manager: BranchManager }>> => {
    const response = await api.post('/admin/branch-managers', data);
    return response.data;
  },
  updateManager: async (managerId: number | string, data: BranchManagerFormData): Promise<ApiResponse<{ manager: BranchManager }>> => {
    const response = await api.put(`/admin/branch-managers/${managerId}`, data);
    return response.data;
  },
  deleteManager: async (managerId: number | string): Promise<ApiResponse<unknown>> => {
    const response = await api.delete(`/admin/branch-managers/${managerId}`);
    return response.data;
  },
  getAvailableBranches: async (): Promise<ApiResponse<{ branches: BranchOption[] }>> => {
    const response = await api.get('/admin/branch-managers/create');
    return response.data;
  },
  updateBalance: async (managerId: number | string, amount: number, type: string): Promise<ApiResponse<unknown>> => {
    const response = await api.post(`/admin/branch-managers/${managerId}/balance/update`, { amount, type });
    return response.data;
  },
  getSettlements: async (managerId: number | string): Promise<ApiResponse<unknown>> => {
    const response = await api.get(`/admin/branch-managers/${managerId}/settlements`);
    return response.data;
  },
  bulkUpdateStatus: async (managerIds: number[], status: string): Promise<ApiResponse<unknown>> => {
    const response = await api.post('/admin/branch-managers/bulk-status-update', { manager_ids: managerIds, status });
    return response.data;
  },
};

export const branchWorkersApi = {
  getWorkers: async (params?: BranchWorkerListParams): Promise<ApiResponse<BranchWorkerListResponse>> => {
    const response = await api.get('/admin/branch-workers', { params });
    return response.data;
  },
  getWorker: async (workerId: number | string): Promise<ApiResponse<{ worker: BranchWorkerDetail; analytics: Record<string, unknown> }>> => {
    const response = await api.get(`/admin/branch-workers/${workerId}`);
    return response.data;
  },
  createWorker: async (data: BranchWorkerFormData): Promise<ApiResponse<{ worker: BranchWorker }>> => {
    const response = await api.post('/admin/branch-workers', data);
    return response.data;
  },
  updateWorker: async (workerId: number | string, data: BranchWorkerFormData): Promise<ApiResponse<{ worker: BranchWorker }>> => {
    const response = await api.put(`/admin/branch-workers/${workerId}`, data);
    return response.data;
  },
  deleteWorker: async (workerId: number | string): Promise<ApiResponse<unknown>> => {
    const response = await api.delete(`/admin/branch-workers/${workerId}`);
    return response.data;
  },
  getAvailableResources: async (): Promise<ApiResponse<{ branches: BranchOption[]; users: UserOption[] }>> => {
    const response = await api.get('/admin/branch-workers/create');
    return response.data;
  },
  unassignWorker: async (workerId: number | string): Promise<ApiResponse<unknown>> => {
    const response = await api.post(`/admin/branch-workers/${workerId}/unassign`);
    return response.data;
  },
  assignShipment: async (workerId: number | string, shipmentId: number): Promise<ApiResponse<unknown>> => {
    const response = await api.post(`/admin/branch-workers/${workerId}/assign-shipment`, { shipment_id: shipmentId });
    return response.data;
  },
  bulkUpdateStatus: async (workerIds: number[], status: string): Promise<ApiResponse<unknown>> => {
    const response = await api.post('/admin/branch-workers/bulk-status-update', { worker_ids: workerIds, status });
    return response.data;
  },
};

export const driversApi = {
  getDrivers: async (params?: DriverListParams): Promise<ApiResponse<DriverListResponse>> => {
    const response = await api.get<ApiResponse<DriverListResponse>>('/v10/drivers', { params });
    return response.data;
  },
  getDriver: async (driverId: number | string): Promise<ApiResponse<DriverDetailResponse>> => {
    const response = await api.get<ApiResponse<DriverDetailResponse>>(`/v10/drivers/${driverId}`);
    return response.data;
  },
  createDriver: async (payload: DriverFormPayload): Promise<ApiResponse<DriverDetailResponse>> => {
    const response = await api.post<ApiResponse<DriverDetailResponse>>('/v10/drivers', payload);
    return response.data;
  },
  updateDriver: async (driverId: number | string, payload: DriverFormPayload): Promise<ApiResponse<DriverDetailResponse>> => {
    const response = await api.put<ApiResponse<DriverDetailResponse>>(`/v10/drivers/${driverId}`, payload);
    return response.data;
  },
  toggleStatus: async (driverId: number | string, status: DriverDetailResponse['status']): Promise<ApiResponse<DriverDetailResponse>> => {
    const response = await api.patch<ApiResponse<DriverDetailResponse>>(`/v10/drivers/${driverId}/status`, { status });
    return response.data;
  },
};

export const driverRostersApi = {
  getRosters: async (params?: DriverRosterListParams): Promise<ApiResponse<DriverRosterListResponse>> => {
    const response = await api.get<ApiResponse<DriverRosterListResponse>>('/v10/driver-rosters', { params });
    return response.data;
  },
  createRoster: async (payload: DriverRosterFormPayload): Promise<ApiResponse<DriverRosterRecord>> => {
    const response = await api.post<ApiResponse<DriverRosterRecord>>('/v10/driver-rosters', payload);
    return response.data;
  },
  updateRoster: async (rosterId: number | string, payload: Partial<DriverRosterFormPayload>): Promise<ApiResponse<DriverRosterRecord>> => {
    const response = await api.put<ApiResponse<DriverRosterRecord>>(`/v10/driver-rosters/${rosterId}`, payload);
    return response.data;
  },
  deleteRoster: async (rosterId: number | string): Promise<ApiResponse<unknown>> => {
    const response = await api.delete<ApiResponse<unknown>>(`/v10/driver-rosters/${rosterId}`);
    return response.data;
  },
};

export const driverTimeLogsApi = {
  getLogs: async (params?: DriverTimeLogListParams): Promise<ApiResponse<DriverTimeLogListResponse>> => {
    const response = await api.get<ApiResponse<DriverTimeLogListResponse>>('/v10/driver-time-logs', { params });
    return response.data;
  },
  createLog: async (payload: DriverTimeLogFormPayload): Promise<ApiResponse<DriverTimeLogRecord>> => {
    const response = await api.post<ApiResponse<DriverTimeLogRecord>>('/v10/driver-time-logs', payload);
    return response.data;
  },
};

const buildUserFormData = (payload: AdminUserPayload, isUpdate = false): FormData => {
  const formData = new FormData();
  formData.append('name', payload.name);
  formData.append('email', payload.email);
  formData.append('mobile', payload.mobile);
  if (payload.password) {
    formData.append('password', payload.password);
  }
  if (payload.nid_number) {
    formData.append('nid_number', payload.nid_number);
  }
  formData.append('designation_id', String(payload.designation_id));
  formData.append('department_id', String(payload.department_id));
  formData.append('role_id', String(payload.role_id));
  if (payload.hub_id !== undefined && payload.hub_id !== null) {
    formData.append('hub_id', String(payload.hub_id));
  }
  formData.append('joining_date', payload.joining_date);
  if (typeof payload.salary === 'number') {
    formData.append('salary', String(payload.salary));
  }
  formData.append('address', payload.address);
  formData.append('status', String(payload.status));
  if (payload.image instanceof File) {
    formData.append('image', payload.image);
  }
  if (isUpdate) {
    formData.append('_method', 'PUT');
  }

  return formData;
};

export const adminUsersApi = {
  getUsers: async (filters?: AdminUserFilters): Promise<AdminUserCollection & { success?: boolean; message?: string }> => {
    const response = await api.get<AdminUserCollection & { success?: boolean; message?: string }>('/admin/users', { params: filters });
    return response.data;
  },
  getUser: async (userId: number | string): Promise<ApiResponse<AdminUser>> => {
    const response = await api.get<ApiResponse<AdminUser>>(`/admin/users/${userId}`);
    return response.data;
  },
  getMeta: async (): Promise<ApiResponse<AdminUserMeta>> => {
    const response = await api.get<ApiResponse<AdminUserMeta>>('/admin/users/meta');
    return response.data;
  },
  createUser: async (payload: AdminUserPayload): Promise<ApiResponse<AdminUser>> => {
    const formData = buildUserFormData(payload);
    const response = await api.post<ApiResponse<AdminUser>>('/admin/users', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },
  updateUser: async (userId: number | string, payload: AdminUserPayload): Promise<ApiResponse<AdminUser>> => {
    const formData = buildUserFormData(payload, true);
    const response = await api.post<ApiResponse<AdminUser>>(`/admin/users/${userId}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },
  deleteUser: async (userId: number | string): Promise<ApiResponse<unknown>> => {
    const response = await api.delete<ApiResponse<unknown>>(`/admin/users/${userId}`);
    return response.data;
  },
  bulkAssign: async (payload: AdminUsersBulkAssignPayload): Promise<ApiResponse<AdminUsersBulkAssignResult>> => {
    const response = await api.post<ApiResponse<AdminUsersBulkAssignResult>>('/admin/users/bulk-assign', payload);
    return response.data;
  },
};

export const adminRolesApi = {
  getRoles: async (params?: { page?: number; per_page?: number; search?: string; status?: number }): Promise<AdminRoleCollection & { success?: boolean; message?: string }> => {
    const response = await api.get<AdminRoleCollection & { success?: boolean; message?: string }>('/admin/roles', { params });
    return response.data;
  },
  getMeta: async (): Promise<ApiResponse<AdminRoleMeta>> => {
    const response = await api.get<ApiResponse<AdminRoleMeta>>('/admin/roles/meta');
    return response.data;
  },
  createRole: async (payload: AdminRolePayload): Promise<ApiResponse<AdminRole>> => {
    const response = await api.post<ApiResponse<AdminRole>>('/admin/roles', payload);
    return response.data;
  },
  updateRole: async (roleId: number | string, payload: AdminRolePayload): Promise<ApiResponse<AdminRole>> => {
    const response = await api.put<ApiResponse<AdminRole>>(`/admin/roles/${roleId}`, payload);
    return response.data;
  },
  deleteRole: async (roleId: number | string): Promise<ApiResponse<unknown>> => {
    const response = await api.delete<ApiResponse<unknown>>(`/admin/roles/${roleId}`);
    return response.data;
  },
  toggleStatus: async (roleId: number | string): Promise<ApiResponse<{ status: number }>> => {
    const response = await api.patch<ApiResponse<{ status: number }>>(`/admin/roles/${roleId}/status`);
    return response.data;
  },
};

export const merchantsApi = {
  getMerchants: async (params?: MerchantListParams): Promise<ApiResponse<MerchantListResponse>> => {
    const response = await api.get<ApiResponse<MerchantListResponse>>('/v10/merchants', { params });
    return response.data;
  },
  getMerchant: async (merchantId: number | string): Promise<ApiResponse<MerchantDetailResponse>> => {
    const response = await api.get<ApiResponse<MerchantDetailResponse>>(`/v10/merchants/${merchantId}`);
    return response.data;
  },
};

export const workflowApi = {
  getBoard: async (): Promise<ApiResponse<WorkflowBoardResponse>> => {
    const response = await api.get<ApiResponse<WorkflowBoardResponse>>('/v10/workflow-board');
    return response.data;
  },
};

export const workflowQueueApi = {
  getQueue: async () => {
    const response = await api.get('/v10/dashboard/workflow-queue');
    return response.data;
  },
  create: async (data: Record<string, unknown>) => {
    const response = await api.post('/v10/workflow-items', data);
    return response.data;
  },
  update: async (id: string, data: Record<string, unknown>) => {
    const response = await api.put(`/v10/workflow-items/${id}`, data);
    return response.data;
  },
  delete: async (id: string) => {
    const response = await api.delete(`/v10/workflow-items/${id}`);
    return response.data;
  },
  updateStatus: async (id: string, status: string) => {
    const response = await api.patch(`/v10/workflow-items/${id}/status`, { status });
    return response.data;
  },
  assign: async (id: string, assignedTo: string) => {
    const response = await api.patch(`/v10/workflow-items/${id}/assign`, { assigned_to: assignedTo });
    return response.data;
  },
  bulkUpdate: async (ids: string[], data: Record<string, unknown>) => {
    const response = await api.post('/v10/workflow-items/bulk-update', { ids, data });
    return response.data;
  },
  bulkDelete: async (ids: string[]) => {
    const response = await api.post('/v10/workflow-items/bulk-delete', { ids });
    return response.data;
  },
  addComment: async (id: string, text: string) => {
    const response = await api.post(`/v10/workflow-items/${id}/comments`, { text });
    return response.data;
  },
  updateComment: async (id: string, commentId: string, text: string) => {
    const response = await api.put(`/v10/workflow-items/${id}/comments/${commentId}`, { text });
    return response.data;
  },
  deleteComment: async (id: string, commentId: string) => {
    const response = await api.delete(`/v10/workflow-items/${id}/comments/${commentId}`);
    return response.data;
  },
  getHistory: async (id: string) => {
    const response = await api.get(`/v10/workflow-items/${id}/history`);
    return response.data;
  },
};

export const operationsApi = {
  getDispatchBoard: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/operations/dispatch-board');
    return response.data;
  },
  getExceptionMetrics: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/operations/exception-metrics');
    return response.data;
  },
  getAlerts: async (): Promise<ApiResponse<Record<string, unknown>[]>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>[]>>('/v10/operations/alerts');
    return response.data;
  },
  getShipmentMetrics: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/operations/shipment-metrics');
    return response.data;
  },
  getWorkerUtilization: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/operations/worker-utilization');
    return response.data;
  },
  getNotifications: async (): Promise<ApiResponse<Record<string, unknown>[]>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>[]>>('/v10/operations/notifications');
    return response.data;
  },
  getNotificationHistory: async (): Promise<ApiResponse<Record<string, unknown>[]>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>[]>>('/v10/operations/notification-history');
    return response.data;
  },
  getUnreadNotifications: async (): Promise<ApiResponse<{ count: number }>> => {
    const response = await api.get<ApiResponse<{ count: number }>>('/v10/operations/notifications/unread-count');
    return response.data;
  },
};

// Shipments API functions (used by Bookings page)
export const shipmentsApi = {
  getShipments: async (params?: ApiListParams) => {
    const response = await api.get('/v10/shipments', { params });
    return response.data as ApiResponse<unknown> & {
      data: Array<Record<string, unknown>>;
      pagination?: Record<string, unknown>;
    };
  },
  getStatistics: async () => {
    const response = await api.get('/v10/shipments/statistics');
    return response.data as ApiResponse<Record<string, number>>;
  },
};

export const reportsApi = {
  getSummary: async (payload: Record<string, unknown> = {}) => {
    const response = await api.post<ApiResponse<Record<string, unknown>>>('/v10/statement-reports', payload);
    return response.data;
  },
};

export const unifiedShipmentsApi = {
  getHubSortation: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/unified-shipments/hub-sortation');
    return response.data;
  },
  getInterBranchTransfers: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/unified-shipments/inter-branch-transfers');
    return response.data;
  },
  getWorkflowAnalytics: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/unified-shipments/workflow-analytics');
    return response.data;
  },
  getWorkflowAlerts: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/unified-shipments/workflow-alerts');
    return response.data;
  },
};

export const parcelsApi = {
  getLogs: async (parcelId: string | number): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>(`/v10/parcel/logs/${parcelId}`);
    return response.data;
  },
  trackByNumber: async (trackingNumber: string): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>(`/v10/parcel/tracking/${trackingNumber}`);
    return response.data;
  },
};

export const financeApi = {
  getInvoices: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/invoice-list/index');
    return response.data;
  },
  getInvoiceDetails: async (invoiceId: number | string): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>(`/v10/invoice-details/${invoiceId}`);
    return response.data;
  },
  getPaymentAccounts: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/payment-accounts/index');
    return response.data;
  },
  getPaymentRequests: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/payment-request/index');
    return response.data;
  },
  getSettlements: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/statements/index');
    return response.data;
  },
};

export const searchApi = {
  search: async (query: string, params: Record<string, unknown> = {}): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/search', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },
  autocomplete: async (query: string, limit = 10) => {
    const response = await api.get('/v10/search/autocomplete', {
      params: { q: query, limit },
    });
    return response.data as ApiResponse<Record<string, unknown>> & {
      suggestions?: Array<Record<string, unknown>>;
    };
  },
  advanced: async (params: Record<string, unknown>): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/search/advanced', { params });
    return response.data;
  },
  stats: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/search/stats');
    return response.data;
  },
};

export const generalSettingsApi = {
  getGeneralSettings: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/general-settings');
    return response.data;
  },
  getCodCharges: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/settings/cod-charges');
    return response.data;
  },
  getDeliveryCharges: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/settings/delivery-charges');
    return response.data;
  },
  getCurrencies: async (): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/v10/all-currencies');
    return response.data;
  },
  updateGeneralSettings: async (payload: FormData): Promise<ApiResponse<Record<string, unknown>>> => {
    const response = await api.put<ApiResponse<Record<string, unknown>>>('/v10/general-settings', payload, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    return response.data;
  },
};

export const bookingWizardApi = {
  step1: async (payload: Record<string, unknown>) => {
    const response = await api.post<ApiResponse<Record<string, unknown>>>('/v10/booking/step1', payload);
    return response.data;
  },
  step2: async (payload: Record<string, unknown>) => {
    const response = await api.post<ApiResponse<Record<string, unknown>>>('/v10/booking/step2', payload);
    return response.data;
  },
  step3: async (payload: Record<string, unknown>) => {
    const response = await api.post<ApiResponse<Record<string, unknown>>>('/v10/booking/step3', payload);
    return response.data;
  },
  step4: async () => {
    const response = await api.post<ApiResponse<Record<string, unknown>>>('/v10/booking/step4');
    return response.data;
  },
  step5: async (payload: Record<string, unknown>) => {
    const response = await api.post<ApiResponse<Record<string, unknown>>>('/v10/booking/step5', payload);
    return response.data;
  },
  downloadLabels: async (shipmentId: string | number) => {
    const response = await api.get(`/v10/booking/download-labels/${shipmentId}`, {
      responseType: 'blob',
    });
    return response.data as Blob;
  },
};

const DEFAULT_STATUS_FILTERS: SupportSelectOption[] = [
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'closed', label: 'Closed' },
];

const DEFAULT_PRIORITY_FILTERS: SupportSelectOption[] = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
];

const toNumberOr = (value: unknown, fallback: number): number => {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = Number(value);
    if (Number.isFinite(parsed)) {
      return parsed;
    }
  }

  return fallback;
};

const buildPaginationFromMeta = (meta: unknown, fallbackLength: number): PaginationMeta | undefined => {
  if (!meta || typeof meta !== 'object') {
    return undefined;
  }

  const metaRecord = meta as Record<string, unknown>;
  return {
    current_page: toNumberOr(metaRecord.current_page, 1),
    per_page: toNumberOr(metaRecord.per_page, fallbackLength > 0 ? fallbackLength : 10),
    last_page: toNumberOr(metaRecord.last_page, 1),
    total: toNumberOr(metaRecord.total, fallbackLength),
  };
};

const deriveDepartmentOptions = (supports: Support[]): SupportSelectOption[] => {
  const unique = new Map<string, SupportSelectOption>();

  supports.forEach((support) => {
    if (!support.department) {
      return;
    }
    const key = support.department.trim();
    if (!unique.has(key)) {
      unique.set(key, { value: key, label: support.department });
    }
  });

  return Array.from(unique.values());
};

const normaliseSelectOptionArray = (options: unknown): SupportSelectOption[] | undefined => {
  if (!Array.isArray(options)) {
    return undefined;
  }

  const resolved = options
    .map((item) => {
      if (!item || typeof item !== 'object') {
        return undefined;
      }
      const candidate = item as Record<string, unknown>;
      const value = candidate.value;
      const label = candidate.label;

      if (typeof value !== 'string' || typeof label !== 'string') {
        return undefined;
      }

      return { value, label } as SupportSelectOption;
    })
    .filter(Boolean) as SupportSelectOption[];

  return resolved.length > 0 ? resolved : undefined;
};

const normaliseSupportFilters = (rawFilters: unknown, supports: Support[]): SupportFilters => {
  const filtersRecord = rawFilters && typeof rawFilters === 'object'
    ? (rawFilters as Record<string, unknown>)
    : {};

  const statusOptions = normaliseSelectOptionArray(filtersRecord.status_options) ?? DEFAULT_STATUS_FILTERS;
  const priorityOptions = normaliseSelectOptionArray(filtersRecord.priority_options) ?? DEFAULT_PRIORITY_FILTERS;
  const departmentOptions = normaliseSelectOptionArray(filtersRecord.department_options) ?? deriveDepartmentOptions(supports);

  return {
    status_options: statusOptions,
    priority_options: priorityOptions,
    department_options: departmentOptions,
  };
};

const normaliseSupportListResponse = (rawData: unknown): SupportListResponse => {
  const dataRecord = rawData && typeof rawData === 'object'
    ? (rawData as Record<string, unknown>)
    : {};

  const rawSupports = dataRecord.supports as unknown;
  let supports: Support[] = [];
  let pagination: PaginationMeta | undefined;

  if (Array.isArray(rawSupports)) {
    supports = rawSupports as Support[];
  } else if (rawSupports && typeof rawSupports === 'object') {
    const supportsRecord = rawSupports as Record<string, unknown>;
    if (Array.isArray(supportsRecord.data)) {
      supports = supportsRecord.data as Support[];
    }
    pagination = buildPaginationFromMeta(supportsRecord.meta, supports.length);
  }

  const summary = dataRecord.summary as SupportSummary | undefined;
  const filters = normaliseSupportFilters(dataRecord.filters, supports);

  return {
    supports,
    pagination,
    summary,
    filters,
  };
};

export const supportApi = {
  getSupports: async (params?: ApiListParams): Promise<SupportListResponse> => {
    const response = await api.get('/v10/support/index', { params });
    const payload = response.data as ApiResponse<Record<string, unknown>>;

    if (!payload?.success) {
      throw new Error(payload?.message ?? 'Failed to fetch support tickets');
    }

    return normaliseSupportListResponse(payload.data);
  },
  getCreateMeta: async (): Promise<ApiResponse<SupportCreateMeta>> => {
    const response = await api.get('/v10/support/create');
    return response.data;
  },
  createSupport: async (payload: SupportFormData): Promise<ApiResponse<{ support: Support }>> => {
    const formData = new FormData();
    formData.append('department_id', payload.department_id);
    formData.append('service', payload.service);
    formData.append('priority', payload.priority);
    formData.append('subject', payload.subject);
    formData.append('description', payload.description);
    if (payload.attached_file) {
      formData.append('attached_file', payload.attached_file);
    }

    const response = await api.post('/v10/support/store', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },
  getEditMeta: async (supportId: number): Promise<ApiResponse<SupportEditMeta>> => {
    const response = await api.get(`/v10/support/edit/${supportId}`);
    return response.data;
  },
  getSupportDetail: async (supportId: number): Promise<ApiResponse<SupportDetail>> => {
    const response = await api.get(`/v10/support/view/${supportId}`);
    return response.data;
  },
  updateSupport: async (supportId: number, payload: SupportFormData): Promise<ApiResponse<{ support: Support }>> => {
    const formData = new FormData();
    formData.append('department_id', payload.department_id);
    formData.append('service', payload.service);
    formData.append('priority', payload.priority);
    formData.append('subject', payload.subject);
    formData.append('description', payload.description);
    if (payload.attached_file) {
      formData.append('attached_file', payload.attached_file);
    }

    const response = await api.put(`/v10/support/update/${supportId}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },
  deleteSupport: async (supportId: number): Promise<ApiResponse<unknown>> => {
    const response = await api.delete(`/v10/support/delete/${supportId}`);
    return response.data;
  },
  replyToSupport: async (payload: SupportReplyData): Promise<ApiResponse<unknown>> => {
    const formData = new FormData();
    formData.append('support_id', payload.support_id.toString());
    formData.append('message', payload.message);
    if (payload.attached_file) {
      formData.append('attached_file', payload.attached_file);
    }

    const response = await api.post('/v10/support/reply', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },
};

export default api;
