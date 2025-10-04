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
  BranchListParams,
  BranchListResponse,
} from '../types/branches';
import type {
  MerchantDetailResponse,
  MerchantListParams,
  MerchantListResponse,
} from '../types/merchants';
import type { WorkflowBoardResponse } from '../types/workflow';

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

    if (status && [401, 403, 419].includes(status)) {
      redirectToLogin();
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
